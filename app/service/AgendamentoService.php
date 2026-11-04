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
        $historico->store();

        if ($atendimento && !self::atendimentoTemVinculo((int) $atendimento->id))
        {
            AtendimentoProcedimento::where('atendimento_id', '=', $atendimento->id)->delete();
            $atendimento->delete();
        }
    }

    /** Alguma tabela (fora os procedimentos copiados do agendamento) aponta para este atendimento? */
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
