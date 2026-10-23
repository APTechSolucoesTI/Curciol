<?php

/**
 * Cadastro de parceria de levantamento (painel lateral).
 *
 * Fixa: cada participante tem um percentual e a soma e 100%.
 * Por tempo (dois marcos): participantes no nivel 1 e no nivel 2, cada um com
 * percentual "antes" e "depois" do marco; no nivel 2 cada coluna soma 100%.
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
        $participante_percentual_depois = new TEntry('participante_percentual_depois[]');
        $participante_recebe_diferenca = new TCombo('participante_recebe_diferenca[]');

        $this->fieldList_participantes = new TFieldList();
        $this->fieldList_participantes->addField(null, $participante_id, []);
        $this->fieldList_participantes->addField(null, $participante___row__id, ['uniqid' => true]);
        $this->fieldList_participantes->addField(null, $participante___row__data, []);
        $this->fieldList_participantes->addField(new TLabel("Pessoa", null, '14px', null), $participante_pessoa_id, ['width' => '34%']);
        $this->fieldList_participantes->addField(new TLabel("Nível", null, '14px', null), $participante_nivel, ['width' => '14%']);
        $this->fieldList_participantes->addField(new TLabel("Percentual (%)", null, '14px', null), $participante_percentual, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Antes do marco (%)", null, '14px', null), $participante_percentual_antes, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Depois do marco (%)", null, '14px', null), $participante_percentual_depois, ['width' => '12%']);
        $this->fieldList_participantes->addField(new TLabel("Recebe a diferença", null, '14px', null), $participante_recebe_diferenca, ['width' => '12%']);

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
        ]);
        $participante_nivel->setDefaultOption('Selecione');
        $participante_recebe_diferenca->addItems(['S' => 'Sim']);
        $participante_recebe_diferenca->setDefaultOption('Não');
        foreach ([$participante_percentual, $participante_percentual_antes, $participante_percentual_depois] as $campo_pct)
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
        $participante_percentual_depois->setSize('100%');
        $participante_recebe_diferenca->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Tipo:", '#ff0000', '14px', null, '100%'),$tipo],[new TLabel("Ativo:", null, '14px', null, '100%'),$ativo]);
        $row1->layout = [' col-sm-2',' col-sm-5',' col-sm-3',' col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("Primeiro marco (nível 1 até esta data):", null, '14px', null, '100%'),$marco1],[new TLabel("Segundo marco (nível 2 até esta data):", null, '14px', null, '100%'),$marco2]);
        $row2->layout = [' col-sm-4',' col-sm-4'];

        // participantes: so pessoas ja cadastradas (o cadastro de parceiros e feito na tela de Parceiros)

        $row3 = $this->form->addFields([new TFormSeparator("Participantes", '#333', '18', '#eee')]);
        $row3->layout = [' col-sm-12'];

        $ajuda = new TElement('div');
        $ajuda->id = 'ajuda_participantes';
        $ajuda->style = 'color:#666; font-size:12px; margin-bottom:8px;';
        $ajuda->add('');
        $soma = new TElement('div');
        $soma->id = 'soma_participantes';
        $soma->style = 'font-weight:bold; font-size:12px; margin-bottom:8px;';
        $row4 = $this->form->addFields([$ajuda, $soma]);
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
        // Posicao na lista: 4 pessoa, 5 nivel, 6 percentual, 7 antes, 8 depois, 9 diferenca (1 a 3 sao campos ocultos).
        $f = "#" . self::$formName;
        $t = "table.tfieldlist > * > tr > ";
        $seletores = ["{$f}.pl-fixa {$t}:nth-child(5)", "{$f}.pl-fixa {$t}:nth-child(7)", "{$f}.pl-fixa {$t}:nth-child(8)", "{$f}.pl-temporal {$t}:nth-child(6)"];
        parent::add(TElement::tag('style', implode(', ', $seletores) . ' { display: none !important; }'));

        // soma dos percentuais, ao digitar (a regra de 100% e a do servico; aqui e so para o usuario conferir)
        TScript::create("
            window.plSomar = function () {
                var f = $('{$f}'), n = function (v) { v = String(v || '0'); if (v.indexOf(',') >= 0) { v = v.replace(/\./g, '').replace(',', '.'); } return parseFloat(v) || 0; };
                var tot = {fixo: 0, a1: 0, d1: 0, a2: 0, d2: 0};
                f.find('table.tfieldlist tbody tr').each(function () {
                    var r = $(this), nivel = r.find('[name=\"participante_nivel[]\"]').val();
                    var v = function (c) { return n(r.find('[name=\"participante_' + c + '[]\"]').val()); };
                    if (f.hasClass('pl-temporal')) {
                        if (nivel === 'MARCO1') { tot.a1 += v('percentual_antes'); tot.d1 += v('percentual_depois'); }
                        else { tot.a2 += v('percentual_antes'); tot.d2 += v('percentual_depois'); }
                    } else { tot.fixo += v('percentual'); }
                });
                var t = function (x) { return (Math.round(x * 10000) / 10000).toLocaleString('pt-BR', {maximumFractionDigits: 4}) + '%'; };
                var cor = function (x) { return Math.abs(x - 100) < 0.000001 ? '#2e7d32' : '#c62828'; };
                var h = f.hasClass('pl-temporal')
                    ? 'Nível 2: antes <span style=\"color:' + cor(tot.a2) + '\">' + t(tot.a2) + '</span>, depois <span style=\"color:' + cor(tot.d2) + '\">' + t(tot.d2) + '</span> (cada coluna deve somar 100%) | Nível 1: antes ' + t(tot.a1) + ', depois ' + t(tot.d1)
                    : 'Soma: <span style=\"color:' + cor(tot.fixo) + '\">' + t(tot.fixo) + '</span> (deve ser 100%)';
                $('#soma_participantes').html(h);
            };
            $(document).off('input.plpct').on('input.plpct', '{$f} table.tfieldlist input[name^=\"participante_percentual\"]', function () { var v = this.value.replace(/[^0-9,.]/g, ''); if (v !== this.value) { this.value = v; } });
            $(document).off('click.plsoma').on('click.plsoma', '{$f} table.tfieldlist .fa-times', function () { setTimeout(window.plSomar, 400); });
            $(document).off('input.plsoma change.plsoma keyup.plsoma').on('input.plsoma change.plsoma keyup.plsoma', '{$f} table.tfieldlist :input', function () { window.plSomar(); });
        ");
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
            ? 'Por tempo: informe os participantes dos níveis 1 e 2. No nível 2, as colunas "antes" e "depois" do marco devem somar 100% cada. Marque quem recebe a diferença de centavos.'
            : 'Fixa: os percentuais devem somar 100%. Marque quem recebe a diferença de centavos.';

        TScript::create("setTimeout(function () { $('#" . self::$formName . "').removeClass('pl-fixa pl-temporal').addClass('{$classe}'); $('#ajuda_participantes').text(" . json_encode($ajuda) . "); if (window.plSomar) { window.plSomar(); } }, 250);");
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
            $depois = self::numero($l['percentual_depois'] ?? null, $rotulo);

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
            if (!$fixa && ($antes === null || $depois === null))
            {
                throw new Exception("Informe os percentuais \"antes\" e \"depois\" do marco de {$rotulo} (use 0 se não participa).");
            }

            $saida[] = [
                'pessoa_id'         => (int) $pessoa,
                'nivel'             => $fixa ? ParceriaLevantamentoParticipante::NIVEL_FIXO : ($l['nivel'] ?? ParceriaLevantamentoParticipante::NIVEL_FIXO),
                'percentual'        => $fixa ? $pct : null,
                'percentual_antes'  => $fixa ? null : $antes,
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

            // apaga e regrava os participantes (a ficha dos financeiros ja gerados guarda os percentuais da epoca)
            ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $object->id)->delete();

            foreach ($participantes as $ordem => $p)
            {
                $novo = new ParceriaLevantamentoParticipante();
                $novo->parceria_levantamento_id = $object->id;
                $novo->pessoa_id          = $p['pessoa_id'];
                $novo->nivel              = $p['nivel'];
                $novo->percentual         = $p['percentual'];
                $novo->percentual_antes   = $p['percentual_antes'];
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
