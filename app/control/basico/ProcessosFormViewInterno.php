<?php

/*

class ProcessosFormViewInterno extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Pessoa';

*/
class ProcessosFormViewInterno extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_PessoaInterno';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        /*
            PRE-PROCESSO: dono do registro e autorizacao.

            Antes o cliente era descoberto so pela ponte contrato_processo ->
            contrato_pessoa. Um pre-processo pode existir antes do contrato, e
            nesse caso a consulta nao achava ninguem e a tela caia. Agora a
            pergunta passa pelo servico, que conhece os dois caminhos.

            A checagem de acesso e a segunda mudanca e vale para qualquer
            processo, nao so para pre-processo. Esta classe esta em
            public_classes e recebe processo_id direto da requisicao: sem
            conferir de quem e o registro, trocar o numero na URL mostrava o
            processo de outra pessoa. Com sessao de portal aberta, agora so
            passa o que pertence a quem esta logado. Sem sessao de portal - o
            caso do back-office - o comportamento e o mesmo de antes.
        */
        $processo_id_pedido = (int) ($param['processo_id'] ?? 0);
        $cliente_logado     = (int) (TSession::getValue('portal_cliente_id') ?? 0);

        /*
            PROCESSOS VINCULADOS: o processo aberto aqui e o topo de uma
            familia, e a tela mostra a familia que este cliente pode ver
            (ProcessoFamiliaService). A checagem de exibir_cliente, que era
            so do proprio processo, passa a ser feita pelo servico, junto com
            a dos ancestrais e a do cliente em cada processo da familia.
        */
        $familia_ids = [];

        if ($processo_id_pedido > 0)
        {
            $clientes_do_processo = PreProcessoService::clientesDoProcesso($processo_id_pedido);

            if (empty($clientes_do_processo))
            {
                throw new Exception('Este processo ainda não tem cliente vinculado. Vincule o cliente no cadastro do processo para que ele apareça no portal.');
            }

            if ($cliente_logado > 0)
            {
                if (!array_key_exists($cliente_logado, $clientes_do_processo))
                {
                    throw new Exception('Você não tem acesso a este processo.');
                }

                $param['key'] = $cliente_logado;
            }
            elseif (!TSession::getValue('logged'))
            {
                /*
                    Sem cliente no portal e sem usuario do back-office: ninguem
                    a quem mostrar. Antes, qualquer processo_id na URL abria.
                */
                throw new Exception('Você não tem acesso a este processo.');
            }
            elseif (empty($param['key']))
            {
                $param['key'] = array_key_first($clientes_do_processo);
            }

            /*
                Vazio quando o processo nao e um topo deste cliente: oculto,
                debaixo de um processo oculto, ou incidente de um processo que
                ele ja ve - nesse caso ele entra pela familia do topo.
            */
            $familia_ids = ProcessoFamiliaService::familiaVisivel($processo_id_pedido, (int) $param['key']);

            if (empty($familia_ids))
            {
                throw new Exception('Este processo não está disponível para acompanhamento.');
            }
        }

        if (empty($param['key']))
        {
            throw new Exception('Pessoa não informada.');
        }

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("");

        /*

        $publicacao_etapa_id = new TDBArrowStep('publicacao_etapa_id', 'escritorio', 'PublicacaoEtapa', 'id', '{etapa_nome}','id asc' );
        $processo_view = new BPageContainer();

        $publicacao_etapa_id->setEditable(false);
        $publicacao_etapa_id->setColorColumn('cor');
        $publicacao_etapa_id->setFilledColor('#fa931f');
        $publicacao_etapa_id->setFilledFontColor('#ffffff');
        $publicacao_etapa_id->setUnfilledColor('#d3d3d3');
        $publicacao_etapa_id->setUnfilledFontColor('#333333');
        $publicacao_etapa_id->setWidth('100%');
        $publicacao_etapa_id->setHeight('60');
        $publicacao_etapa_id->setValue($pessoa->id);
        $processo_view->setSize('100%');
        $processo_view->setAction(new TAction(['ProcessoPublicacoesTimeLine', 'onShow']));
        $processo_view->setId('b69d541fa523dc');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processo_view->add($loadingContainer);
        $processo_view->setParameter("processo_id", $param["processo_id"] ?? "");

*/      $processo_id = (int) ($param['processo_id'] ?? 0);

        $criteria_etapas = new TCriteria;

        $tipo_processo_id = null;
        $filtro_tipo_etapa_sql = '';

        $etapas_ids = [];
        $etapa_atual_id = null;
        $ordem_etapa_atual = null;

        $etapas_fixas = [];
        $etapas_ocultas = [1, 10];

        $dados_processo = null;
        $etapa_atual_nome = '-';

        if ($processo_id > 0)
        {
            $conn = TTransaction::get();

            /*
            * Descobre se o processo é Judicial ou Extrajudicial.
            * 1 = Judicial
            * 2 = Extrajudicial
            *
            * A mesma consulta já traz os dados exibidos no cabeçalho
            * do processo (tipo, assunto e número).
            */
            $stmt_tipo = $conn->prepare("
                SELECT
                    p.tipo_processo_id,
                    p.numero_cnj_numero,
                    p.pre_processo,
                    p.descricao_pre_processo,
                    tp.nome AS tipo_nome,
                    a.nome  AS assunto_nome
                FROM processo p
                LEFT JOIN tipo_processo tp
                    ON tp.id = p.tipo_processo_id
                LEFT JOIN assunto a
                    ON a.id = p.assunto_id
                WHERE p.id = :processo_id
                LIMIT 1
            ");

            $stmt_tipo->execute([
                ':processo_id' => $processo_id
            ]);

            $dados_processo = $stmt_tipo->fetch(PDO::FETCH_OBJ);

            $tipo_processo_id = (int) ($dados_processo->tipo_processo_id ?? 0);

            /*
                PROCESSOS VINCULADOS: a trilha e sempre a do topo (este
                processo). Movimentacoes dos incidentes em etapas de outra
                trilha ficam fora da faixa.
            */
            $filtro_tipo_etapa_sql = ProcessoFamiliaService::filtroTrilhaSql($tipo_processo_id);

            /*
            * Etapas de abertura: aparecem sempre, mesmo sem publicacao, e ja
            * contam como percorridas.
            *
            * Sao duas por trilha, e o proprio filtro Judicial/Extrajudicial
            * escolhe o par certo:
            *   extrajudicial -> 8  (Organizacao Documental) e 2  (Pedido Protocolado)
            *   judicial      -> 15 (Organizacao Documental) e 16 (Protocolo Inicial)
            *
            * A ordem vem de ordem_prioridade, como no cadastro. Antes havia
            * um CASE que fixava 8 e depois 2 por id; isso ignorava o valor
            * cadastrado e desalinhava esta consulta do ordenamento usado
            * pelo proprio ArrowStep.
            *
            * A consulta mora em ProcessoFamiliaService::etapasFixas, que a
            * lista "Meus processos" tambem usa.
            */
            $etapas_fixas = ProcessoFamiliaService::etapasFixas($tipo_processo_id);
        }

        if ($processo_id > 0)
        {
            $conn = TTransaction::get();

            /*
                Busca TODAS as etapas que realmente apareceram nas publicações.
                Essas etapas vão aparecer e ficar pintadas, desde que estejam antes ou sejam a etapa atual.

                PROCESSOS VINCULADOS: de todos os processos da familia que o
                cliente ve, e nao so deste.
            */
            $etapas_aparecidas = ProcessoFamiliaService::etapasAparecidas($familia_ids, $tipo_processo_id);

            /*
                Sempre começa com 8 e 2.
            */
            $etapas_ids = $etapas_fixas;

            /*
                Aqui adiciona todas as etapas que apareceram.
                Exemplo:
                Se apareceu Instrução, Decisão e Julgamento, as 3 entram.
                Mesmo que Julgamento seja a maior, as anteriores continuam aparecendo porque apareceram.
            */
            if ($etapas_aparecidas)
            {
                foreach ($etapas_aparecidas as $etapa)
                {
                    $id_etapa = (int) $etapa->id;

                    if (!in_array($id_etapa, $etapas_ocultas))
                    {
                        $etapas_ids[] = $id_etapa;
                    }
                }
            }

            /*
                Etapa atual = a maior pela ordem_prioridade, fora as de
                abertura. Essa será o setValue() do ArrowStep.

                Sem nenhuma etapa dinâmica, a base é a última etapa de abertura
                da trilha ("Protocolo Inicial" / "Pedido Protocolado").

                PRE-PROCESSO: menos uma. Marcar o protocolo como percorrido
                afirmaria que a peça foi protocolada - a movimentação oficial
                que ainda não aconteceu. Enquanto o registro estiver pendente,
                a trilha para na organização documental; na conversão o
                número chega e a etapa avança sozinha.

                A conta mora em ProcessoFamiliaService::etapaAtual, a mesma que
                a coluna "Última etapa" da lista usa: os dois lugares mostram
                sempre a mesma etapa.
            */
            $etapa_atual = ProcessoFamiliaService::etapaAtual($familia_ids, $dados_processo);

            $etapa_atual_id    = $etapa_atual ? (int) $etapa_atual->id : null;
            $ordem_etapa_atual = $etapa_atual ? (int) $etapa_atual->ordem_prioridade : 0;

            /*
                Agora busca as etapas futuras depois da maior etapa encontrada.
                Essas entram no ArrowStep, mas vão ficar cinzas.

                Exemplo:
                Se maior etapa apareceu = Julgamento de Recursos
                Então Cumprimento da Decisão e Processo Concluído aparecem cinzas.
            */
           $sql_etapas_futuras = "
                SELECT 
                    pe.id
                FROM publicacao_etapa pe
                WHERE pe.id NOT IN (1, 10)
                AND pe.id NOT IN (8, 2, 15, 16)

                {$filtro_tipo_etapa_sql}

                AND pe.ordem_prioridade > :ordem_etapa_atual

                ORDER BY
                    pe.ordem_prioridade ASC,
                    pe.id ASC
            ";

            $stmt_futuras = $conn->prepare($sql_etapas_futuras);
            $stmt_futuras->execute([
                ':ordem_etapa_atual' => $ordem_etapa_atual
            ]);

            $etapas_futuras = $stmt_futuras->fetchAll(PDO::FETCH_OBJ);

            if ($etapas_futuras)
            {
                foreach ($etapas_futuras as $etapa)
                {
                    $id_etapa = (int) $etapa->id;

                    if (!in_array($id_etapa, $etapas_ocultas))
                    {
                        $etapas_ids[] = $id_etapa;
                    }
                }
            }

            /*
                Remove duplicados.
            */
            $etapas_ids = array_values(array_unique($etapas_ids));

            $criteria_etapas->add(new TFilter('id', 'in', $etapas_ids));
        }
        else
        {
            $criteria_etapas->add(new TFilter('id', '=', 0));
        }

        $publicacao_etapa_id = new TDBArrowStep(
            'etapa_atual_processo',
            'escritorio',
            'PublicacaoEtapa',
            'id',
            '{etapa_nome}',
            'ordem_prioridade ASC, id ASC',
            $criteria_etapas
        );

        $processo_view = new BPageContainer();

        $publicacao_etapa_id->setEditable(false);
        $publicacao_etapa_id->setColorColumn('cor');
        $publicacao_etapa_id->setFilledColor('#fa931f');
        $publicacao_etapa_id->setFilledFontColor('#ffffff');
        $publicacao_etapa_id->setUnfilledColor('#d3d3d3');
        $publicacao_etapa_id->setUnfilledFontColor('#333333');
        $publicacao_etapa_id->setWidth('100%');
        $publicacao_etapa_id->setHeight('60');

        /*
            Esse é o ponto principal:
            o ArrowStep precisa receber a maior etapa que apareceu.
            Assim ele pinta 8, 2 e todas as etapas aparecidas antes dela.
            As futuras ficam cinzas.
        */
        if (!empty($etapa_atual_id) && in_array((int) $etapa_atual_id, $etapas_ids))
        {
            $publicacao_etapa_id->setValue((int) $etapa_atual_id);
        }

        /*
            Nome da etapa atual, exibido no cabeçalho do processo.
            Reaproveita o id que já foi calculado para o setValue().
        */
        if (!empty($etapa_atual_id))
        {
            $stmt_nome_etapa = TTransaction::get()->prepare("
                SELECT etapa_nome
                FROM publicacao_etapa
                WHERE id = :id
                LIMIT 1
            ");

            $stmt_nome_etapa->execute([
                ':id' => (int) $etapa_atual_id
            ]);

            $etapa_atual_nome = $stmt_nome_etapa->fetchColumn() ?: '-';
        }

        $processo_view->setId('processo_publicacoes_timeline_container');
        $processo_view->setSize('100%');

        $action_timeline = new TAction(['ProcessoPublicacoesTimeLine', 'onShow']);
        $action_timeline->setParameter('processo_id', $param['processo_id'] ?? '');

        /*
            So conta sem sessao de portal (back-office). Com cliente logado,
            a timeline ignora este parametro e usa a sessao.
        */
        $action_timeline->setParameter('cliente_id', $param['key'] ?? '');
        $action_timeline->setParameter('target_container', 'processo_publicacoes_timeline_container');

        $processo_view->setAction($action_timeline);

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processo_view->add($loadingContainer);

        /*
            Cabeçalho com as informações do processo, exibido acima do ArrowStep.
        */
        if ($processo_id > 0)
        {
            /*
                PRE-PROCESSO: o cabecalho conta o que esta acontecendo.

                Antes da distribuicao nao ha numero, e deixar o campo em branco
                faz o cliente achar que falta informacao. O texto diz o estado
                de verdade, e a descricao entra porque nessa fase e ela que
                identifica o trabalho. Nenhuma movimentacao oficial e inventada.

                Depois da conversao a mesma tela passa a mostrar o numero real,
                sem nenhuma troca de pagina.
            */
            $eh_pre_processo = PreProcessoService::ehPreProcesso($dados_processo);

            $info_processo = [
                'Tipo'    => $dados_processo->tipo_nome    ?? '',
                'Assunto' => $dados_processo->assunto_nome ?? '',
            ];

            if ($eh_pre_processo)
            {
                $info_processo['Status']    = 'Em preparação';
                $info_processo['Descrição'] = $dados_processo->descricao_pre_processo ?? '';
            }

            $info_processo['Número']       = PreProcessoService::rotuloNumero($dados_processo);
            $info_processo['Última etapa'] = $etapa_atual_nome;

            $info_html = '';

            foreach ($info_processo as $rotulo => $valor)
            {
                $valor = trim((string) $valor);

                if ($valor === '')
                {
                    $valor = '-';
                }

                $rotulo_html = htmlspecialchars((string) $rotulo, ENT_QUOTES, 'UTF-8');
                $valor_html  = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

                /* numero_cnj_numero é text e pode estourar a coluna no celular */
                $classe_valor = ($rotulo === 'Número')
                    ? 'curciol-info-valor curciol-info-valor-numero'
                    : 'curciol-info-valor';

                $info_html .= "
                    <div>
                        <span class='curciol-info-rotulo'>{$rotulo_html}</span>
                        <span class='{$classe_valor}'>{$valor_html}</span>
                    </div>
                ";
            }

            $info_container = new TElement('div');
            $info_container->class = 'curciol-info-grade';
            $info_container->add($info_html);

            $row0 = $this->form->addContent([$info_container]);
            $row0->layout = [' col-sm-12'];

            $row0->class = trim(($row0->class ?? '') . ' curciol-info-processo');
        }

        $row1 = $this->form->addFields([$publicacao_etapa_id]);
        $row1->layout = [' col-sm-12'];

        /*
            No celular a faixa de setas não cabe na largura da tela e obrigava o
            cliente a arrastar para os lados. As mesmas etapas, na mesma ordem,
            são remontadas aqui como uma lista vertical.
        */
        $passos_html = '';

        if ($processo_id > 0 && !empty($etapas_ids))
        {
            $ids_passos = implode(',', array_map('intval', $etapas_ids));

            $etapas_passos = TTransaction::get()->query("
                SELECT id, etapa_nome, cor
                FROM publicacao_etapa
                WHERE id IN ({$ids_passos})
                ORDER BY
                    ordem_prioridade ASC,
                    id ASC
            ")->fetchAll(PDO::FETCH_OBJ);

            $passou_etapa_atual = false;

            foreach ($etapas_passos as $etapa_passo)
            {
                $eh_etapa_atual = !empty($etapa_atual_id)
                    && (int) $etapa_passo->id === (int) $etapa_atual_id;

                if ($passou_etapa_atual)
                {
                    $estado_passo = 'futura';
                }
                elseif ($eh_etapa_atual)
                {
                    $estado_passo = 'atual';
                    $passou_etapa_atual = true;
                }
                else
                {
                    $estado_passo = 'concluida';
                }

                $aria_passo = ($estado_passo === 'atual') ? " aria-current='step'" : '';

                $nome_passo_html = htmlspecialchars(
                    (string) $etapa_passo->etapa_nome,
                    ENT_QUOTES,
                    'UTF-8'
                );

                /*
                    A cor de cada etapa vem do cadastro, igual ao ArrowStep do
                    desktop. Etapas ainda por vir ficam neutras, tambem como la:
                    a cor marca o que ja foi percorrido.
                */
                $cor_passo = trim((string) ($etapa_passo->cor ?? ''));

                $estilo_passo = '';

                if ($estado_passo !== 'futura' && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $cor_passo))
                {
                    $cor_passo_html = htmlspecialchars($cor_passo, ENT_QUOTES, 'UTF-8');

                    $estilo_passo = " style='--passo-cor:{$cor_passo_html}'";
                }

                $passos_html .= "
                    <li class='curciol-passo curciol-passo-{$estado_passo}'{$aria_passo}{$estilo_passo}>
                        <span class='curciol-passo-marca'></span>
                        <span class='curciol-passo-nome'>{$nome_passo_html}</span>
                    </li>
                ";
            }
        }

        if (!empty($passos_html))
        {
            $lista_passos = new TElement('ol');
            $lista_passos->class = 'curciol-passos';
            $lista_passos->add($passos_html);

            $row1b = $this->form->addContent([$lista_passos]);
            $row1b->layout = [' col-sm-12'];

            $row1b->class = trim(($row1b->class ?? '') . ' curciol-passos-row');

            $row1b->style = 'display:none; margin-left:0; margin-right:0;';
        }

        $row2 = $this->form->addFields([$processo_view]);
        $row2->layout = [' col-sm-12'];

        $row1->class = trim(($row1->class ?? '') . ' curciol-arrowstep-row');

        $row2->class = trim(($row2->class ?? '') . ' curciol-timeline-mobile');

        /*

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Processos"]));
        }
        $container->add($this->form);

        */
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container curciol-portal curciol-processos-interno-page';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Processos"]));
        }
        $container->add($this->form);

        /*
            A apresentação desta tela vive em app/lib/include/css/curciol-portal.css.
        */

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

    public static function buscarEtapaMaisAvancadaDoProcesso($processo_id)
    {
        try
        {
            TTransaction::open('escritorio');

            $conn = TTransaction::get();

            $sql = "
                SELECT pe.id
                FROM processo_publicacoes pp

                LEFT JOIN publicacao pub
                    ON pub.id = pp.publicacao_id

                LEFT JOIN andamento andam
                    ON andam.id = pp.andamento_id

                INNER JOIN publicacao_etapa pe
                    ON pe.id = COALESCE(
                        pp.publicacao_etapa_id,
                        pub.publicacao_etapa_id,
                        andam.publicacao_etapa_id
                    )

                    INNER JOIN processo proc
                        ON proc.id = pp.processo_id

                WHERE pp.processo_id = :processo_id

                AND COALESCE(
                    pp.publicacao_etapa_id,
                    pub.publicacao_etapa_id,
                    andam.publicacao_etapa_id
                ) IS NOT NULL

                AND pe.id NOT IN (1, 10)
                AND (
                    (
                        proc.tipo_processo_id = 1
                        AND COALESCE(UPPER(TRIM(pe.judicial)), 'N') = 'S'
                    )
                    OR
                    (
                        proc.tipo_processo_id = 2
                        AND COALESCE(UPPER(TRIM(pe.extrajudicial)), 'N') = 'S'
                    )
                    OR
                    (
                        proc.tipo_processo_id NOT IN (1, 2)
                    )
                )

                AND (
                    (
                        pp.publicacao_id IS NOT NULL
                        AND pp.andamento_id IS NULL
                        AND COALESCE(UPPER(TRIM(pub.etapa_verificada)), 'N') = 'S'
                    )
                    OR
                    (
                        pp.andamento_id IS NOT NULL
                        AND pp.publicacao_id IS NULL
                        AND COALESCE(UPPER(TRIM(andam.etapa_verificada)), 'N') = 'S'
                    )
                )

                ORDER BY pe.ordem_prioridade DESC, pe.id DESC
                LIMIT 1
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':processo_id' => $processo_id]);

            $id = $stmt->fetchColumn();

            TTransaction::close();

            return $id ?: null;
        }
        catch (Exception $e)
        {
            if (TTransaction::get())
            {
                TTransaction::rollback();
            }

            throw $e;
        }
    }

}

