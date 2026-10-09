<?php

/**
 * Visualizacao de um cliente na "Integracao Contatos APChat": os dados no
 * Curciol, o que e enviado ao APChat, o contato como esta hoje no APChat
 * (consulta ao vivo, so leitura) e o historico de sincronizacoes.
 */
class ApchatContatoFormView extends TWindow
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $formName = 'formView_ApchatContato';

    public function __construct($param)
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::setTitle("Contato APChat");
        parent::setProperty('class', 'window_modal');
        parent::setDialogClass('curciol-janela-rolavel');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');
        $this->form->setFormTitle("");

        try
        {
            TTransaction::open(self::$database);

            $pessoa = new Pessoa((int) ($param['key'] ?? 0));
            $enviado = APChatContactService::montarContato($pessoa);
            $autorizado = strtoupper(trim((string) $pessoa->aceita_receber_mensagen_whatsapp)) === 'T';

            $logs = ApchatContatoLog::where('pessoa_id', '=', (int) $pessoa->id)
                                    ->orderBy('id', 'desc')
                                    ->take(10)
                                    ->load();

            TTransaction::close();

            // --- Curciol
            $this->form->addContent([$this->titulo('No Curciol')]);

            $row = $this->form->addFields(
                [new TLabel("Nome:", null, '12px', 'B', '100%'), new TTextDisplay($pessoa->nome, '', '12px', '')],
                [new TLabel("CPF ou CNPJ:", null, '12px', 'B', '100%'), new TTextDisplay($this->documento($pessoa->cpf_cnpj), '', '12px', '')],
                [new TLabel("Autorizou WhatsApp:", null, '12px', 'B', '100%'), new TTextDisplay($autorizado ? 'Sim' : 'Não', '', '12px', '')]
            );
            $row->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];

            $row = $this->form->addFields(
                [new TLabel("Telefone:", null, '12px', 'B', '100%'), new TTextDisplay($pessoa->telefone ?: '-', '', '12px', '')],
                [new TLabel("Número enviado:", null, '12px', 'B', '100%'), new TTextDisplay($enviado['number'] ?? 'inválido - não é enviado', '', '12px', '')],
                [new TLabel("E-mail:", null, '12px', 'B', '100%'), new TTextDisplay($pessoa->email ?: '-', '', '12px', '')]
            );
            $row->layout = ['col-sm-3', 'col-sm-3', 'col-sm-6'];

            // --- Enviado
            $this->form->addContent([$this->titulo('Enviado ao APChat')]);
            $this->form->addContent([$this->tabela(['Campo', 'Valor'], array_map(function ($campo, $valor) {
                return [$campo, $valor];
            }, array_keys($enviado), $enviado))]);

            // --- APChat agora
            $this->form->addContent([$this->titulo('No APChat agora')]);

            $consulta = APChatContactService::consultarPessoa($pessoa->id);

            if ($consulta->situacao === 'ENCONTRADO')
            {
                $c = $consulta->contato;
                $linhas = [];

                foreach (['id' => 'Id no APChat', 'number' => 'Número', 'name' => 'Nome', 'email' => 'E-mail', 'cpf' => 'CPF', 'firstName' => 'Primeiro nome', 'lastName' => 'Sobrenome', 'businessName' => 'Empresa', 'birthdayDate' => 'Nascimento', 'updatedAt' => 'Atualizado em'] as $campo => $rotulo)
                {
                    $linhas[] = [$rotulo, (string) ($c[$campo] ?? '')];
                }

                $this->form->addContent([$this->tabela(['Campo', 'Valor'], $linhas)]);
            }
            else
            {
                $textos = [
                    'NAO_ENCONTRADO' => 'Este contato ainda não existe no APChat.',
                ];

                $this->form->addContent([$this->aviso($textos[$consulta->situacao] ?? $consulta->mensagem)]);
            }

            // --- Historico
            $this->form->addContent([$this->titulo('Últimas sincronizações')]);

            $linhas = [];

            foreach ($logs as $log)
            {
                $linhas[] = [
                    TDateTime::convertToMask($log->data_criacao, 'yyyy-mm-dd hh:ii:ss', 'dd/mm/yyyy hh:ii'),
                    $log->origem === ApchatContatoLog::ORIGEM_LOTE ? 'Sincronizar todos' : 'Cadastro do cliente',
                    $log->operacao,
                    ['html' => ApchatContatoList::seloSituacao($log->situacao)],
                    $log->numero,
                    $log->mensagem,
                ];
            }

            $this->form->addContent([empty($linhas)
                ? $this->aviso('Nenhuma sincronização registrada.')
                : $this->tabela(['Data', 'Origem', 'Operação', 'Situação', 'Número', 'Detalhe'], $linhas)]);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }

        parent::add($this->form);
    }

    public function onShow($param = null)
    {
    }

    private function titulo($texto)
    {
        $el = new TElement('h5');
        $el->style = 'margin:14px 0 6px 0; font-weight:600;';
        $el->add(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));

        return $el;
    }

    private function aviso($texto)
    {
        $el = new TElement('div');
        $el->class = 'alert alert-secondary';
        $el->style = 'margin:0; padding:8px 12px; font-size:12px;';
        $el->add(htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8'));

        return $el;
    }

    /**
     * @param string[] $cabecalho
     * @param array[]  $linhas celula texto, ou ['html' => ...] ja escapado
     */
    private function tabela(array $cabecalho, array $linhas)
    {
        $html = "<table class='table table-sm table-bordered' style='font-size:12px; margin:0;'><thead><tr>";

        foreach ($cabecalho as $titulo)
        {
            $html .= '<th>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($linhas as $linha)
        {
            $html .= '<tr>';

            foreach ($linha as $celula)
            {
                $html .= '<td>' . (is_array($celula) ? $celula['html'] : htmlspecialchars((string) $celula, ENT_QUOTES, 'UTF-8')) . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $el = new TElement('div');
        $el->class = 'table-responsive';
        $el->add($html);

        return $el;
    }

    private function documento($valor)
    {
        $valor = preg_replace('/\D/', '', (string) $valor);

        if (strlen($valor) === 11)
        {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $valor);
        }

        if (strlen($valor) === 14)
        {
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $valor);
        }

        return $valor !== '' ? $valor : '-';
    }
}
