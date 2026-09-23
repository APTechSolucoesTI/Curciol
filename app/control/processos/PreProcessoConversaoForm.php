<?php

/**
 * Confirmacao da conversao de um pre-processo em processo definitivo.
 *
 * Fica entre a escolha e a gravacao de proposito: a conversao grava o
 * numero judicial no registro e vale a pena o usuario ver, lado a lado, o
 * que a publicacao traz e o que o pre-processo ja tem antes de confirmar.
 *
 * Os dois ids chegam como parametro e sao revalidados aqui. Nada vem de
 * sessao.
 *
 * E TWindow, e nao TPage, para continuar a mesma conversa visual da janela
 * de pesquisa que veio antes: o usuario escolhe o pre-processo num modal e
 * confirma no mesmo formato, sem a tela de fundo trocar por baixo dele.
 */
class PreProcessoConversaoForm extends TWindow
{
    private static $database = 'escritorio';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.6, null);
        parent::setTitle('Vincular pré-processo');
        parent::setProperty('class', 'window_modal');

        /*
            A janela de pesquisa ja cumpriu o papel dela. Fecha-la evita dois
            modais empilhados e deixa claro que a escolha foi feita.
        */
        TWindow::closeWindowByName('PreProcessoSeekWindow');

        $processo_id   = (int) ($param['processo_id'] ?? 0);
        $publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $nivel = (($param['nivel'] ?? '') === PreProcessoService::NIVEL_PRINCIPAL)
            ? PreProcessoService::NIVEL_PRINCIPAL
            : PreProcessoService::NIVEL_PROCESSO;

        try
        {
            TTransaction::open(self::$database);

            $processo   = Processo::find($processo_id);
            $publicacao = Publicacao::find($publicacao_id);

            if (!$processo || !$publicacao)
            {
                throw new Exception('Pré-processo ou publicação não encontrados. Reabra a publicação e tente novamente.');
            }

            if (!PreProcessoService::ehPreProcesso($processo))
            {
                throw new Exception("O processo #{$processo->id} não é mais um pré-processo pendente. Nada foi alterado.");
            }

            $numero = PreProcessoService::numeroDaPublicacao($publicacao, $nivel);

            $clientes = PreProcessoService::clientesDoProcesso($processo->id);

            /*
                A tela e montada como BootstrapFormBuilder, e nao como um
                TPanelGroup solto, porque no Adianti todo TButton precisa
                pertencer a um TForm - solto ele levanta
                "Voce deve passar o TButton como parametro para
                TForm::setFields()" na hora de renderizar.
            */
            $form = new BootstrapFormBuilder('form_PreProcessoConversao');
            $form->setFormTitle('Confirmar vínculo e conversão');

            $html = new TElement('div');
            $html->style = 'padding:12px 4px;';

            $html->add($this->bloco('Publicação', [
                'Número da publicação'   => $publicacao->numero_publicacao,
                'Disponibilizada em'     => $publicacao->data_disponibilizacao ? date('d/m/Y', strtotime($publicacao->data_disponibilizacao)) : '-',
                'Número que será usado'  => $numero ?: '(a publicação não traz número)',
                'Título'                 => $publicacao->titulo,
            ]));

            $html->add($this->bloco('Pré-processo', [
                'Id interno'  => '#' . $processo->id,
                'Descrição'   => $processo->descricao_pre_processo,
                'Tipo'        => $processo->tipo_processo->nome ?? '-',
                'Área'        => $processo->area->nome ?? '-',
                'Assunto'     => $processo->assunto->nome ?? '-',
                'Responsável' => $processo->responsavel->nome ?? '-',
                'Cliente(s)'  => empty($clientes) ? '-' : implode(', ', $clientes),
            ]));

            $aviso = new TElement('p');
            $aviso->style = 'margin-top:14px;';
            $aviso->add(
                'O processo continua sendo o registro <b>#' . $processo->id . '</b>. '
                . 'Contratos, clientes, contrapartes, documentos e o histórico já lançado permanecem como estão; '
                . 'o que muda é o número, que passa a existir.'
            );
            $html->add($aviso);

            $linha = $form->addContent([$html]);
            $linha->layout = [' col-sm-12'];

            $btn_confirmar = $form->addAction(
                'Confirmar e converter',
                new TAction(
                    ['PreProcessoConversaoForm', 'onConfirmar'],
                    ['processo_id' => $processo->id, 'publicacao_id' => $publicacao->id, 'nivel' => $nivel, 'static' => 1]
                ),
                'fas:check #ffffff'
            );
            $btn_confirmar->addStyleClass('btn-primary');

            /*
                Cancelar apenas fecha o modal: a publicacao continua aberta
                atras dele, exatamente como o usuario a deixou.
            */
            $form->addAction(
                'Cancelar',
                new TAction(['PreProcessoConversaoForm', 'onCancelar'], ['static' => 1]),
                'fas:times #dd5a43'
            );

            /*
                Sem numero na publicacao nao ha conversao possivel. O botao
                fica desabilitado e o motivo aparece no proprio quadro acima,
                em "Número que será usado".
            */
            if (empty($numero))
            {
                $btn_confirmar->setProperty('disabled', 'disabled');
            }

            TTransaction::close();

            parent::add($form);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function bloco($titulo, array $campos): TElement
    {
        $bloco = new TElement('div');
        $bloco->style = 'margin-bottom:14px;';

        $h = new TElement('h5');
        $h->style = 'margin:0 0 6px 0; font-weight:600;';
        $h->add($titulo);
        $bloco->add($h);

        $tabela = new TElement('table');
        $tabela->class = 'table table-condensed';
        $tabela->style = 'margin-bottom:0;';

        foreach ($campos as $rotulo => $valor)
        {
            $valor = trim((string) $valor);
            $valor = ($valor === '') ? '-' : $valor;

            $tr = new TElement('tr');

            $td_rotulo = new TElement('td');
            $td_rotulo->style = 'width:220px; color:#6b7280;';
            $td_rotulo->add($rotulo);

            $td_valor = new TElement('td');
            $td_valor->add(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'));

            $tr->add($td_rotulo);
            $tr->add($td_valor);
            $tabela->add($tr);
        }

        $bloco->add($tabela);

        return $bloco;
    }

    /**
     * Executa a conversao.
     *
     * Tudo em uma transacao: numero, estado, publicacao e vinculos vao
     * juntos. Se qualquer verificacao falhar - numero ja usado por outro
     * processo, publicacao sem numero, registro que deixou de ser pendente -
     * nada e gravado e o usuario ve o motivo.
     */
    public static function onConfirmar($param = null)
    {
        $processo_id   = (int) ($param['processo_id'] ?? 0);
        $publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $nivel         = $param['nivel'] ?? PreProcessoService::NIVEL_PROCESSO;

        try
        {
            TTransaction::open(self::$database);

            $processo = PreProcessoService::converter($processo_id, $publicacao_id, $nivel, TSession::getValue('userid'));

            TTransaction::close();

            /* Fecha pelo nome: nao depende de qual modal esta por cima. */
            TWindow::closeWindowByName('PreProcessoSeekWindow');
            TWindow::closeWindowByName('PreProcessoConversaoForm');

            TToast::show(
                'success',
                "Pré-processo #{$processo->id} convertido. Número {$processo->numero_cnj_numero}.",
                'topRight',
                'far:check-circle'
            );

            TApplication::loadPage('PublicacaoHeaderList', 'onShow');
            TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $publicacao_id]);

            /*
                A classificacao de etapas roda depois e fora da transacao da
                conversao: e uma rotina de manutencao, e uma falha nela nao
                pode desfazer um vinculo que ja esta correto.
            */
            APIPublicacaoController::onVerificaPublicacaoEtapa();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Fecha o modal sem alterar nada.
     */
    public static function onCancelar($param = null)
    {
        TWindow::closeWindowByName('PreProcessoConversaoForm');
    }

    public function onShow($param = null)
    {
    }
}
