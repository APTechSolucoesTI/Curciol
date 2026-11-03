<?php

class MensagemService
{
    public static function enviarEmailCredenciais($pessoa, $escritorio)
    {
        //Recebe o template cadastrado no banco de dados
        //parametros (qual o template que precisa, qual escritorio vai mandar)
        $template = TemplateEscritorio::getTemplate('EMAIL_CREDENCIAIS', $escritorio->id);

        if ($template && $template->habilitado == 'T' && $pessoa->email)
        {
            //busca as configurações SMTP da escritorio, necessarias para enviar o email
            $config = EmailConfig::where('escritorio_id', '=', $escritorio->id)->first();
        
            if (empty($config))
            {
                throw new Exception('E-mail não configurado! Veja o cadastro da clínica');
            }
 
            //prepara o objeto ($cliente) para receber os parametros de envio de MensagemService
            //parametro config passado para receber os dados do smtp da escritorio
            $cliente = new EmailClient($config);
            
            //troca o title no template, parametros: (1, 2, 3)
            $title = TemplateEscritorio::replace($pessoa, $template->titulo, $escritorio);
            //troca as variaveis do template por dados para a mensagem, parametros: (1, 2, 3)
            $message = TemplateEscritorio::replace($pessoa, $template->template, $escritorio);
            //PARAMETROS REPLACE
            //1 - $pessoa: objeto que contem os dados da pessoa cadatrada
            //2 - $template->titulo : variavel que contem o texto do titulo (buscado no banco de dados)
            //3 - $escritorio: objeto que contem os dados da escritorio

            //Envia o email de acordo com os dados smtp armazenados em $cliente
            //PARAMETROS:
            //1: destinatário
            //2: titulo
            //3: mensagem
            $cliente->send($pessoa->email, $title, $message);
        }
    }
    
    public static function canEnviarWhatsappLembreteConsulta($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::CONFIRMADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('WHATSAPP_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            return true;
        }
        
        
        return false;
    }
    
    public static function canEnviarWhatsappConfirmacao($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::AGENDADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('WHATSAPP_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            return true;
        }
        
        
        return false;
    }
    
    public static function canEnviarEmailConfirmacao($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::AGENDADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('EMAIL_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            return true;
        }
        
        return false;
    }
    
    public static function canEnviarEmailLembreteConsulta($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::CONFIRMADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('EMAIL_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            return true;
        }
        
        return false;
    }
    
    // =================================================================
    // Envio pela tela "Enviar mensagem" (03/11/2026)
    // =================================================================
    //
    // Todo envio do agendamento passa pela cortina EnviarMensagemForm: o template (botoes de confirmacao e lembrete)
    // ou o texto escrito na hora (envio manual), com as variaveis trocadas no envio. WhatsApp pelo APChat, e-mail pelo
    // SMTP do escritorio.
    //
    // Fora da producao (AmbienteService) o destino e obrigatorio e nunca pode ser do cliente do agendamento: a
    // homologacao tem copia dos dados reais e nenhuma mensagem pode chegar a um cliente de verdade.

    const MSG_TESTE_SEM_DESTINO = 'Ambiente de teste: digite %s que vai receber a mensagem (o do cliente não é permitido).';
    const MSG_TESTE_DO_CLIENTE  = 'Ambiente de teste: %s é do cliente %s. Digite um destino de teste.';

    /** Variaveis dos templates do agendamento: [variavel => descricao]. */
    public static function variaveis(): array
    {
        return [
            '{$cliente}'                 => 'Nome do cliente',
            '{$data_inicial}'            => 'Data do agendamento',
            '{$profissional}'            => 'Nome do profissional',
            '{$agenda_nome}'             => 'Nome da agenda',
            '{$estado}'                  => 'Estado atual do agendamento',
            '{$id}'                      => 'Código do agendamento',
            '{$observacao}'              => 'Observação',
            '{$escritorio}'              => 'Nome do escritório',
            '{$endereco_escritorio}'     => 'Endereço do escritório',
            '{$telefone_escritorio}'     => 'Telefone do escritório',
            '{$email_escritorio}'        => 'E-mail do escritório',
            '{$link_detalhe}'            => 'Link de detalhe do agendamento',
            '{$link_confirmacao}'        => 'Link para confirmar agendamento',
            '{$link_cancelamento}'       => 'Link para cancelar agendamento',
            '{$link_atendimento_online}' => 'Link do atendimento online',
        ];
    }

