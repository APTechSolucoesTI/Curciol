<?php

/**
 * Regras do pre-processo.
 *
 * O pre-processo e o proprio processo na sua fase inicial: mesmo registro,
 * mesmo id, mesmos relacionamentos. Quando a publicacao correspondente
 * chega, o registro recebe os dados judiciais e deixa de ser pendente. Nao
 * ha copia, nao ha segundo cadastro.
 *
 * Toda a regra mora aqui, e nao nas telas, por dois motivos: as telas de
 * processo sao geradas pelo Mad Builder e uma regeneracao apagaria o que
 * estivesse dentro delas; e a mesma regra e chamada de quatro lugares
 * diferentes (cadastro manual, wizard de contratos, publicacoes e portal).
 *
 * Convencao de transacao: nenhum metodo aqui abre ou fecha transacao. Quem
 * chama controla o escopo, e e assim que a criacao pelo wizard consegue ser
 * atomica (processo + contrato + andamento em uma unica transacao).
 */
class PreProcessoService
{
    const SIM = 'S';
    const NAO = 'N';

    /** Filtros da listagem de processos. */
    const SITUACAO_TODOS      = 'TODOS';
    const SITUACAO_PENDENTES  = 'PENDENTES';
    const SITUACAO_DEFINITIVOS = 'DEFINITIVOS';

    /** Usado quando a publicacao traz o numero do processo principal. */
    const NIVEL_PROCESSO  = 'PROCESSO';
    const NIVEL_PRINCIPAL = 'PRINCIPAL';

    // =================================================================
    // Estado
    // =================================================================

    /**
     * O registro ainda aguarda distribuicao judicial?
     *
     * @param Processo|ProcessoView|stdClass|null $processo
     */
    public static function ehPreProcesso($processo): bool
    {
        if (empty($processo))
        {
            return false;
        }

        return strtoupper(trim((string) ($processo->pre_processo ?? ''))) === self::SIM;
    }

    /**
     * O registro nasceu como pre-processo e ja foi convertido?
     *
     * @param Processo|ProcessoView|stdClass|null $processo
     */
    public static function foiConvertido($processo): bool
    {
        if (empty($processo) || self::ehPreProcesso($processo))
        {
            return false;
        }

        return !empty($processo->data_conversao);
    }

    /**
     * Rotulo curto do estado, para listagens e para a janela de pesquisa.
     */
    public static function estado($processo): string
    {
        if (self::ehPreProcesso($processo))
        {
            return 'Aguardando distribuição';
        }

        if (self::foiConvertido($processo))
        {
            return 'Convertido em processo';
        }

        return 'Processo';
    }

    /**
     * Como o numero do processo deve ser lido na tela.
     *
     * Um pre-processo nao tem numero, e mostrar um campo em branco faz o
     * leitor achar que a informacao se perdeu. O texto diz o que esta
     * acontecendo.
     */
    public static function rotuloNumero($processo): string
    {
        $numero = trim((string) ($processo->numero_cnj_numero ?? $processo->numero ?? ''));

        if ($numero !== '')
        {
            return $numero;
        }

        if (self::ehPreProcesso($processo))
        {
            return 'Aguardando distribuição';
        }

        return '-';
    }

    /**
     * Identificacao textual do registro, para combos e mensagens.
     *
     * Enquanto nao existe numero, o que identifica o registro e a descricao
     * mais o id interno.
     */
    public static function identificacao($processo): string
    {
        if (empty($processo))
        {
            return '';
        }

        $numero    = trim((string) ($processo->numero_cnj_numero ?? ''));
        $descricao = trim((string) ($processo->descricao_pre_processo ?? ''));

        // Com numero, a descricao do pre-processo deixa de aparecer.
        if ($numero !== '')
        {
            return "#{$processo->id} - {$numero}";
        }

        return $descricao !== ''
            ? "#{$processo->id} - {$descricao}"
            : "#{$processo->id} - sem número e sem descrição";
    }

    // =================================================================
    // Normalizacao e validacao do cadastro
    // =================================================================

