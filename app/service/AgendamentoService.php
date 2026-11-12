<?php

class AgendamentoService
{
    const KEY = 'Y2hhdmUgYWdlbmRhbWVudG8gYnVpbGRlciBjbGluaWNh';
    const CIPHER = 'AES-128-CBC';
    const PREFIXO = 'v2-';

    /**
     * Curciol (seguranca 22/10/2026): o token antigo era cifrado com chave fixa no codigo (forjavel) e passava por
     * unserialize. O novo e "v2-<id>-<assinatura>", assinado com o seed do sistema. Links antigos ja enviados aos
     * clientes continuam abrindo enquanto o agendamento nao passou e se ele ja existia quando o formato mudou.
     */
    public static function decode($token)
    {
        $token = (string) $token;
        if (strpos($token, self::PREFIXO) === 0)
        {
            $partes = explode('-', substr($token, strlen(self::PREFIXO)));
            if (count($partes) == 2 AND ctype_digit($partes[0]) AND hash_equals(self::assinatura($partes[0]), $partes[1]))
            {
                $obj = new stdClass;
                $obj->agendamento_id = (int) $partes[0];
                return $obj;
            }
            return null;
        }

        $secretHash = self::KEY;
        $password = self::KEY;
        $encryptionMethod = self::CIPHER;
        $length = openssl_cipher_iv_length($encryptionMethod);
        $iv = substr(md5($password), 0, $length);

        $obj = @unserialize(base64_decode((string) openssl_decrypt(base64_decode($token), $encryptionMethod, $secretHash, null, $iv)), ['allowed_classes' => ['stdClass']]);
        if (!$obj instanceof stdClass OR empty($obj->agendamento_id) OR !self::legadoValido((int) $obj->agendamento_id))
        {
            return null;
        }
        return $obj;
    }

    public static function getToken($id)
    {
        return self::PREFIXO . (int) $id . '-' . self::assinatura((int) $id);
    }

    private static function assinatura($id)
    {
        $ini = AdiantiApplicationConfig::get();
        $chave = APPLICATION_NAME . ($ini['general']['seed'] ?? '') . 'agendamento';
        return substr(hash_hmac('sha256', 'agendamento:' . (int) $id, $chave), 0, 32);
    }

    /**
     * Atendimento do agendamento confirmado, com os procedimentos do agendamento (o "Iniciar atendimento" precisa dele).
     * Usado pelo Confirmar da equipe e pela confirmacao do cliente pelo WhatsApp (03/11/2026). Reconfirmar depois de
     * "Cancelar confirmacao" reaproveita o atendimento que ficou (tinha documento, anexo etc.): nao duplica.
     */
    public static function criarAtendimento(Agendamento $agendamento): Atendimento
    {
        $existente = Atendimento::where('agendamento_id', '=', $agendamento->id)->first();
        if ($existente)
        {
            return $existente;
        }

        $atendimento = new Atendimento;
        $atendimento->agendamento_id = $agendamento->id;
        $atendimento->cliente_id = $agendamento->cliente_id;
        $atendimento->profissional_id = $agendamento->get_agenda()->profissional_id;
        $atendimento->tipo_atendimento_id = TipoAtendimento::AGENDADO;
        $atendimento->criacao_user_id = TSession::getValue('userid');
        $atendimento->store();

        $total = 0;
        foreach ($agendamento->getAgendamentoProcedimentos() ?: [] as $agendamentoProcedimento)
        {
            $atendimentoProcedimento = new AtendimentoProcedimento;
            $atendimentoProcedimento->atendimento_id = $atendimento->id;
            $atendimentoProcedimento->quantidade = $agendamentoProcedimento->quantidade;
            $atendimentoProcedimento->valor = $agendamentoProcedimento->valor;
            $atendimentoProcedimento->valor_total = $agendamentoProcedimento->valor_total;
            $atendimentoProcedimento->procedimento_id = $agendamentoProcedimento->procedimento_id;
            $atendimentoProcedimento->parceiro_id = $agendamentoProcedimento->parceiro_id;
            $atendimentoProcedimento->store();

            $total += $atendimentoProcedimento->valor;
        }

        $atendimento->valor_total = $total;
        $atendimento->modificacao_user_id = TSession::getValue('userid');
        $atendimento->store();

        return $atendimento;
    }

