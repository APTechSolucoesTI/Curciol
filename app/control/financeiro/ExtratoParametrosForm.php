<?php
/**
 * Extrato: janela unica com os parametros e filtros (substitui Trocar parametros, Filtros e Gerar relatorio).
 * "Buscar" recarrega a listagem; no rodape, o relatorio em PDF ou Excel com o que estiver na janela.
 */
class ExtratoParametrosForm extends TWindow
{
    protected $form;
    private static $formName = 'form_ExtratoParametros';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.75, null);
        parent::setTitle('Parâmetros e filtros do extrato');
        parent::setProperty('class', 'window_modal');

        $this->form = new BootstrapFormBuilder(self::$formName);
        $p = ExtratoParametrosService::atuais();

        $contaCaixa = new TDBMultiSearch('conta_caixa_ids', 'escritorio', 'ContaCaixa', 'id', 'nome', 'nome asc');
        $contaCaixa->setMinLength(0);
        $contaCaixa->setMask('{nome}');
        $situacao = new TCombo('situacao');
        $situacao->addItems(['' => 'Compensados e não compensados', 'S' => 'Só compensados', 'N' => 'Só não compensados']);
        $situacao->setDefaultOption(false);
        $periodoTipo = new TCombo('periodo_tipo');
        $periodoTipo->addItems(ExtratoParametrosService::ROTULO_DATA);
        $periodoTipo->setDefaultOption(false);
        $dataDe = new TDate('data_de');
        $dataAte = new TDate('data_ate');
        foreach ([$dataDe, $dataAte] as $d) { $d->setMask('dd/mm/yyyy'); $d->setDatabaseMask('yyyy-mm-dd'); }
        $tipoExtrato = new TDBMultiSearch('tipo_extrato_ids', 'escritorio', 'TipoExtrato', 'id', 'nome', 'nome asc');
        $tipoExtrato->setMinLength(0);
        $tipoExtrato->setMask('{nome}');
        $historico = new TEntry('historico');
        $entrada = new TNumeric('entrada_valor', 2, ',', '.');
        $saida = new TNumeric('saida_valor', 2, ',', '.');
        $pessoa = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc');
        $pessoa->setMinLength(2);
        $pessoa->setMask('{nome}');
        $processo = new TDBUniqueSearch('processo_id', 'escritorio', 'Processo', 'id', 'numero_cnj_numero', 'numero_cnj_numero asc');
        $processo->setFilterColumns(['numero_cnj_numero', 'numero_outro']);
        $processo->setMinLength(3);
        $processo->setMask('{numero_cnj_numero} {numero_outro}');
        $processo->setTip('Digite parte do número. Escolhendo um processo pai ou principal, entram também as movimentações dos processos filhos.');
        foreach ([$contaCaixa, $situacao, $periodoTipo, $dataDe, $dataAte, $tipoExtrato, $historico, $entrada, $saida, $pessoa, $processo] as $w) { $w->setSize('100%'); }

        $atalhos = "<div class='ep-atalhos'>"
            . "<a href='javascript:void(0)' onclick=\"epPeriodo(7)\">7 dias</a><a href='javascript:void(0)' onclick=\"epPeriodo(15)\">15 dias</a>"
            . "<a href='javascript:void(0)' onclick=\"epPeriodo(30)\">30 dias</a><a href='javascript:void(0)' onclick=\"epPeriodo('mes')\">Este mês</a>"
            . "<a href='javascript:void(0)' onclick=\"epPeriodo('passado')\">Mês passado</a><a href='javascript:void(0)' onclick=\"epPeriodo('')\">Sem período</a></div>";

        $r = $this->form->addFields([new TLabel('Conta-caixa', null, '13px', null, '100%'), $contaCaixa], [new TLabel('Situação', null, '13px', null, '100%'), $situacao]);
        $r->layout = ['col-sm-8', 'col-sm-4'];
        $r = $this->form->addFields([new TLabel('Período pela data de', null, '13px', null, '100%'), $periodoTipo], [new TLabel('De', null, '13px', null, '100%'), $dataDe], [new TLabel('Até', null, '13px', null, '100%'), $dataAte]);
        $r->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
        $this->form->addContent([$atalhos]);
        $r = $this->form->addFields([new TLabel('Pessoa / colaborador', null, '13px', null, '100%'), $pessoa], [new TLabel('Processo', null, '13px', null, '100%'), $processo]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Tipo de extrato', null, '13px', null, '100%'), $tipoExtrato], [new TLabel('Histórico contém', null, '13px', null, '100%'), $historico]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Valor da entrada (R$)', null, '13px', null, '100%'), $entrada], [new TLabel('Valor da saída (R$)', null, '13px', null, '100%'), $saida], []);
        $r->layout = ['col-sm-3', 'col-sm-3', 'col-sm-6'];

        $b = $this->form->addAction('Buscar', new TAction([$this, 'onBuscar']), 'fas:search #ffffff');
        $b->addStyleClass('btn-primary');
        $this->form->addAction('Limpar', new TAction([$this, 'onLimpar']), 'fas:eraser #f44336');
        $this->form->addAction('Relatório (PDF)', new TAction([$this, 'onRelatorioPdf']), 'far:file-pdf #D32F2F');
        $this->form->addAction('Relatório (Excel)', new TAction([$this, 'onRelatorioXls']), 'far:file-excel #218838');

        $dados = new stdClass;
        $dados->conta_caixa_ids = $p['conta_caixa_ids'] ?? [];
        $dados->situacao = $p['situacao'] ?? '';
        $dados->periodo_tipo = $p['periodo_tipo'] ?? 'compensacao';
        $dados->data_de = $p['data_de'] ?? null;
        $dados->data_ate = $p['data_ate'] ?? null;
        $dados->tipo_extrato_ids = $p['tipo_extrato_ids'] ?? [];
        $dados->historico = $p['historico'] ?? '';
        $dados->entrada_valor = isset($p['entrada_valor']) && $p['entrada_valor'] !== null ? number_format($p['entrada_valor'], 2, ',', '.') : '';
        $dados->saida_valor = isset($p['saida_valor']) && $p['saida_valor'] !== null ? number_format($p['saida_valor'], 2, ',', '.') : '';
        $dados->pessoa_id = $p['pessoa_id'] ?? null;
        $dados->processo_id = $p['processo_id'] ?? null;
        $this->form->setData($dados);

        $estilo = new TElement('style');
        $estilo->add('.ep-atalhos { margin:-4px 0 8px 0; font-size:12px; } .ep-atalhos a { margin-right:14px; }
                      form[name=form_ExtratoParametros] .card-footer, form[name=form_ExtratoParametros] .panel-footer { display:flex; flex-wrap:wrap; gap:6px; }');
        parent::add($this->form);
        parent::add($estilo);
        TScript::create("
            window.epPeriodo = function (q) {
                var f = function (d) { return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear(); };
                var hoje = new Date(), de = '', ate = '';
                if (q === 'mes') { de = f(new Date(hoje.getFullYear(), hoje.getMonth(), 1)); ate = f(hoje); }
                else if (q === 'passado') { de = f(new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1)); ate = f(new Date(hoje.getFullYear(), hoje.getMonth(), 0)); }
                else if (q) { var d = new Date(hoje); d.setDate(d.getDate() - q); de = f(d); ate = f(hoje); }
                $('form[name=form_ExtratoParametros] [name=data_de]').val(de);
                $('form[name=form_ExtratoParametros] [name=data_ate]').val(ate);
            };
        ");
    }

    public function onShow($param = null) {}

    public function onBuscar($param = null)
    {
        try {
            $p = ExtratoParametrosService::normalizar($this->form->getData());
            TSession::setValue(ExtratoParametrosService::SESSAO, $p);
            // os filtros antigos (cortina e colunas) dao lugar aos parametros
            TSession::setValue('ExtratoList_filter_data', null);
            TSession::setValue('ExtratoList_filters', null);
            TWindow::closeWindow();
            AdiantiCoreApplication::loadPage('ExtratoList', 'onReload', ['offset' => 0, 'first_page' => 1]);
        } catch (Throwable $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public function onLimpar($param = null)
    {
        TSession::setValue(ExtratoParametrosService::SESSAO, null);
        TSession::setValue('ExtratoList_filter_data', null);
        TSession::setValue('ExtratoList_filters', null);
        TWindow::closeWindow();
        AdiantiCoreApplication::loadPage('ExtratoList', 'onReload', ['offset' => 0, 'first_page' => 1]);
    }

    public function onRelatorioPdf($param = null)
    {
        $this->relatorio('gerarPdf');
    }

    public function onRelatorioXls($param = null)
    {
        $this->relatorio('gerarXls');
    }

    private function relatorio(string $metodo): void
    {
        try {
            $p = ExtratoParametrosService::normalizar($this->form->getData());
            $this->form->setData($this->form->getData());
            $doc = ExtratoRelatorioService::$metodo($p);
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
}