    /**
     * Normaliza o estado e o numero antes de gravar.
     *
     * Ponto importante: um pre-processo grava numero_cnj_numero como NULL,
     * nunca como string vazia. O indice unico parcial criado na migracao
     * ignora NULL, entao varios pre-processos convivem; '' colidiria a
     * partir do segundo registro.
     *
     * @param object $data Dados vindos do formulario (por referencia).
     */
    public static function normalizarDadosCadastro($data): void
    {
        $data->pre_processo = strtoupper(trim((string) ($data->pre_processo ?? ''))) === self::SIM
            ? self::SIM
            : self::NAO;

        $numero = trim((string) ($data->numero_cnj_numero ?? ''));
        $data->numero_cnj_numero = ($numero === '') ? null : $numero;

        $descricao = trim((string) ($data->descricao_pre_processo ?? ''));
        $data->descricao_pre_processo = ($descricao === '') ? null : $descricao;

        $numero_outro = trim((string) ($data->numero_outro ?? ''));
        $data->numero_outro = ($numero_outro === '') ? null : $numero_outro;
    }

    /**
     * Validacao de servidor do cadastro de processo.
     *
     * Existe porque esconder um campo obrigatorio no navegador nao torna o
     * dado opcional: a requisicao pode chegar sem passar pela tela.
     *
     * @param object   $data
     * @param int|null $processo_id  Id em edicao, para nao colidir consigo mesmo.
     * @param bool     $tem_contrato Se ha contrato vinculado ao processo.
     */
    public static function validarCadastro($data, $processo_id = null, $tem_contrato = true): void
    {
        $eh_pre = (($data->pre_processo ?? self::NAO) === self::SIM);
        $numero = $data->numero_cnj_numero ?? null;

        if ($eh_pre)
        {
            if (!empty($numero))
            {
                throw new Exception('Um pré-processo não pode ter número judicial. Desmarque "É um pré-processo?" ou remova o número.');
            }

            if (empty(trim((string) ($data->descricao_pre_processo ?? ''))))
            {
                throw new Exception('Informe a descrição do pré-processo. É por ela que o registro será encontrado enquanto não existir número.');
            }

            /*
                O contrato e o que diz de quem e o processo - e, sem numero, e
                tambem o unico vinculo que o pre-processo tem com o cliente.
                Sem ele o registro nao chega ao portal e nao ha a quem
                apresenta-lo.
            */
            if (!$tem_contrato)
            {
                throw new Exception('Vincule o contrato na aba "Contratos". É por ele que o cliente é identificado, e um pré-processo sem contrato não aparece no portal.');
            }
        }
        else
        {
            if (empty($numero))
            {
                throw new Exception('Informe o número do processo. Se o processo ainda não foi distribuído, marque "É um pré-processo?".');
            }
        }

        if (empty($data->tipo_processo_id))
        {
            throw new Exception('Informe o tipo de processo.');
        }

        if (!empty($numero))
        {
            self::validarNumeroDisponivel($numero, $processo_id);
        }
    }

    /**
     * O numero ja pertence a outro processo?
     *
     * @param string   $numero
     * @param int|null $ignorar_processo_id
     */
    public static function validarNumeroDisponivel($numero, $ignorar_processo_id = null): void
    {
        $numero = trim((string) $numero);

        if ($numero === '')
        {
            return;
        }

        $existente = Processo::where('numero_cnj_numero', '=', $numero)->first();

        if ($existente && (int) $existente->id !== (int) $ignorar_processo_id)
        {
            throw new Exception("O número {$numero} já pertence ao processo #{$existente->id}. Não é possível usá-lo novamente.");
        }
    }

    // =================================================================
    // Clientes
    // =================================================================

    /**
     * Clientes do processo.
     *
     * O caminho e um so: contrato_processo -> contrato_pessoa. Um processo,
     * pre ou definitivo, sempre nasce amarrado a um contrato, e e o contrato
     * que diz de quem ele e.
     *
     * (Houve aqui, brevemente, uma tabela processo_cliente para o caso de um
     * pre-processo existir antes do contrato. Esse caso nao existe no
     * escritorio, e a tabela foi removida em 23/09/2026.)
     *
     * @return array [cliente_id => nome]
     */
    public static function clientesDoProcesso($processo_id): array
    {
        $processo_id = (int) $processo_id;

        if ($processo_id <= 0)
        {
            return [];
        }

        $clientes = [];

        $conn = TTransaction::get();

        $sql = "
            SELECT DISTINCT pe.id, pe.nome
            FROM contrato_processo cp
            JOIN contrato_pessoa cpe ON cpe.contrato_id = cp.contrato_id
            JOIN pessoa pe ON pe.id = cpe.cliente_id
            WHERE cp.processo_id = :processo_id
            ORDER BY pe.nome
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':processo_id' => $processo_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $linha)
        {
            $clientes[(int) $linha->id] = $linha->nome;
        }