    /**
     * O cliente respondeu (WhatsApp ou link do e-mail): muda o estado e registra no historico, sem usuario ("Automatico").
     * Confirmando, cria o atendimento, como o Confirmar da equipe.
     */
    public static function respostaDoCliente(Agendamento $agendamento, int $estado, string $evento, string $observacao): void
    {
        $agendamento->estado_agenda_id = $estado;
        $agendamento->store();

        $historico = new EstadoAgendamento();
        $historico->agendamento_id = $agendamento->id;
        $historico->estado_agenda_id = $estado;
        $historico->atribuido_em = date('Y-m-d H:i:s');
        $historico->evento = $evento;
        $historico->observacao = $observacao;
        $historico->store();

        if ($estado === (int) EstadoAgenda::CONFIRMADO)
        {
            self::criarAtendimento($agendamento);
        }
    }

    /**
     * Link do e-mail (04/11/2026): abrir so mostra o botao daquele link, porque antivirus de e-mail abrem os links sozinhos; quem
     * muda o agendamento e o clique. Vale so com o agendamento em aberto e antes do horario. Devolve o aviso de quando o link nao
     * pode mais responder, ['tipo' => classe do alerta, 'texto' => frase], ou null se pode.
     */
    public static function avisoDoLink(Agendamento $agendamento, int $estado): ?array
    {
        $atual = (int) $agendamento->estado_agenda_id;

        if ($atual === (int) EstadoAgenda::CONFIRMADO)
        {
            return ['tipo' => 'success', 'texto' => 'Este agendamento já foi confirmado.'];
        }
        if ($atual === (int) EstadoAgenda::CANCELADO)
        {
            return ['tipo' => 'danger', 'texto' => 'Este agendamento já foi cancelado.'];
        }
        if (!in_array($atual, [(int) EstadoAgenda::AGENDADO, (int) EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO], true))
        {
            return ['tipo' => 'warning', 'texto' => 'Este agendamento está ' . $agendamento->estado_agenda->nome . '. Nada foi alterado.'];
        }
        if ((string) $agendamento->dt_inicial <= date('Y-m-d H:i:s'))
        {
            return ['tipo' => 'warning', 'texto' => 'O horário deste agendamento já passou. Nada foi alterado.'];
        }
        if ($estado === (int) EstadoAgenda::CANCELADO && AgendamentoFinanceiroService::ficha((int) $agendamento->id))
        {
            return ['tipo' => 'warning', 'texto' => 'Não foi possível cancelar este agendamento pelo link. Nada foi alterado.'];
        }

        return null;
    }

    /** O clique no botao do link: confirma ou cancela, conforme o link, e devolve o aviso da pagina. */
    public static function respostaPeloLink(Agendamento $agendamento, int $estado): array
    {
        if ($aviso = self::avisoDoLink($agendamento, $estado))
        {
            return $aviso;
        }

        if ($estado === (int) EstadoAgenda::CONFIRMADO)
        {
            self::respostaDoCliente($agendamento, $estado, EstadoAgendamento::EVENTO_CONFIRMADO_EMAIL, 'Cliente confirmou pelo link do e-mail');
            return ['tipo' => 'success', 'texto' => 'Agendamento confirmado.'];
        }

        self::respostaDoCliente($agendamento, $estado, EstadoAgendamento::EVENTO_CANCELADO_EMAIL, 'Cliente cancelou pelo link do e-mail');
        return ['tipo' => 'danger', 'texto' => 'Agendamento cancelado.'];
    }

