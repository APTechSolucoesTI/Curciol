<?php

/**
 * Confirmacao e execucao da conversao do pre-processo.
 *
 * Renderiza na mesma area da consulta de publicacao usada pela lista, entao a
 * publicacao permanece visivel acima durante todo o fluxo: escolher,
 * conferir, confirmar e ver o resultado acontecem sem abrir nem fechar tela
 * nenhuma.
 *
 * A etapa de conferencia existe de proposito: a conversao grava o numero
 * judicial no registro, e vale o usuario ver lado a lado o que a publicacao
 * traz e o que o pre-processo ja tem.
 *
 * Os dois ids chegam por parametro e sao revalidados aqui. Nada vem de sessao.
 */
class PreProcessoVincularConfirmacao extends TPage
{
    private static $database = 'escritorio';

    public function __construct($param = null)
    {
        parent::__construct();

        $processo_id   = (int) ($param['processo_id'] ?? 0);
        $publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $nivel = (($param['nivel'] ?? '') === PreProcessoService::NIVEL_PRINCIPAL)
            ? PreProcessoService::NIVEL_PRINCIPAL
            : PreProcessoService::NIVEL_PROCESSO;

        $container = $param['target_container'] ?? PreProcessoVincularList::CONTAINER;
        $this->adianti_target_container = $container;

        try
        {
            TTransaction::open(self::$database);

            $processo   = Processo::find($processo_id);
            $publicacao = Publicacao::find($publicacao_id);

            if (!$processo || !$publicacao)
            {
                throw new Exception('Pré-processo ou publicação não encontrados. Refaça a busca.');
            }

            if (!PreProcessoService::ehPreProcesso($processo))
            {
                throw new Exception("O processo #{$processo->id} não é mais um pré-processo pendente. Nada foi alterado.");
            }

            $numero   = PreProcessoService::numeroDaPublicacao($publicacao, $nivel);
            $clientes = PreProcessoService::clientesDoProcesso($processo->id);

            $form = new BootstrapFormBuilder('form_PreProcessoVincularConfirmacao');

            /* Mesmo motivo da lista: setFormTitle nao aparece aqui dentro. */
            $titulo = new TElement('div');
            $titulo->class = 'curciol-passo-vinculo-titulo';
            $titulo->add('Confira antes de vincular');

            $linha_titulo = $form->addContent([$titulo]);
            $linha_titulo->layout = [' col-sm-12'];

            $html = new TElement('div');
            $html->style = 'padding:4px;';

            $html->add($this->bloco('Publicação', [
                'Número da publicação'  => $publicacao->numero_publicacao,
                'Disponibilizada em'    => $publicacao->data_disponibilizacao ? date('d/m/Y', strtotime($publicacao->data_disponibilizacao)) : '-',
                'Número que será usado' => $numero ?: '(a publicação não traz número)',
                'Título'                => $publicacao->titulo,
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
            $aviso->style = 'margin-top:12px;';
            $aviso->add(
                'O processo continua sendo o registro <b>#' . $processo->id . '</b>. '
                . 'Contratos, clientes, contrapartes, documentos e o histórico já lançado permanecem como estão; '
                . 'o que muda é o número, que passa a existir.'
            );
            $html->add($aviso);

            $linha = $form->addContent([$html]);
            $linha->layout = [' col-sm-12'];

            $contexto = [
                'processo_id'      => $processo->id,
                'publicacao_id'    => $publicacao->id,
                'nivel'            => $nivel,
                'target_container' => $container,
                'static'           => 1,
            ];

            $btn_confirmar = $form->addAction('Confirmar e vincular', new TAction([__CLASS__, 'onConfirmar'], $contexto), 'fas:check #ffffff');
            $btn_confirmar->addStyleClass('btn-primary');

            /* Voltar para a lista, no mesmo lugar: nada abre nem fecha. */
            $form->addAction(
                'Voltar à lista',
                new TAction(['PreProcessoVincularList', 'onShow'], [
                    'publicacao_id'    => $publicacao->id,
                    'nivel'            => $nivel,
                    'target_container' => $container,
                ]),
                'fas:arrow-left #666666'
            );

            $form->addAction(
                'Cancelar',
                new TAction(['PreProcessoVincularList', 'onFechar'], ['target_container' => $container, 'static' => 1]),
                'fas:times #dd5a43'
            );

            if (empty($numero))
            {
                $btn_confirmar->setProperty('disabled', 'disabled');
            }

            TTransaction::close();

            $caixa = new TElement('div');
            $caixa->class = 'curciol-passo-vinculo';
            $caixa->add($form);

            parent::add($caixa);
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
        $bloco->style = 'margin-bottom:12px;';

        $h = new TElement('h5');
        $h->style = 'margin:0 0 4px 0; font-weight:600;';
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
     * Tudo em uma transacao: numero, estado, publicacao e vinculos vao juntos.
     * Se qualquer verificacao falhar - numero ja usado por outro processo,
     * publicacao sem numero, registro que deixou de ser pendente - nada e
     * gravado e o usuario ve o motivo, com a tela intacta.
     */
    public static function onConfirmar($param = null)
    {
        $processo_id   = (int) ($param['processo_id'] ?? 0);
        $publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $nivel         = $param['nivel'] ?? PreProcessoService::NIVEL_PROCESSO;
        $container     = $param['target_container'] ?? PreProcessoVincularList::CONTAINER;

        try
        {
            TTransaction::open(self::$database);

            $processo = PreProcessoService::converter($processo_id, $publicacao_id, $nivel, TSession::getValue('userid'));
            $numero   = $processo->numero_cnj_numero;

            TTransaction::close();

            /*
                A classificacao de etapas roda fora da transacao da conversao:
                e manutencao, e uma falha nela nao pode desfazer um vinculo que
                ja esta correto.
            */
            APIPublicacaoController::onVerificaPublicacaoEtapa();

            TToast::show(
                'success',
                "Publicação vinculada ao processo #{$processo->id}.",
                'topRight',
                'far:check-circle'
            );

            /*
                A consulta da publicacao nao e recarregada: ela nunca saiu da
                tela. O que mudou nela e atualizado no lugar - o numero, que
                passou a existir, e os botoes, que trocam de papel.
            */
            $numero_html = htmlspecialchars((string) $numero, ENT_QUOTES, 'UTF-8');
            $numero_js   = addslashes((string) $numero);

            TScript::create("
                $('#publicacao_numero_processo').text('{$numero_js}');
                $(\"[name='btnCriarProcesso']\").closest('.fb-inline-field-container').hide();
                $(\"[name='btnVerProcesso']\").closest('.fb-inline-field-container').show();
            ");

            /* O resultado ocupa a area do fluxo, no lugar da confirmacao. */
            $resultado = "
                <div class='curciol-passo-vinculo'>
                    <div class='curciol-passo-vinculo-ok'>
                        <h5>Publicação vinculada</h5>
                        <p>
                            O pré-processo <b>#{$processo->id}</b> passou a ser o processo
                            <b>{$numero_html}</b>. O histórico já lançado continua no mesmo registro.
                        </p>
                    </div>
                </div>
            ";

            TScript::create("$('#" . addslashes($container) . "').html(" . json_encode($resultado) . ");");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {
    }
}