        return $clientes;
    }

    /**
     * O processo tem contrato vinculado?
     *
     * E por ele que o cliente chega, entao e o que o cadastro de
     * pre-processo exige antes de gravar.
     */
    public static function temContrato($processo_id): bool
    {
        $processo_id = (int) $processo_id;

        if ($processo_id <= 0)
        {
            return false;
        }

        return ContratoProcesso::where('processo_id', '=', $processo_id)->count() > 0;
    }

    /**
     * O cliente logado no portal pode ver este processo?
     *
     * Vale tanto para pre-processo quanto para processo convencional: a
     * pergunta e sempre a mesma, "este registro e dele?".
     */
    public static function clienteTemAcesso($processo_id, $cliente_id): bool
    {
        $cliente_id = (int) $cliente_id;

        if ($cliente_id <= 0)
        {
            return false;
        }

        return array_key_exists($cliente_id, self::clientesDoProcesso($processo_id));
    }

    // =================================================================
    // Etapa e andamento de organizacao documental
    // =================================================================

    /**
     * A etapa marcada como "Padrão pré-processo" para a trilha do processo.
     *
     * Quem escolhe e o cadastro de etapas, nao o codigo. Antes esta rotina
     * procurava a etapa pelo nome ("organiza...documental"): funcionava, mas
     * bastava alguem renomear a etapa para a criacao automatica do andamento
     * parar de acontecer, sem aviso nenhum.
     *
     * Ha no maximo uma etapa marcada por trilha - o banco garante isso com
     * dois indices unicos parciais. Nada e criado aqui: se nenhuma etapa
     * estiver marcada, quem chama decide o que fazer.
     */
    public static function etapaPadraoPreProcesso($tipo_processo_id): ?PublicacaoEtapa
    {
        $tipo_processo_id = (int) $tipo_processo_id;

        $repositorio = PublicacaoEtapa::where('padrao_pre_processo', '=', self::SIM);

        if ($tipo_processo_id === (int) TipoProcesso::JUDICIAL)
        {
            $repositorio->where('judicial', '=', self::SIM);
        }
        elseif ($tipo_processo_id === (int) TipoProcesso::EXTRAJUDICIAL)
        {
            $repositorio->where('extrajudicial', '=', self::SIM);
        }

        return $repositorio->orderBy('ordem_prioridade', 'asc')->first();
    }

    /**
     * Etapas validas para a trilha de um tipo de processo.
     *
     * Usada pelos combos de etapa: oferecer uma etapa de outra trilha e
     * oferecer um caminho que a timeline depois vai ignorar.
     */
    public static function criteriaEtapasDoTipo($tipo_processo_id): TCriteria
    {
        $criteria = new TCriteria();
        $tipo_processo_id = (int) $tipo_processo_id;

        if ($tipo_processo_id === (int) TipoProcesso::JUDICIAL)
        {
            $criteria->add(new TFilter('judicial', '=', self::SIM));
        }
        elseif ($tipo_processo_id === (int) TipoProcesso::EXTRAJUDICIAL)
        {
            $criteria->add(new TFilter('extrajudicial', '=', self::SIM));
        }

        return $criteria;
    }

    /**
     * Garante "somente uma etapa padrao por trilha".
     *
     * Desmarca as concorrentes antes de gravar a nova. O banco tem indice
     * unico parcial para as duas trilhas; sem esta limpeza o salvamento
     * esbarraria nele. Devolve os nomes das etapas que sairam, para a tela
     * poder dizer ao usuario o que mudou.
     *
     * @return string[]
     */
    public static function liberarEtapaPadrao(PublicacaoEtapa $etapa): array
    {
        $substituidas = [];

        foreach (['judicial', 'extrajudicial'] as $trilha)
        {
            if (strtoupper(trim((string) $etapa->{$trilha})) !== self::SIM)
            {
                continue;
            }

            $concorrentes = PublicacaoEtapa::where('padrao_pre_processo', '=', self::SIM)
                                           ->where($trilha, '=', self::SIM)
                                           ->load();

            foreach ($concorrentes as $concorrente)
            {
                if ((int) $concorrente->id === (int) $etapa->id)
                {
                    continue;
                }

                $concorrente->padrao_pre_processo = self::NAO;
                $concorrente->store();

                $substituidas[$concorrente->id] = $concorrente->etapa_nome;
            }
        }

        return array_values($substituidas);
    }

    /**
     * Tipo de andamento usado no registro automatico.
     *
     * Reaproveita o cadastro existente em vez de criar um tipo novo. A
     * preferencia e por um tipo com nome parecido; se nao houver, cai em
     * "Outros" e, no limite, no primeiro cadastrado.
     */
    public static function tipoAndamentoPadrao(): ?TipoAndamento
    {
        $tipo = TipoAndamento::where('nome', 'ilike', '%organiza%')->first();

        if (!$tipo)
        {
            $tipo = TipoAndamento::where('nome', 'ilike', '%outro%')->first();
        }

        if (!$tipo)
        {
            $tipo = TipoAndamento::where('id', '>', 0)->orderBy('id', 'asc')->first();
        }

        return $tipo;
    }

    /**
     * Cria o andamento inicial do pre-processo.
     *
     * A etapa vem do cadastro - a que estiver marcada como "Padrão
     * pré-processo" para a trilha do processo. O titulo e o texto saem da
     * propria etapa, entao quem escreve o que o cliente le e quem cadastra a
     * etapa, nao este codigo.
     *
     * Usa o modelo Andamento real e a mesma ponte processo_publicacoes que a
     * timeline ja consome, para o registro nascer visivel sem nenhuma
     * estrutura paralela.
     *
     * Idempotente: se o processo ja tem um andamento nessa etapa, devolve o
     * que existe. Isso protege o duplo clique e o F5 no wizard.
     *
     * Devolve null quando nenhuma etapa esta marcada para a trilha. Nao e
     * erro: o pre-processo continua valido, so nasce sem o andamento. Quem
     * chama avisa o usuario.
     */
    public static function criarAndamentoInicial(Processo $processo, $user_id = null): ?Andamento
    {
        $etapa = self::etapaPadraoPreProcesso($processo->tipo_processo_id);

        if (!$etapa)
        {
            return null;
        }

        $existente = Andamento::where('processo_id', '=', (int) $processo->id)
                              ->where('publicacao_etapa_id', '=', (int) $etapa->id)
                              ->first();

        if ($existente)
        {
            self::garantirPonteAndamento($existente);

            return $existente;
        }

        $agora = date('Y-m-d H:i:s');

        $andamento = new Andamento();
        $andamento->processo_id         = (int) $processo->id;
        $andamento->publicacao_etapa_id = (int) $etapa->id;
        $andamento->tipo_andamento_id   = (self::tipoAndamentoPadrao())->id ?? null;

        /*
            Data real do registro. O que nao existe ainda e a distribuicao
            judicial, e essa nao se inventa.
        */
        $andamento->data_andamento    = $agora;

        /*
            Titulo e texto saem da etapa. "Explicação" (descricao) e o campo
            que o escritorio escreve pensando no cliente - e exatamente o que
            deve aparecer na timeline dele. Se estiver em branco, entra uma
            frase neutra em vez de um andamento sem conteudo.
        */
        $andamento->titulo = trim((string) $etapa->etapa_nome);

        $explicacao = trim((string) $etapa->descricao);
        $andamento->texto = ($explicacao !== '')
            ? $explicacao
            : 'O escritório iniciou o trabalho contratado.';

        $andamento->etapa_verificada  = self::SIM;
        $andamento->criacao_user_id   = $user_id;
        $andamento->store();

        self::garantirPonteAndamento($andamento);

        return $andamento;
    }

    /**
     * Garante a linha de processo_publicacoes que leva o andamento para a
     * timeline, sem duplicar se ela ja existir.
     */
    public static function garantirPonteAndamento(Andamento $andamento): ProcessoPublicacoes
    {
        $ponte = ProcessoPublicacoes::where('andamento_id', '=', (int) $andamento->id)->first();

        if (!$ponte)
        {
            $ponte = new ProcessoPublicacoes();
            $ponte->processo_id = (int) $andamento->processo_id;
            $ponte->andamento_id = (int) $andamento->id;
        }

        $ponte->publicacao_etapa_id = (int) $andamento->publicacao_etapa_id;

        if (empty($ponte->date_log))
        {
            $ponte->date_log = $andamento->data_andamento ?: date('Y-m-d H:i:s');
        }

        $ponte->store();

        return $ponte;
    }

    // =================================================================
    // Criacao a partir do contrato (wizard)
    // =================================================================

    /**
     * O contrato ja gerou um pre-processo?
     *
     * Consultado antes de criar: se ja existe, o wizard apresenta o que
     * existe em vez de gerar outro. E o que impede o F5 e o duplo clique de
     * produzirem registros repetidos.
     */
    public static function processoDoContrato($contrato_id): ?Processo
    {
        $contrato_id = (int) $contrato_id;

        if ($contrato_id <= 0)
        {
            return null;
        }

        $vinculo = ContratoProcesso::where('contrato_id', '=', $contrato_id)
                                   ->orderBy('id', 'asc')
                                   ->first();

        if (!$vinculo)
        {
            return null;
        }

        return Processo::find((int) $vinculo->processo_id);
    }

    /**
     * Cria o pre-processo a partir de um contrato ja gravado.
     *
     * Reaproveita o que o contrato ja sabe (clientes, tipo, envolvimento,
     * area, assunto e o objeto como base da descricao) e deixa em branco o
     * que so a distribuicao judicial define.
     *
     * Quem chama deve estar dentro de uma transacao: processo, vinculo com
     * o contrato, clientes e andamento inicial vao juntos ou nao vao.
     */
    public static function criarAPartirDoContrato($contrato_id, $user_id = null): Processo
    {
        $contrato_id = (int) $contrato_id;

        $contrato = Contrato::find($contrato_id);

        if (!$contrato)
        {
            throw new Exception("Contrato #{$contrato_id} não encontrado. O pré-processo não foi criado.");
        }

        $existente = self::processoDoContrato($contrato_id);

        if ($existente)
        {
            return $existente;
        }

        $processo = new Processo();
        $processo->pre_processo     = self::SIM;
        $processo->tipo_processo_id = $contrato->tipo_processo_id ?: TipoProcesso::JUDICIAL;
        $processo->envolvimento_id  = $contrato->envolvimento_id ?: null;
        $processo->area_id          = $contrato->area_id ?: null;
        $processo->assunto_id       = $contrato->assunto_id ?: null;

        /* Sem numero: nao ha distribuicao. NULL, nunca '' nem 0000000. */
        $processo->numero_cnj_numero = null;

        $processo->descricao_pre_processo = self::descricaoAPartirDoContrato($contrato);

        /*
            Nasce visivel para o cliente: acompanhar a preparacao antes da
            distribuicao e justamente o objetivo do pre-processo. Quem nao
            quiser desmarca no cadastro.
        */
        $processo->exibir_cliente  = self::SIM;
        $processo->criacao_user_id = $user_id;
        $processo->store();

        $vinculo = new ContratoProcesso();
        $vinculo->contrato_id     = $contrato_id;
        $vinculo->processo_id     = (int) $processo->id;
        $vinculo->data_criacao    = date('Y-m-d H:i:s');
        $vinculo->criacao_user_id = $user_id;
        $vinculo->store();

        /*
            Os clientes nao sao copiados para lugar nenhum: eles ja estao em
            contrato_pessoa, e e de la que o portal os le. O vinculo com o
            contrato, criado acima, e o que amarra tudo.
        */

        self::criarAndamentoInicial($processo, $user_id);

        return $processo;
    }

    /**
     * Descricao inicial a partir do objeto do contrato.
     */
    public static function descricaoAPartirDoContrato(Contrato $contrato): string
    {
        $objeto = trim(strip_tags((string) ($contrato->objeto ?? '')));
        $objeto = trim(preg_replace('/\s+/', ' ', $objeto));

        if ($objeto !== '')
        {
            if (mb_strlen($objeto) > 180)
            {
                $objeto = mb_substr($objeto, 0, 179) . '…';
            }

            return $objeto;
        }

        return "Contrato {$contrato->numero}";
    }

    // =================================================================
    // Conversao
    // =================================================================

    /**
     * Numero que a publicacao oferece para o nivel pedido.
     */
    public static function numeroDaPublicacao(Publicacao $publicacao, $nivel): ?string
    {
        $numero = ($nivel === self::NIVEL_PRINCIPAL)
            ? $publicacao->numero_processo_principal
            : $publicacao->numero_unico_processo;

        $numero = trim((string) $numero);

        return ($numero === '') ? null : $numero;
    }

    /**
     * Converte o pre-processo em processo definitivo.
     *
     * O id nao muda. O registro que era pre-processo passa a ter numero e
     * deixa de ficar pendente; tudo que ja apontava para ele continua
     * apontando. Contratos, contrapartes, clientes, andamentos, documentos
     * e tarefas nao sao tocados de proposito - eles nunca deixaram de estar
     * corretos.
     *
     * Quem chama deve estar dentro de uma transacao.
     *
     * @param int    $processo_id   Pre-processo escolhido.
     * @param int    $publicacao_id Publicacao de origem, sempre explicita.
     * @param string $nivel         PROCESSO ou PRINCIPAL.
     */
    public static function converter($processo_id, $publicacao_id, $nivel = self::NIVEL_PROCESSO, $user_id = null): Processo
    {
        $processo_id   = (int) $processo_id;
        $publicacao_id = (int) $publicacao_id;
        $nivel         = ($nivel === self::NIVEL_PRINCIPAL) ? self::NIVEL_PRINCIPAL : self::NIVEL_PROCESSO;

        $processo = Processo::find($processo_id);

        if (!$processo)
        {
            throw new Exception("Pré-processo #{$processo_id} não encontrado.");
        }

        if (!self::ehPreProcesso($processo))
        {
            throw new Exception("O processo #{$processo_id} não é um pré-processo pendente. Não há o que converter.");
        }

        $publicacao = Publicacao::find($publicacao_id);

        if (!$publicacao)
        {
            throw new Exception("Publicação #{$publicacao_id} não encontrada.");
        }

        $numero = self::numeroDaPublicacao($publicacao, $nivel);

        if (empty($numero))
        {
            $campo = ($nivel === self::NIVEL_PRINCIPAL) ? 'do processo principal' : 'do processo';

            throw new Exception("A publicação #{$publicacao_id} não traz o número {$campo}. Sem número não há como converter o pré-processo.");
        }

        /*
            Conflito antes de qualquer escrita. Dois processos com o mesmo
            numero seriam dois donos do mesmo caso; a saida e o usuario
            resolver, nao o sistema mesclar.
        */
        $conflito = Processo::where('numero_cnj_numero', '=', $numero)->first();

        if ($conflito && (int) $conflito->id !== $processo_id)
        {
            throw new Exception("O número {$numero} já pertence ao processo #{$conflito->id}. A conversão foi cancelada e nada foi alterado. Verifique se a publicação não deveria ser vinculada àquele processo.");
        }

        /*
            Para o nivel PROCESSO a publicacao passa a pertencer a este
            registro. Se ela ja pertence a outro, a associacao manual
            anterior nao e desfeita por aqui.
        */
        if ($nivel === self::NIVEL_PROCESSO
            && !empty($publicacao->processo_id)
            && (int) $publicacao->processo_id !== $processo_id)
        {
            throw new Exception("A publicação #{$publicacao_id} já está vinculada ao processo #{$publicacao->processo_id}. Desfaça esse vínculo antes de convertê-la em outro registro.");
        }

        $processo->numero_cnj_numero = $numero;
        $processo->pre_processo      = self::NAO;
        $processo->data_conversao    = date('Y-m-d H:i:s');
        $processo->conversao_user_id = $user_id;

        /*
            descricao_pre_processo permanece: e o rastro da origem e continua
            servindo de busca depois que o numero existe.

            Tribunal, foro, comarca, vara e data de distribuicao nao sao
            preenchidos: a publicacao nao carrega esses campos de forma
            confiavel e inventa-los seria pior do que deixa-los pendentes.
        */

        $processo->modificacao_user_id = $user_id;
        $processo->store();

        if ($nivel === self::NIVEL_PROCESSO)
        {
            $publicacao->processo_id = $processo_id;

            if (empty(trim((string) $publicacao->numero_unico_processo)))
            {
                $publicacao->numero_unico_processo = $numero;
            }

            $publicacao->store();
        }
        else
        {
            /*
                Nivel PRINCIPAL: o pre-processo convertido vira o principal do
                processo que a publicacao ja aponta. O vinculo so e criado se
                ainda nao existir.
            */
            if (!empty($publicacao->processo_id) && (int) $publicacao->processo_id !== $processo_id)
            {
                self::garantirVinculoPrincipal($processo_id, (int) $publicacao->processo_id);
            }

            if (empty(trim((string) $publicacao->numero_processo_principal)))
            {
                $publicacao->numero_processo_principal = $numero;
                $publicacao->store();
            }
        }

        self::adotarPublicacoesOrfas($processo_id, $numero);

        APIPublicacaoController::adicionarMovimentacao(
            $publicacao_id,
            'Pré-processo vinculado e convertido em processo definitivo.',
            null,
            $processo_id
        );

        return $processo;
    }

    /**
     * Liga ao processo as publicacoes com o mesmo numero que ainda estao sem
     * dono. Publicacoes ja vinculadas a outro processo nao sao tocadas.
     */
    public static function adotarPublicacoesOrfas($processo_id, $numero): int
    {
        $adotadas = 0;

        $publicacoes = Publicacao::where('numero_unico_processo', '=', $numero)->load();

        foreach ($publicacoes as $publicacao)
        {
            if (!empty($publicacao->processo_id))
            {
                continue;
            }

            $publicacao->processo_id = (int) $processo_id;
            $publicacao->store();

            APIPublicacaoController::adicionarMovimentacao(
                $publicacao->id,
                'Processo vinculado.',
                null,
                (int) $processo_id
            );

            $adotadas++;
        }

        return $adotadas;
    }

    /**
     * Cria o vinculo principal/incidente se ele ainda nao existir.
     */
    public static function garantirVinculoPrincipal($processo_principal_id, $processo_incidente_id): void
    {
        $processo_principal_id = (int) $processo_principal_id;
        $processo_incidente_id = (int) $processo_incidente_id;

        if ($processo_principal_id <= 0 || $processo_incidente_id <= 0 || $processo_principal_id === $processo_incidente_id)
        {
            return;
        }

        $existe = ProcessoVinculo::where('processo_principal_id', '=', $processo_principal_id)
                                 ->where('processo_incidente_id', '=', $processo_incidente_id)
                                 ->count();

        if ($existe > 0)
        {
            return;
        }

        $vinculo = new ProcessoVinculo();
        $vinculo->processo_principal_id = $processo_principal_id;
        $vinculo->processo_incidente_id = $processo_incidente_id;
        $vinculo->store();
    }

    // =================================================================
    // Apresentacao
    // =================================================================

    /**
     * Selo "PRE-PROCESSO" usado na listagem e no portal.
     *
     * A folha de estilo de cada contexto define a aparencia; aqui so sai a
     * marcacao, com as classes que o tema ja conhece.
     */
    public static function selo($processo, $com_descricao = true, $com_estado = true): string
    {
        if (!self::ehPreProcesso($processo))
        {
            return '';
        }

        $html = "<span class='label label-warning curciol-selo-pre'>PRÉ-PROCESSO</span>";

        if ($com_descricao)
        {
            $descricao = trim((string) ($processo->descricao_pre_processo ?? ''));

            if ($descricao !== '')
            {
                $html .= "<div class='curciol-pre-descricao'>" . htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') . '</div>';
            }

            if ($com_estado)
            {
                $html .= "<div class='curciol-pre-estado'>Aguardando distribuição</div>";
            }
        }

        return $html;
    }
}