    /**
     * "Cancelar confirmacao" (04/11/2026): o agendamento Confirmado volta a Agendado, para remarcar ou cancelar depois, e a
     * observacao fica no historico. O atendimento criado na confirmacao sai, a nao ser que ja tenha algo ligado a ele.
     */
    public static function cancelarConfirmacao(Agendamento $agendamento, string $observacao): void
    {
        $observacao = trim($observacao);

        if ((int) $agendamento->estado_agenda_id !== (int) EstadoAgenda::CONFIRMADO)
        {
            throw new Exception('Só é possível cancelar a confirmação de um agendamento confirmado.');
        }
        if ($observacao === '')
        {
            throw new Exception('Escreva a observação.');
        }

        $atendimento = Atendimento::where('agendamento_id', '=', $agendamento->id)->first();

        if ($atendimento && !empty($atendimento->dt_inicio))
        {
            throw new Exception('O atendimento deste agendamento já foi iniciado.');
        }

        $agendamento->estado_agenda_id = EstadoAgenda::AGENDADO;
        $agendamento->store();

        $historico = new EstadoAgendamento();
        $historico->agendamento_id = $agendamento->id;
        $historico->estado_agenda_id = $agendamento->estado_agenda_id;
        $historico->system_users_id = TSession::getValue('userid');
        $historico->atribuido_em = date('Y-m-d H:i:s');
        $historico->observacao = $observacao;
        $historico->evento = EstadoAgendamento::EVENTO_CONFIRMACAO_CANCELADA;
        $historico->store();

        MensagemAgendadaService::cancelarPendentes((int) $agendamento->id, 'Confirmação cancelada');

        if ($atendimento && !self::atendimentoTemVinculo((int) $atendimento->id))
        {
            AtendimentoProcedimento::where('atendimento_id', '=', $atendimento->id)->delete();
            $atendimento->delete();
        }
    }

    /** Alguma tabela (fora os procedimentos copiados do agendamento) aponta para este atendimento? */
    /**
     * Falta (06/11/2026): so um agendamento Confirmado pode ser marcado como Nao compareceu, na tela e no servidor. Os lembretes
     * pendentes sao cancelados e o atendimento criado na confirmacao sai, a nao ser que ja tenha algo ligado. Usa a transacao aberta.
     */
    public static function marcarNaoCompareceu(?Agendamento $agendamento): void
    {
        if (!$agendamento || (int) $agendamento->estado_agenda_id !== (int) EstadoAgenda::CONFIRMADO)
        {
            throw new Exception('Só um agendamento confirmado pode ser marcado como Não compareceu.');
        }

        $atendimento = Atendimento::where('agendamento_id', '=', $agendamento->id)->first();

        if ($atendimento && !empty($atendimento->dt_inicio))
        {
            throw new Exception('O atendimento deste agendamento já foi iniciado.');
        }

        $agendamento->estado_agenda_id = EstadoAgenda::NAO_COMPARECEU;
        $agendamento->store();

        $historico = new EstadoAgendamento();
        $historico->agendamento_id = $agendamento->id;
        $historico->estado_agenda_id = $agendamento->estado_agenda_id;
        $historico->system_users_id = TSession::getValue('userid');
        $historico->atribuido_em = date('Y-m-d H:i:s');
        $historico->store();

        MensagemAgendadaService::cancelarPendentes((int) $agendamento->id, 'O cliente não compareceu');

        if ($atendimento && !self::atendimentoTemVinculo((int) $atendimento->id))
        {
            AtendimentoProcedimento::where('atendimento_id', '=', $atendimento->id)->delete();
            $atendimento->delete();
        }
    }