    /** Templates habilitados de uma chave no escritorio da unidade logada: [id => descricao]. */
    public static function templatesDaChave(string $chave): array
    {
        $escritorio = Escritorio::findByUnitId(TSession::getValue('userunitid'));

        if (!$escritorio)
        {
            return [];
        }

        return TemplateEscritorio::where('chave', '=', $chave)
                                 ->where('escritorio_id', '=', $escritorio->id)
                                 ->where('habilitado', '=', 'T')
                                 ->orderBy('id')
                                 ->getIndexedArray('id', 'descricao');
    }
    
    /**
     * Titulo e texto de um template, ainda com as variaveis. As acoes do template (botoes de link do envio antigo pela
     * Z-API) nao vao: o cliente responde SIM ou NAO na conversa (usuario, 03/11/2026: sem links).
     */
    public static function textoDoTemplate(TemplateEscritorio $template): array
    {
        return ['titulo' => (string) $template->titulo, 'texto' => (string) $template->template];
    }
    
    /**
     * Numero que vai receber o WhatsApp (55 + DDD + numero).
     * Producao: o digitado ou, vazio, o telefone do cliente. Teste: so o digitado, nunca um numero do cliente.
     */
    public static function destinoWhatsapp(Agendamento $agendamento, ?string $digitado): string
    {
        $digitado = trim((string) $digitado);
        $doCliente = self::numerosDoCliente((int) $agendamento->cliente_id);

        if (!AmbienteService::producao())
        {
            if ($digitado === '')
            {
                throw new Exception(sprintf(self::MSG_TESTE_SEM_DESTINO, 'o número'));
            }
        }
        elseif ($digitado === '')
        {
            $digitado = (string) $agendamento->cliente->telefone;
        }

        $numero = APChatContactService::normalizarTelefone($digitado);

        if ($numero === '')
        {
            throw new Exception('Número de WhatsApp inválido: ' . $digitado . '. Use DDD + número.');
        }

        $ehDoCliente = array_intersect(APChatContactService::variantes($numero), $doCliente) !== [];

        if ($ehDoCliente && !AmbienteService::producao())
        {
            throw new Exception(sprintf(self::MSG_TESTE_DO_CLIENTE, 'este número', $agendamento->cliente->nome));
        }

        // a autorizacao e do cliente: vale quando a mensagem vai para um numero dele
        if ($ehDoCliente && $agendamento->cliente->aceita_receber_mensagen_whatsapp != 'T')
        {
            throw new Exception('O cliente não aceita receber mensagens de WhatsApp.');
        }

        return APChatContactService::numeroCanonico($numero);
    }
    
    /**
     * E-mail que vai receber a mensagem. Producao: o digitado ou, vazio, o do cliente. Teste: so o digitado, nunca um
     * e-mail do cliente.
     */
    public static function destinoEmail(Agendamento $agendamento, ?string $digitado): string
    {
        $email = mb_strtolower(trim((string) $digitado));

        if (!AmbienteService::producao())
        {
            if ($email === '')
            {
                throw new Exception(sprintf(self::MSG_TESTE_SEM_DESTINO, 'o e-mail'));
            }

            if (in_array($email, self::emailsDoCliente((int) $agendamento->cliente_id), true))
            {
                throw new Exception(sprintf(self::MSG_TESTE_DO_CLIENTE, 'este e-mail', $agendamento->cliente->nome));
            }
        }
        elseif ($email === '')
        {
            $email = mb_strtolower(trim((string) $agendamento->cliente->email));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('E-mail inválido: ' . ($email !== '' ? $email : '(vazio)') . '.');
        }

        return $email;
    }
    
