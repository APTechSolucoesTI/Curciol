<?php

/**
 * Cadastro de parceria de levantamento (painel lateral).
 *
 * Fixa: cada participante tem um percentual e a soma e 100%.
 * Por tempo (por periodos, 13/11/2026): os dois marcos dividem o tempo do processo em 3 periodos (antes do 1o marco,
 * entre os marcos, depois do 2o marco); cada coluna e um periodo e soma 100%. O nivel diz em qual marco o percentual
 * da pessoa muda: nivel 1 (o "entre" repete o "depois"), nivel 2 (o "entre" repete o "antes"), nivel 1 e 2 (livre).
 * Cada pessoa fica numa linha so; a ordem das linhas (arrastar pela alca) e a ordem das telas dos financeiros gerados.
 * As regras de soma, nivel e "quem recebe a diferenca" sao as do
 * ParceriaPercentualService::validar(), a mesma que vale na geracao.
 * Excluir so e permitido se a parceria nunca foi usada num financeiro gerado.
 */
class ParceriaLevantamentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private static $database = 'escritorio';
    private static $activeRecord = 'ParceriaLevantamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_ParceriaLevantamento';

    const MARCO1_PADRAO = '01/11/2016';
    const MARCO2_PADRAO = '01/06/2018';

    use BuilderMasterDetailFieldListTrait;

    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle("Cadastro de parceria de levantamento");

        $criteria_pessoa = new TCriteria();
        // so parceiros (todo profissional tambem e parceiro)
        $criteria_pessoa->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = " . Grupo::PARCEIRO . ")"));

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $tipo = new TCombo('tipo');
        $ativo = new TCheckButton('ativo');
        $marco1 = new TDate('marco1');
        $marco2 = new TDate('marco2');

        $participante_id = new THidden('participante_id[]');
        $participante___row__id = new THidden('participante___row__id[]');
        $participante___row__data = new THidden('participante___row__data[]');
        $participante_pessoa_id = new TDBUniqueSearch('participante_pessoa_id[]', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria_pessoa);
        $participante_nivel = new TCombo('participante_nivel[]');
        $participante_percentual = new TEntry('participante_percentual[]');
        $participante_percentual_antes = new TEntry('participante_percentual_antes[]');
        $participante_percentual_entre = new TEntry('participante_percentual_entre[]');
        $participante_percentual_depois = new TEntry('participante_percentual_depois[]');
        $participante_recebe_diferenca = new TCombo('participante_recebe_diferenca[]');

        $this->fieldList_participantes = new TFieldList();
        $this->fieldList_participantes->addField(null, $participante_id, []);
        $this->fieldList_participantes->addField(null, $participante___row__id, ['uniqid' => true]);
        $this->fieldList_participantes->addField(null, $participante___row__data, []);
        $this->fieldList_participantes->addField(new TLabel("Pessoa", null, '14px', null), $participante_pessoa_id, ['width' => '30%']);
        $this->fieldList_participantes->addField(new TLabel("Nível", null, '14px', null), $participante_nivel, ['width' => '13%']);
        $this->fieldList_participantes->addField(new TLabel("Percentual (%)", null, '14px', null), $participante_percentual, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Antes do 1º marco (%)", null, '14px', null), $participante_percentual_antes, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Entre os marcos (%)", null, '14px', null), $participante_percentual_entre, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Depois do 2º marco (%)", null, '14px', null), $participante_percentual_depois, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Recebe a diferença", null, '14px', null), $participante_recebe_diferenca, ['width' => '10%']);

        $this->fieldList_participantes->width = '100%';
        $this->fieldList_participantes->setFieldPrefix('participante');
        $this->fieldList_participantes->name = 'fieldList_participantes';

        $this->default_item_participantes = new stdClass();

        $this->form->addField($participante_id);
        $this->form->addField($participante___row__id);
        $this->form->addField($participante___row__data);
        $this->form->addField($participante_pessoa_id);
        $this->form->addField($participante_nivel);
        $this->form->addField($participante_percentual);
        $this->form->addField($participante_percentual_antes);
        $this->form->addField($participante_percentual_entre);
        $this->form->addField($participante_percentual_depois);
        $this->form->addField($participante_recebe_diferenca);

        $this->fieldList_participantes->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $nome->addValidation("Nome", new TRequiredValidator());
        $tipo->addValidation("Tipo", new TRequiredValidator());

        $tipo->addItems(ParceriaLevantamento::tipos());
        $tipo->setValue(ParceriaLevantamento::TIPO_FIXA);
        $tipo->setChangeAction(new TAction([$this, 'onChangeTipo']));
        $tipo->enableSearch();

        $participante_nivel->addItems([
            ParceriaLevantamentoParticipante::NIVEL_FIXO   => 'Fixo',
            ParceriaLevantamentoParticipante::NIVEL_MARCO1 => 'Nível 1',
            ParceriaLevantamentoParticipante::NIVEL_MARCO2 => 'Nível 2',
            ParceriaLevantamentoParticipante::NIVEL_MARCO12 => 'Nível 1 e 2',
        ]);
        $participante_nivel->setDefaultOption('Selecione');
        $participante_recebe_diferenca->addItems(['S' => 'Sim']);
        $participante_recebe_diferenca->setDefaultOption('Não');
        foreach ([$participante_percentual, $participante_percentual_antes, $participante_percentual_entre, $participante_percentual_depois] as $campo_pct)
        {
            $campo_pct->setProperty('inputmode', 'decimal');
        }
        $participante_pessoa_id->setMinLength(3);
        $participante_pessoa_id->setMask('{nome}');

        $nome->forceUpperCase();
        $nome->setMaxLength(120);

        $marco1->setMask('dd/mm/yyyy');
        $marco1->setDatabaseMask('yyyy-mm-dd');
        $marco2->setMask('dd/mm/yyyy');
        $marco2->setDatabaseMask('yyyy-mm-dd');

        $ativo->setIndexValue('S');
        $ativo->setUseSwitch(true, 'blue');
        $ativo->setValue('S');

        $id->setEditable(false);

        $id->setSize('100%');
        $nome->setSize('100%');
        $tipo->setSize('100%');
        $marco1->setSize('100%');
        $marco2->setSize('100%');
        $participante_pessoa_id->setSize('100%');
        $participante_nivel->setSize('100%');
        $participante_percentual->setSize('100%');
        $participante_percentual_antes->setSize('100%');
        $participante_percentual_entre->setSize('100%');
        $participante_percentual_depois->setSize('100%');
        $participante_recebe_diferenca->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Tipo:", '#ff0000', '14px', null, '100%'),$tipo],[new TLabel("Ativo:", null, '14px', null, '100%'),$ativo]);
        $row1->layout = [' col-sm-2',' col-sm-5',' col-sm-3',' col-sm-2'];

        // marcos com a cor de cada um (a mesma das colunas e dos niveis) e a faixa dos 3 periodos
        $periodos = new TElement('div');
        $periodos->id = 'pl_periodos';
        $periodos->add('');
        $row2 = $this->form->addFields([new TLabel("<span class='pl-bol pl-b1'></span> 1º marco:", null, '14px', null, '100%'),$marco1],[new TLabel("<span class='pl-bol pl-b2'></span> 2º marco:", null, '14px', null, '100%'),$marco2],[$periodos]);
        $row2->layout = [' col-sm-3',' col-sm-3',' col-sm-6'];

        // participantes: so pessoas ja cadastradas (o cadastro de parceiros e feito na tela de Parceiros)

        $row3 = $this->form->addFields([new TFormSeparator("Participantes", '#333', '18', '#eee')]);
        $row3->layout = [' col-sm-12'];

        $ajuda = new TElement('div');
        $ajuda->id = 'ajuda_participantes';
        $ajuda->style = 'color:#666; font-size:12px; margin-bottom:8px;';
        $ajuda->add('');
        // legenda dos niveis (so na parceria por tempo)
        $legenda = new TElement('div');
        $legenda->id = 'pl_legenda';
        $legenda->add("<span><span class='pl-bol pl-b1'></span><b class='pl-c1'>Nível 1</b>: muda só no 1º marco (o \"entre\" repete o \"depois\")</span>"
            . "<span><span class='pl-bol pl-b2'></span><b class='pl-c2'>Nível 2</b>: muda só no 2º marco (o \"entre\" repete o \"antes\")</span>"
            . "<span><span class='pl-bol pl-b12'></span><b class='pl-c12'>Nível 1 e 2</b>: muda nos dois marcos (as 3 colunas livres)</span>");
        $row4 = $this->form->addFields([$ajuda, $legenda]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([$this->fieldList_participantes]);
        $row5->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'], ['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary');

        $btn_ondelete = $this->form->addAction("Excluir", new TAction([$this, 'onDelete'], ['static' => 1]), 'far:trash-alt #dd5a43');
        $this->btn_ondelete = $btn_ondelete;

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ParceriaLevantamentoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ParceriaLevantamentoForm]');
        $style->width = '85% !important';
        $style->show(true);

        // colunas que nao valem para o tipo escolhido ficam escondidas (a classe e posta no formulario por aplicarTipo).
        // Posicao na lista: 4 pessoa, 5 nivel, 6 percentual, 7 antes, 8 entre, 9 depois, 10 diferenca, 11 excluir,
        // 12 alca de arrastar (posta pelo plIniciar); 1 a 3 sao campos ocultos.
        // Cores: 1o marco azul, 2o marco laranja, nivel 1 e 2 roxo (marcos, cabecalhos, divisorias das colunas e linhas).
        $f = "#" . self::$formName;
        $t = "table.tfieldlist > * > tr > ";
        $seletores = ["{$f}.pl-fixa {$t}:nth-child(5)", "{$f}.pl-fixa {$t}:nth-child(7)", "{$f}.pl-fixa {$t}:nth-child(8)", "{$f}.pl-fixa {$t}:nth-child(9)",
                      "{$f}.pl-temporal {$t}:nth-child(6)", "{$f}.pl-fixa #pl_periodos", "{$f}.pl-fixa #pl_legenda"];
        $css = implode(', ', $seletores) . ' { display: none !important; }' . str_replace(['__F__', '__T__'], [$f, $t], <<<'CSS'
            __F__ .pl-bol { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; vertical-align: middle; }
            __F__ .pl-b1 { background: #1e6fd9; } __F__ .pl-b2 { background: #e07b00; } __F__ .pl-b12 { background: #7b4fc9; }
            __F__ .pl-c1 { color: #1e6fd9; } __F__ .pl-c2 { color: #e07b00; } __F__ .pl-c12 { color: #7b4fc9; }
            __F__ .pl-marco { border: 1px solid #d9dde3; border-left-width: 5px; border-radius: 4px; padding: 6px 8px; }
            __F__ .pl-marco.pl-m1 { background: #e8f1fd; border-color: #b9d3f7; border-left-color: #1e6fd9; }
            __F__ .pl-marco.pl-m2 { background: #fff3e3; border-color: #f5cf9c; border-left-color: #e07b00; }
            __F__ .pl-marco.pl-m1 label { color: #1e6fd9 !important; font-weight: bold; }
            __F__ .pl-marco.pl-m2 label { color: #e07b00 !important; font-weight: bold; }
            __F__ #pl_periodos .pl-rot { font-size: 12px; color: #555; margin-bottom: 3px; }
            __F__ #pl_periodos .pl-barra { display: flex; height: 30px; border: 1px solid #d9dde3; border-radius: 4px; overflow: hidden; }
            __F__ #pl_periodos .pl-barra div { flex: 1; display: flex; align-items: center; justify-content: center; font-size: 11px; white-space: nowrap; overflow: hidden; padding: 0 6px; background: #eef0f3; }
            __F__ #pl_periodos .pl-barra div + div { background: #f7f8fa; border-left: 3px solid #1e6fd9; }
            __F__ #pl_periodos .pl-barra div + div + div { background: #eef0f3; border-left: 3px solid #e07b00; }
            __F__ #pl_legenda { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12px; margin-bottom: 8px; }
            __F__.pl-temporal __T__:nth-child(7) { border-right: 3px solid #1e6fd9 !important; }
            __F__.pl-temporal __T__:nth-child(8) { border-right: 3px solid #e07b00 !important; }
            __F__ table.tfieldlist > thead small { display: block; color: #888; font-weight: normal; font-size: 11px; }
            __F__ .pl-h1 { color: #1e6fd9; font-weight: bold; } __F__ .pl-h2 { color: #e07b00; font-weight: bold; } __F__ .pl-h { font-weight: bold; }
            __F__.pl-temporal tr.pl-n1 > td:nth-child(4) { box-shadow: inset 5px 0 0 #1e6fd9; }
            __F__.pl-temporal tr.pl-n2 > td:nth-child(4) { box-shadow: inset 5px 0 0 #e07b00; }
            __F__.pl-temporal tr.pl-n12 > td:nth-child(4) { box-shadow: inset 5px 0 0 #7b4fc9; }
            __F__ tr.pl-n1 select[name="participante_nivel[]"] { background: #e8f1fd; color: #1e6fd9; font-weight: bold; border-color: #b9d3f7; }
            __F__ tr.pl-n2 select[name="participante_nivel[]"] { background: #fff3e3; color: #e07b00; font-weight: bold; border-color: #f5cf9c; }
            __F__ tr.pl-n12 select[name="participante_nivel[]"] { background: #f2ecfc; color: #7b4fc9; font-weight: bold; border-color: #d5c4f2; }
            __F__ input.pl-espelho { background: #f1f2f4 !important; color: #888 !important; font-style: italic; }
            __F__ input[name^="participante_percentual"] { text-align: right; }
            __F__ .pl-alca { cursor: grab; color: #8a919c; font-size: 16px; padding: 4px 6px; user-select: none; }
            __F__ .pl-alca:active { cursor: grabbing; }
            __F__ tr.pl-total > td { border-top: 2px solid #d9dde3; padding-top: 6px !important; font-weight: bold; vertical-align: top; }
            __F__ td.pl-tot { text-align: right; padding-right: 10px !important; }
            __F__ td.pl-tot small { display: block; font-weight: normal; font-size: 11px; }
            __F__ .pl-ok { color: #2e7d32; } __F__ .pl-erro { color: #c62828; }
            __F__ tr.ui-sortable-helper { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.2); }
            CSS);
        parent::add(TElement::tag('style', $css));

        // totais por coluna, nivel/espelho do "entre", cabecalhos com as datas, alca de arrastar e pessoa repetida, ao vivo
        // (as regras de 100% sao as do servico; aqui e so para o usuario conferir)
        TScript::create(str_replace('__F__', $f, <<<'JS'
            window.plNum = function (v) { v = String(v || '').trim(); if (v.indexOf(',') >= 0) { v = v.replace(/\./g, '').replace(',', '.'); } return parseFloat(v) || 0; };
            window.plPct = function (x) { return (Math.round(x * 10000) / 10000).toLocaleString('pt-BR', {maximumFractionDigits: 4}) + '%'; };
            window.plMenosUmDia = function (br) {
                var p = String(br || '').split('/'); if (p.length !== 3) { return ''; }
                var d = new Date(Date.UTC(+p[2], +p[1] - 1, +p[0] - 1));
                return ('0' + d.getUTCDate()).slice(-2) + '/' + ('0' + (d.getUTCMonth() + 1)).slice(-2) + '/' + d.getUTCFullYear();
            };
            window.plIniciar = function () {
                var f = $('__F__'), t = f.find('table.tfieldlist').first();
                if (!t.length) { return; }
                t.find('> thead > tr').each(function () { if (!$(this).children('.pl-alca-cel').length) { $(this).append('<th class="pl-alca-cel"></th>'); } });
                t.find('> tfoot > tr').not('.pl-total').each(function () { if (!$(this).children('.pl-alca-cel').length) { $(this).append('<td class="pl-alca-cel"></td>'); } });
                t.find('> tbody > tr').each(function () { if (!$(this).children('.pl-alca-cel').length) { $(this).append('<td class="pl-alca-cel"><span class="pl-alca" title="Arraste para mudar a ordem">&#9776;</span></td>'); } });
                if (!t.find('> tfoot > tr.pl-total').length) {
                    var n = t.find('> tbody > tr:first > td').length, tr = $('<tr class="pl-total"></tr>');
                    for (var i = 1; i <= n; i++) {
                        var td = $('<td></td>');
                        if (i <= 3) { td.css('display', 'none'); }
                        if (i === 4) { td.text('Total'); }
                        if (i >= 6 && i <= 9) { td.addClass('pl-tot').attr('data-col', i); }
                        tr.append(td);
                    }
                    t.find('> tfoot').append(tr);
                }
                var tb = t.find('> tbody');
                if (!tb.data('pl-ordenavel') && tb.sortable) {
                    tb.sortable({
                        handle: '.pl-alca', axis: 'y', cursor: 'grabbing',
                        helper: function (e, linha) { var orig = linha.children(), h = linha.clone(); h.children().each(function (i) { $(this).width(orig.eq(i).width()); }); return h; },
                        update: function () { window.plAtualizar(); }
                    });
                    tb.data('pl-ordenavel', 1);
                }
                f.find('[name="marco1"]').closest('div[class*="col-sm"]').addClass('pl-marco pl-m1');
                f.find('[name="marco2"]').closest('div[class*="col-sm"]').addClass('pl-marco pl-m2');
                window.plAtualizar();
            };
            window.plAtualizar = function () {
                var f = $('__F__'), t = f.find('table.tfieldlist').first(), temporal = f.hasClass('pl-temporal');
                if (!t.length) { return; }
                var m1 = f.find('[name="marco1"]').val() || '', m2 = f.find('[name="marco2"]').val() || '';
                var th = t.find('> thead > tr:first').children();
                th.eq(6).html('<span class="pl-h1">Antes do 1º marco (%)</span><small>' + (m1 ? 'até ' + plMenosUmDia(m1) : '&nbsp;') + '</small>');
                th.eq(7).html('<span class="pl-h">Entre os marcos (%)</span><small>' + (m1 && m2 ? m1 + ' a ' + plMenosUmDia(m2) : '&nbsp;') + '</small>');
                th.eq(8).html('<span class="pl-h2">Depois do 2º marco (%)</span><small>' + (m2 ? 'a partir de ' + m2 : '&nbsp;') + '</small>');
                $('#pl_periodos').html('<div class="pl-rot">Os marcos dividem o tempo do processo (distribuição → levantamento) em 3 períodos; em cada um vale a divisão daquela coluna:</div>'
                    + '<div class="pl-barra"><div>Antes de ' + (m1 || '…') + '</div><div>' + (m1 || '…') + ' a ' + (m2 ? plMenosUmDia(m2) : '…') + '</div><div>A partir de ' + (m2 || '…') + '</div></div>');
                var tot = {6: 0, 7: 0, 8: 0, 9: 0};
                t.find('> tbody > tr').each(function () {
                    var r = $(this), nivel = r.find('[name="participante_nivel[]"]').val() || '';
                    var antes = r.find('[name="participante_percentual_antes[]"]'), entre = r.find('[name="participante_percentual_entre[]"]'), depois = r.find('[name="participante_percentual_depois[]"]');
                    r.removeClass('pl-n1 pl-n2 pl-n12').addClass(nivel === 'MARCO1' ? 'pl-n1' : (nivel === 'MARCO2' ? 'pl-n2' : (nivel === 'MARCO12' ? 'pl-n12' : '')));
                    if (nivel === 'MARCO12') {
                        entre.prop('readonly', false).removeClass('pl-espelho').attr('title', '');
                    } else {
                        entre.prop('readonly', true).addClass('pl-espelho');
                        entre.val(nivel === 'MARCO1' ? depois.val() : (nivel === 'MARCO2' ? antes.val() : ''));
                        entre.attr('title', nivel === 'MARCO1' ? 'Nível 1: repete o "depois do 2º marco"' : (nivel === 'MARCO2' ? 'Nível 2: repete o "antes do 1º marco"' : 'Escolha o nível'));
                    }
                    tot[6] += plNum(r.find('[name="participante_percentual[]"]').val());
                    tot[7] += plNum(antes.val()); tot[8] += plNum(entre.val()); tot[9] += plNum(depois.val());
                });
                t.find('> tfoot td.pl-tot').each(function () {
                    var c = +$(this).attr('data-col'), v = tot[c] || 0, ok = Math.abs(v - 100) < 0.000001, falta = 100 - v;
                    $(this).html('<span class="' + (ok ? 'pl-ok' : 'pl-erro') + '">' + plPct(v) + '</span><small class="' + (ok ? 'pl-ok' : 'pl-erro') + '">'
                        + (ok ? 'fechou 100%' : (falta > 0 ? 'faltam ' : 'sobram ') + plPct(Math.abs(falta))) + '</small>');
                });
            };
            $(document).off('input.plpct').on('input.plpct', '__F__ table.tfieldlist input[name^="participante_percentual"]', function () { var v = this.value.replace(/[^0-9,.]/g, ''); if (v !== this.value) { this.value = v; } });
            $(document).off('input.plsoma change.plsoma keyup.plsoma').on('input.plsoma change.plsoma keyup.plsoma', '__F__ table.tfieldlist :input, __F__ [name="marco1"], __F__ [name="marco2"]', function () { window.plAtualizar(); });
            // excluir ou adicionar linha: a lista muda depois do clique
            $(document).off('click.plsoma').on('click.plsoma', '__F__ table.tfieldlist .btn', function () { setTimeout(window.plIniciar, 400); });
            // mesma pessoa duas vezes: limpa o campo na hora (cada pessoa fica numa linha so)
            $(document).off('change.pldup').on('change.pldup', '__F__ select[name="participante_pessoa_id[]"]', function () {
                var eu = this, v = $(eu).val(), repetida = false;
                if (!v) { return; }
                $('__F__ select[name="participante_pessoa_id[]"]').each(function () { if (this !== eu && $(this).val() === v) { repetida = true; } });
                if (repetida) {
                    var nome = $(eu).find('option:selected').text();
                    $(eu).val(null).trigger('change');
                    __adianti_warning('Participante repetido', nome + ' já está nesta parceria. Cada pessoa fica numa linha só: para percentuais diferentes em cada período use o nível "Nível 1 e 2".');
                }
            });
            JS));
    }

    /**
     * Mostra/oculta marcos e colunas conforme o tipo.
     */
    public static function onChangeTipo($param = null)
    {
        self::aplicarTipo($param['tipo'] ?? ParceriaLevantamento::TIPO_FIXA);
    }

    private static function aplicarTipo($tipo): void
    {
        $temporal = ($tipo === ParceriaLevantamento::TIPO_TEMPORAL);

        if ($temporal)
        {
            BootstrapFormBuilder::showField(self::$formName, 'marco1');
            BootstrapFormBuilder::showField(self::$formName, 'marco2');
        }
        else
        {
            BootstrapFormBuilder::hideField(self::$formName, 'marco1');
            BootstrapFormBuilder::hideField(self::$formName, 'marco2');
        }

        $classe = $temporal ? 'pl-temporal' : 'pl-fixa';
        $ajuda = $temporal
            ? 'Por tempo: cada coluna é um período e deve somar 100%. O nível diz em qual marco o percentual da pessoa muda. Arraste pela alça (☰) para mudar a ordem.'
            : 'Fixa: os percentuais devem somar 100%. Arraste pela alça (☰) para mudar a ordem.';

        TScript::create("setTimeout(function () { $('#" . self::$formName . "').removeClass('pl-fixa pl-temporal').addClass('{$classe}'); $('#ajuda_participantes').text(" . json_encode($ajuda) . "); if (window.plIniciar) { window.plIniciar(); } }, 250);");
    }

    /**
     * "12,5" ou "12.5" -> float; vazio -> null; qualquer outra coisa (1,2,3, abc, 1e2, negativo) -> excecao.
     * $rotulo identifica a linha na mensagem.
     */
    private static function numero($valor, string $rotulo): ?float
    {
        $texto = trim((string) $valor);

        if ($texto === '') { return null; }

        if (!preg_match('/^\d+([.,]\d+)?$/', $texto))
        {
            throw new Exception("Percentual inválido para {$rotulo}: '{$texto}'. Use apenas números com vírgula ou ponto.");
        }

        return (float) str_replace(',', '.', $texto);
    }

    /** Linhas da lista -> array para o servico (so linhas preenchidas). */
    private function participantesDoPost(string $tipo): array
    {
        $saida = [];
        $fixa = ($tipo === ParceriaLevantamento::TIPO_FIXA);
        $n = 0;

        foreach ((array) $this->fieldList_participantes->getPostData() as $linha)
        {
            $n++;
            $l = (array) $linha;
            $pessoa = $l['pessoa_id'] ?? null;
            $pessoa = is_array($pessoa) ? reset($pessoa) : $pessoa;

            $rotulo = "a linha {$n}";
            if (!empty($pessoa))
            {
                $obj = Pessoa::find((int) $pessoa);
                $rotulo = $obj ? $obj->nome : $rotulo;
            }

            $pct    = self::numero($l['percentual'] ?? null, $rotulo);
            $antes  = self::numero($l['percentual_antes'] ?? null, $rotulo);
            $entre  = self::numero($l['percentual_entre'] ?? null, $rotulo);
            $depois = self::numero($l['percentual_depois'] ?? null, $rotulo);
            $nivel  = $fixa ? ParceriaLevantamentoParticipante::NIVEL_FIXO : (string) ($l['nivel'] ?? '');

            if (empty($pessoa) && $pct === null && $antes === null && $depois === null)
            {
                continue; // linha em branco
            }

            if (empty($pessoa))
            {
                throw new Exception("Informe a pessoa de cada participante (linha {$n}) ou remova a linha em branco.");
            }

            // vazio nao vale 0: o servico nao define isso; o usuario informa 0 explicitamente
            if ($fixa && $pct === null)
            {
                throw new Exception("Informe o percentual de {$rotulo} (use 0 se não participa).");
            }
            if (!$fixa && !in_array($nivel, ParceriaPercentualService::niveisTemporais(), true))
            {
                throw new Exception("Escolha o nível de {$rotulo}.");
            }
            if (!$fixa && ($antes === null || $depois === null))
            {
                throw new Exception("Informe os percentuais \"antes do 1º marco\" e \"depois do 2º marco\" de {$rotulo} (use 0 se não participa).");
            }
            // nivel 1 e 2: o "entre" e informado; nivel 1 e nivel 2 repetem o depois / o antes (o campo da tela so espelha)
            if (!$fixa && $nivel === ParceriaLevantamentoParticipante::NIVEL_MARCO12 && $entre === null)
            {
                throw new Exception("Informe o percentual \"entre os marcos\" de {$rotulo} (use 0 se não participa).");
            }
            if (!$fixa)
            {
                $entre = ParceriaPercentualService::entre(['nivel' => $nivel, 'percentual_antes' => $antes, 'percentual_entre' => $entre, 'percentual_depois' => $depois]);
            }

            $saida[] = [
                'pessoa_id'         => (int) $pessoa,
                'nivel'             => $nivel,
                'percentual'        => $fixa ? $pct : null,
                'percentual_antes'  => $fixa ? null : $antes,
                'percentual_entre'  => $fixa ? null : $entre,
                'percentual_depois' => $fixa ? null : $depois,
                'recebe_diferenca'  => (($l['recebe_diferenca'] ?? 'N') === 'S') ? 'S' : 'N',
            ];
        }

        return $saida;
    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $this->form->validate(); // validate form data

            $data = $this->form->getData();
            $nome = trim((string) $data->nome);
            $tipo = (string) $data->tipo;

            self::exigirNomeUnico($nome, (int) $data->id);

            $participantes = array_values($this->participantesDoPost($tipo));
            // a diferenca de centavos fica sempre com a Curciol quando ela participa (regra de 22/10/2026)
            $curciol = RepasseColaboradorService::indiceDaDiferenca(array_column($participantes, 'pessoa_id'), null);
            if ($curciol !== null)
            {
                foreach (array_keys($participantes) as $i)
                {
                    $participantes[$i]['recebe_diferenca'] = ($i === $curciol) ? 'S' : 'N';
                }
            }

            // as mesmas regras (soma 100, nivel, diferenca, repetidos) da geracao do financeiro
            ParceriaPercentualService::validar($tipo, $participantes);

            if ($tipo === ParceriaLevantamento::TIPO_TEMPORAL)
            {
                if (empty($data->marco1) || empty($data->marco2))
                {
                    throw new Exception('Informe os dois marcos da parceria por tempo.');
                }
                if (substr($data->marco1, 0, 10) > substr($data->marco2, 0, 10))
                {
                    throw new Exception('O primeiro marco deve ser anterior ao segundo.');
                }
            }

            $object = new ParceriaLevantamento(((int) $data->id) ?: null);
            $object->nome   = $nome;
            $object->tipo   = $tipo;
            $object->marco1 = ($tipo === ParceriaLevantamento::TIPO_TEMPORAL) ? $data->marco1 : null;
            $object->marco2 = ($tipo === ParceriaLevantamento::TIPO_TEMPORAL) ? $data->marco2 : null;
            $object->ativo  = ($data->ativo === 'S') ? 'S' : 'N';

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object

            // apaga e regrava os participantes na ordem da tela (a ficha dos financeiros ja gerados guarda os percentuais e a
            // ordem da epoca)
            ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $object->id)->delete();

            foreach ($participantes as $ordem => $p)
            {
                $novo = new ParceriaLevantamentoParticipante();
                $novo->parceria_levantamento_id = $object->id;
                $novo->pessoa_id          = $p['pessoa_id'];
                $novo->nivel              = $p['nivel'];
                $novo->percentual         = $p['percentual'];
                $novo->percentual_antes   = $p['percentual_antes'];
                $novo->percentual_entre   = $p['percentual_entre'];
                $novo->percentual_depois  = $p['percentual_depois'];
                $novo->recebe_diferenca   = $p['recebe_diferenca'];
                $novo->ordem              = $ordem + 1;
                $novo->store();
            }

            $data->id = $object->id;

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            Navegacao::voltarParaLista('ParceriaLevantamentoList', __CLASS__);

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /** Nome sem acento (NFC ou NFD) e sem caixa, para comparar. */
    private static function chaveNome(string $nome): string
    {
        if (class_exists('Normalizer')) { $nome = Normalizer::normalize($nome, Normalizer::FORM_D) ?: $nome; }
        $nome = preg_replace('/\p{Mn}+/u', '', TratamentosService::removerAcentos($nome));

        return mb_strtolower(str_replace(['ç', 'Ç'], 'c', trim($nome)));
    }

    /** A tabela tem UNIQUE no nome, mas a comparacao dela e exata e o banco pode ter acentos em NFD. */
    private static function exigirNomeUnico(string $nome, int $id): void
    {
        $chave = self::chaveNome($nome);

        foreach (ParceriaLevantamento::getObjects() ?: [] as $outra)
        {
            if ((int) $outra->id !== $id && self::chaveNome((string) $outra->nome) === $chave)
            {
                throw new Exception("Já existe uma parceria com o nome \"{$outra->nome}\".");
            }
        }
    }

    /**
     * Pede confirmacao e, se a parceria nunca foi usada, exclui.
     */
    public function onDelete($param = null)
    {
        try
        {
            $id = (int) ($param['id'] ?? 0);

            if ($id <= 0)
            {
                throw new Exception('Salve a parceria antes de excluir.');
            }

            self::exigirNaoUsada($id);

            new TQuestion(
                'Deseja realmente excluir esta parceria e seus participantes?',
                new TAction([__CLASS__, 'onConfirmDelete'], ['id' => $id, 'static' => 1])
            );
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onConfirmDelete($param = null)
    {
        try
        {
            $id = (int) ($param['id'] ?? 0);

            TTransaction::open(self::$database);

            self::exigirNaoUsada($id);

            ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $id)->delete();
            $object = new ParceriaLevantamento($id);
            $object->delete();

            TTransaction::close();

            TToast::show('success', "Registro excluído", 'topRight', 'far:check-circle');
            Navegacao::voltarParaLista('ParceriaLevantamentoList', __CLASS__);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Parceria usada em financeiro gerado nao pode sumir (historico).
     * Abre a propria transacao se nao houver uma.
     */
    public static function exigirNaoUsada(int $id): void
    {
        $abriu = false;

        if (!TTransaction::get())
        {
            TTransaction::open(self::$database);
            $abriu = true;
        }

        try
        {
            $usos = LevantamentoFinanceiro::where('parceria_levantamento_id', '=', $id)->count();
        }
        finally
        {
            if ($abriu) { TTransaction::close(); }
        }

        if ($usos > 0)
        {
            throw new Exception('Parceria usada em financeiros gerados — desative em vez de excluir.');
        }
    }

    private static function formatar($valor): ?string
    {
        return ($valor === null || $valor === '') ? null : rtrim(rtrim(number_format((float) $valor, 8, ',', ''), '0'), ',');
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new ParceriaLevantamento($key); // instantiates the Active Record

                $this->fieldList_participantes->addHeader();

                $participantes = $object->participantes();

                if ($participantes)
                {
                    foreach ($participantes as $p)
                    {
                        $item = new stdClass();
                        $item->participante_id = $p->id;
                        $item->participante_pessoa_id = $p->pessoa_id;
                        $item->participante_nivel = $p->nivel;
                        $item->participante_percentual = self::formatar($p->percentual);
                        $item->participante_percentual_antes = self::formatar($p->percentual_antes);
                        $item->participante_percentual_entre = self::formatar($p->percentual_entre);
                        $item->participante_percentual_depois = self::formatar($p->percentual_depois);
                        $item->participante_recebe_diferenca = ($p->recebe_diferenca === 'S') ? 'S' : '';
                        $item->__row__id = 'b' . uniqid();
                        $item->__row__data = '';
                        $this->fieldList_participantes->addDetail($item);
                    }
                }
                else
                {
                    $this->fieldList_participantes->addDetail($this->default_item_participantes);
                }

                $this->fieldList_participantes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

                $this->form->setData($object); // fill the form

                TTransaction::close(); // close the transaction

                self::aplicarTipo($object->tipo);
            }
            else
            {
                $this->novo();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /** Parceria nova: uma linha em branco e os marcos padrao. */
    private function novo(): void
    {
        $this->fieldList_participantes->addHeader();
        $this->fieldList_participantes->addDetail($this->default_item_participantes);
        $this->fieldList_participantes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->form->setData((object) [
            'tipo' => ParceriaLevantamento::TIPO_FIXA, 'ativo' => 'S',
            'marco1' => self::MARCO1_PADRAO, 'marco2' => self::MARCO2_PADRAO,
        ]);

        self::aplicarTipo(ParceriaLevantamento::TIPO_FIXA);
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);
        $this->novo();
    }

    public function onShow($param = null)
    {
        $this->novo();
    }

    public static function getFormName()
    {
        return self::$formName;
    }

}