    private static function atendimentoTemVinculo(int $atendimentoId): bool
    {
        $c = TTransaction::get();
        $tabelas = $c->query("SELECT c.conrelid::regclass::text AS tabela, a.attname AS coluna
                                FROM pg_constraint c
                                JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = c.conkey[1]
                               WHERE c.contype = 'f' AND c.confrelid = 'atendimento'::regclass
                                 AND c.conrelid <> 'atendimento_procedimento'::regclass")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tabelas as $t)
        {
            $st = $c->prepare("SELECT 1 FROM {$t['tabela']} WHERE \"{$t['coluna']}\" = ? LIMIT 1");
            $st->execute([$atendimentoId]);
            if ($st->fetchColumn()) { return true; }
        }
        return false;
    }

    /** "a, b e c". */
    public static function camposPorExtenso(array $campos): string
    {
        $ultimo = array_pop($campos);

        return $campos ? implode(', ', $campos) . ' e ' . $ultimo : (string) $ultimo;
    }

    /**
     * Campos essenciais que faltam no cadastro do cliente: sem eles o agendamento confirma, mas o atendimento nao inicia
     * (o atendimento avulso, sem agendamento, nao exige nada). Regra de 12/11/2026, na ordem do formulario:
     * PF = nome, data de nascimento, CPF, RG/CIN, orgao emissor, nacionalidade, sexo, estado civil, endereco e contato;
     * PJ = nome, CNPJ, data de abertura, representante legal, endereco e contato.
     * Endereco: qualquer um, principal ou nao. Contato: um telefone (com algum digito diferente de zero) ou um e-mail, nos Dados
     * ou em qualquer contato, principal ou nao. O formato de CPF e RG/CIN e conferido ao salvar, nao aqui.
     */
    public static function pendenciasCadastro(int $clienteId): array
    {
        return self::pendenciasCadastroDe([$clienteId])[$clienteId] ?? [];
    }

    /** O mesmo para varios clientes numa consulta so (agenda): [id => [campos]], so quem tem pendencia. */
    public static function pendenciasCadastroDe(array $clienteIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $clienteIds))));
        if (!$ids) { return []; }

        $linhas = TTransaction::get()->query("SELECT p.id, p.tipo_pessoa_id, NULLIF(trim(p.nome), '') AS nome, NULLIF(trim(p.cpf_cnpj), '') AS cpf_cnpj,
                                                     NULLIF(trim(p.rg_ie), '') AS rg_ie, NULLIF(trim(p.orgao_emissor), '') AS orgao_emissor, p.dt_nascimento_abertura,
                                                     p.nacionalidade_id, p.sexo_id, p.estado_civil_id,
                                                     (EXISTS (SELECT 1 FROM pessoa_endereco e WHERE e.pessoa_id = p.id))::int AS tem_endereco,
                                                     (COALESCE(p.telefone, '') ~ '[1-9]' OR COALESCE(p.email, '') LIKE '%_@_%'
                                                      OR EXISTS (SELECT 1 FROM pessoa_contato c WHERE c.pessoa_id = p.id
                                                                    AND (COALESCE(c.telefone, '') ~ '[1-9]' OR COALESCE(c.email, '') LIKE '%_@_%')))::int AS tem_contato,
                                                     (EXISTS (SELECT 1 FROM pessoa_representantes_legais r WHERE r.pessoa_juridica_id = p.id))::int AS tem_representante
                                                FROM pessoa p WHERE p.id IN (" . implode(',', $ids) . ") ORDER BY p.id")->fetchAll(PDO::FETCH_ASSOC);

        $pendencias = [];
        foreach ($linhas as $p)
        {
            $campos = ['nome' => 'nome'];

            if ((string) $p['tipo_pessoa_id'] === (string) TipoPessoa::FISICA)
            {
                $campos += ['dt_nascimento_abertura' => 'data de nascimento', 'cpf_cnpj' => 'CPF', 'rg_ie' => 'RG/CIN', 'orgao_emissor' => 'órgão emissor do RG/CIN',
                            'nacionalidade_id' => 'nacionalidade', 'sexo_id' => 'sexo', 'estado_civil_id' => 'estado civil'];
            }
            elseif ((string) $p['tipo_pessoa_id'] === (string) TipoPessoa::JURIDICA)
            {
                $campos += ['cpf_cnpj' => 'CNPJ', 'dt_nascimento_abertura' => 'data de abertura', 'tem_representante' => 'representante legal'];
            }
            $campos += ['tem_endereco' => 'endereço', 'tem_contato' => 'contato (telefone ou e-mail)'];

            $faltam = [];
            foreach ($campos as $coluna => $rotulo)
            {
                if (empty($p[$coluna])) { $faltam[] = $rotulo; }
            }
            if ($faltam) { $pendencias[(int) $p['id']] = $faltam; }
        }

        return $pendencias;
    }

    /** Token antigo: so de agendamento que ja existia na troca do formato e que ainda nao passou. */
    private static function legadoValido($id)
    {
        TTransaction::open('permission');
        $preferencia = SystemPreference::find('agendamento_token_legado_ate');
        TTransaction::close();

        TTransaction::open('escritorio');
        if (!$preferencia)
        {
            $preferencia = new stdClass;
            $preferencia->preference = (int) Agendamento::where('id', '>', 0)->maxBy('id');
            TTransaction::open('permission');
            $nova = new SystemPreference;
            $nova->id = 'agendamento_token_legado_ate';
            $nova->preference = (string) $preferencia->preference;
            $nova->store();
            TTransaction::close();
        }
        $agendamento = Agendamento::find($id);
        TTransaction::close();

        return $agendamento
            AND $id <= (int) $preferencia->preference
            AND strtotime((string) $agendamento->dt_inicial) >= strtotime('-1 day');
    }
}