    /** Numeros do cliente (Dados e aba Contatos), cada um com e sem o nono digito. */
    public static function numerosDoCliente(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare(
            'SELECT telefone FROM pessoa WHERE id = ? UNION ALL SELECT telefone FROM pessoa_contato WHERE pessoa_id = ?');
        $st->execute([$pessoaId, $pessoaId]);
    
        $numeros = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $telefone)
        {
            $n = APChatContactService::normalizarTelefone($telefone);
            if ($n !== '') { $numeros = array_merge($numeros, APChatContactService::variantes($n)); }
        }

        return array_values(array_unique($numeros));
    }

    /** E-mails do cliente (Dados e aba Contatos), em minusculas. */
    public static function emailsDoCliente(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare(
            'SELECT email FROM pessoa WHERE id = ? UNION ALL SELECT email FROM pessoa_contato WHERE pessoa_id = ?');
        $st->execute([$pessoaId, $pessoaId]);
        
        $emails = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $email)
        {
            $e = mb_strtolower(trim((string) $email));
            if ($e !== '') { $emails[] = $e; }
        }
        
        return array_values(array_unique($emails));
    }

    /**
     * WhatsApp pelo APChat. $titulo e $texto ainda com as variaveis (trocadas aqui). Fica registrado em mensagem.
     */
    public static function enviarWhatsapp(Agendamento $agendamento, ?string $titulo, string $texto, $template_escritorio_id, ?string $digitado): Mensagem
    {
        $numero = self::destinoWhatsapp($agendamento, $digitado);
        $texto = trim(TemplateEscritorio::replace($agendamento, $texto));

        if ($texto === '')
        {
            throw new Exception('Escreva a mensagem.');
        }
        
        $mensagem = self::registrar($agendamento, TemplateEscritorio::WHATSAPP, $titulo, $texto, $template_escritorio_id, $numero);
        
        $mensagem->retorno = ApchatApiService::enviarMensagem($numero, $texto, 'curciol-mensagem-' . $mensagem->id);
        $mensagem->store();
        
        return $mensagem;
    }
        
    /**
     * E-mail pelo SMTP do escritorio (um so para o escritorio todo). $titulo e $texto ainda com as variaveis.
     */
    public static function enviarEmail(Agendamento $agendamento, ?string $titulo, string $texto, $template_escritorio_id, ?string $digitado): Mensagem
    {
        $email = self::destinoEmail($agendamento, $digitado);
        $titulo = trim(TemplateEscritorio::replace($agendamento, (string) $titulo));
        $texto = TemplateEscritorio::replace($agendamento, $texto);
        
        if ($titulo === '')
        {
            throw new Exception('Título é obrigatório para envio de e-mail.');
        }
        if (trim(strip_tags($texto)) === '')
        {
            throw new Exception('Escreva a mensagem.');
        }
                
        $config = self::emailConfig($agendamento);
        $mensagem = self::registrar($agendamento, TemplateEscritorio::EMAIL, $titulo, $texto, $template_escritorio_id, $email);
                
        (new EmailClient($config))->send($email, $titulo, $texto);
        
        return $mensagem;
    }
    
    /** SMTP do escritorio da agenda; sem ele, o primeiro configurado. */
    private static function emailConfig(Agendamento $agendamento): EmailConfig
    {
        $config = EmailConfig::where('escritorio_id', '=', (int) $agendamento->agenda->escritorio_id)->first()
               ?: EmailConfig::where('host', 'is not', null)->orderBy('id')->first();
        
        if (empty($config) || trim((string) $config->host) === '')
        {
            throw new Exception('E-mail não configurado! Veja Configurações do escritório.');
        }
        
        return $config;
    }
        
    private static function registrar(Agendamento $agendamento, string $tipo, ?string $titulo, string $texto, $template_escritorio_id, string $destino): Mensagem
    {
        $mensagem = new Mensagem();
        $mensagem->agendamento_id = $agendamento->id;
        $mensagem->template_escritorio_id = $template_escritorio_id ?: null;
        $mensagem->system_user_id = TSession::getValue('userid');
        $mensagem->titulo = $titulo;
        $mensagem->tipo_mensagem = $tipo;
        $mensagem->template = $texto;
        $mensagem->destino = $destino;
        $mensagem->store();
        
        return $mensagem;
    }
}
