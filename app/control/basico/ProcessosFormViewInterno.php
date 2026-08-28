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

        if (empty($param['key']) && !empty($param['processo_id']))
        {
            $conn = TTransaction::get();

            $processo_id = (int) $param['processo_id'];

            $result = $conn->query("
                SELECT pe.id AS pessoa_id
                FROM processo p
                JOIN contrato_processo cp
                    ON cp.processo_id = p.id
                JOIN contrato_pessoa cpe
                    ON cpe.contrato_id = cp.contrato_id
                JOIN pessoa pe
                    ON pe.id = cpe.cliente_id
                WHERE p.id = {$processo_id}
                ORDER BY pe.id
                LIMIT 1
            ");

            $dados = $result->fetch(PDO::FETCH_OBJ);

            if (empty($dados) || empty($dados->pessoa_id))
            {
                throw new Exception('Não foi possível localizar o cliente vinculado a este processo.');
            }

            $param['key'] = $dados->pessoa_id;
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

        $etapas_ids = [];
        $etapa_atual_id = null;
        $ordem_etapa_atual = null;

        $etapas_fixas = [8, 2];
        $etapas_ocultas = [1, 10];

        if ($processo_id > 0)
        {
            $conn = TTransaction::get();

            /*
                Busca TODAS as etapas que realmente apareceram nas publicações.
                Essas etapas vão aparecer e ficar pintadas, desde que estejam antes ou sejam a etapa atual.
            */
            $sql_etapas_aparecidas = "
                SELECT 
                    pe.id,
                    pe.ordem_prioridade
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

                WHERE pp.processo_id = :processo_id

                AND COALESCE(
                    pp.publicacao_etapa_id,
                    pub.publicacao_etapa_id,
                    andam.publicacao_etapa_id
                ) IS NOT NULL

                AND pe.id NOT IN (1, 10)

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

                GROUP BY
                    pe.id,
                    pe.ordem_prioridade

                ORDER BY
                    pe.ordem_prioridade ASC,
                    pe.id ASC
            ";

            $stmt_aparecidas = $conn->prepare($sql_etapas_aparecidas);
            $stmt_aparecidas->execute([
                ':processo_id' => $processo_id
            ]);

            $etapas_aparecidas = $stmt_aparecidas->fetchAll(PDO::FETCH_OBJ);

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
                    $ordem_etapa = (int) $etapa->ordem_prioridade;

                    if (!in_array($id_etapa, $etapas_ocultas))
                    {
                        $etapas_ids[] = $id_etapa;
                    }

                    /*
                        Pega a maior etapa pela ordem_prioridade.
                        Essa será o setValue() do ArrowStep.
                    */
                    if (!in_array($id_etapa, $etapas_fixas))
                    {
                        if ($ordem_etapa_atual === null || $ordem_etapa > $ordem_etapa_atual)
                        {
                            $etapa_atual_id = $id_etapa;
                            $ordem_etapa_atual = $ordem_etapa;
                        }
                    }
                }
            }

            /*
                Se não encontrou nenhuma etapa dinâmica, usa Protocolo Inicial como base.
            */
            if ($ordem_etapa_atual === null)
            {
                $sql_ordem_base = "
                    SELECT ordem_prioridade
                    FROM publicacao_etapa
                    WHERE id = 2
                    LIMIT 1
                ";

                $ordem_etapa_atual = (int) $conn->query($sql_ordem_base)->fetchColumn();
                $etapa_atual_id = 2;
            }

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
                AND pe.id NOT IN (8, 2)
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
            "CASE 
                WHEN id = 8 THEN 0
                WHEN id = 2 THEN 1
                ELSE 2
            END, ordem_prioridade ASC, id ASC",
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

        $processo_view->setId('processo_publicacoes_timeline_container');
        $processo_view->setSize('100%');

        $action_timeline = new TAction(['ProcessoPublicacoesTimeLine', 'onShow']);
        $action_timeline->setParameter('processo_id', $param['processo_id'] ?? '');
        $action_timeline->setParameter('target_container', 'processo_publicacoes_timeline_container');

        $processo_view->setAction($action_timeline);

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processo_view->add($loadingContainer);

        $row1 = $this->form->addFields([$publicacao_etapa_id]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$processo_view]);
        $row2->layout = [' col-sm-12'];

        $row1->class = trim(($row1->class ?? '') . ' curciol-etapas-mobile');

        $row2->class = trim(($row2->class ?? '') . ' curciol-timeline-mobile');

        $row1->style = 'margin-left:0; margin-right:0; padding:12px 8px 8px 8px; margin-bottom:10px; background:linear-gradient(180deg,#f8fafc 0%,#ffffff 100%); border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 2px 10px rgba(15,23,42,.05); overflow:hidden;';

        $row2->style = 'margin-left:0; margin-right:0; padding:0; background:transparent; border:0; border-radius:0; overflow:visible; box-shadow:none;';

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
        $container->class = 'form-container curciol-processos-interno-page';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Processos"]));
        }
        $container->add($this->form);

        $style = new TElement('style');
        $style->add('
            @media (max-width: 768px) {
                .curciol-processos-interno-page,
                .curciol-processos-interno-page .tform,
                .curciol-processos-interno-page .panel,
                .curciol-processos-interno-page .panel-body,
                .curciol-processos-interno-page .card,
                .curciol-processos-interno-page .card-body {
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                }

                .curciol-processos-interno-page .panel,
                .curciol-processos-interno-page .card,
                .curciol-processos-interno-page .panel-body,
                .curciol-processos-interno-page .card-body,
                .curciol-processos-interno-page .tform {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }

                .curciol-processos-interno-page .row {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }

                .curciol-processos-interno-page [class*="col-"] {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }

                .curciol-etapas-mobile {
                    position: relative !important;
                    display: block !important;
                    width: calc(100% + 20px) !important;
                    max-width: calc(100% + 20px) !important;
                    margin-left: -10px !important;
                    margin-right: -10px !important;
                    margin-bottom: 10px !important;
                    padding: 38px 8px 10px 8px !important;
                    overflow-x: auto !important;
                    overflow-y: hidden !important;
                    -webkit-overflow-scrolling: touch !important;
                    box-sizing: border-box !important;
                }

                .curciol-etapas-mobile:before {
                    content: "Arraste para ver as próximas etapas";
                    position: absolute;
                    top: 10px;
                    left: 12px;
                    color: #64748b;
                    font-size: 12px;
                    font-weight: 600;
                    z-index: 5;
                }

                .curciol-etapas-mobile::-webkit-scrollbar {
                    height: 6px;
                }

                .curciol-etapas-mobile::-webkit-scrollbar-track {
                    background: #eef2f7;
                    border-radius: 999px;
                }

                .curciol-etapas-mobile::-webkit-scrollbar-thumb {
                    background: #94a3b8;
                    border-radius: 999px;
                }

                .curciol-etapas-mobile > div,
                .curciol-etapas-mobile > [class*="col-"] {
                    width: 820px !important;
                    min-width: 820px !important;
                    max-width: none !important;
                    flex: 0 0 820px !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    box-sizing: border-box !important;
                }

                .curciol-etapas-mobile .arrow-steps,
                .curciol-etapas-mobile .tarrowstep,
                .curciol-etapas-mobile .tdbarrowstep,
                .curciol-etapas-mobile .tstep,
                .curciol-etapas-mobile table,
                .curciol-etapas-mobile ul {
                    width: 100% !important;
                    min-width: 820px !important;
                    max-width: none !important;
                }

                .curciol-etapas-mobile .arrow-steps .step {
                    min-width: 145px !important;
                    max-width: 160px !important;
                    height: 54px !important;
                    padding-top: 10px !important;
                    padding-bottom: 10px !important;
                    font-size: 12px !important;
                    line-height: 1.2 !important;
                    box-sizing: border-box !important;
                }

                .curciol-etapas-mobile .arrow-steps .step span {
                    display: block !important;
                    max-width: 120px !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                }

                .curciol-timeline-mobile {
                    width: calc(100% + 26px) !important;
                    max-width: calc(100% + 26px) !important;
                    margin-left: -13px !important;
                    margin-right: -13px !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                .curciol-timeline-mobile > [class*="col-"] {
                    width: 100% !important;
                    max-width: 100% !important;
                    flex: 0 0 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #processo_publicacoes_timeline_container {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    overflow: visible !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    box-sizing: border-box !important;
                }
            }

            @media (max-width: 390px) {
                .curciol-etapas-mobile {
                    width: calc(100% + 16px) !important;
                    max-width: calc(100% + 16px) !important;
                    margin-left: -8px !important;
                    margin-right: -8px !important;
                }

                .curciol-timeline-mobile {
                    width: calc(100% + 20px) !important;
                    max-width: calc(100% + 20px) !important;
                    margin-left: -10px !important;
                    margin-right: -10px !important;
                }
            }
        ');

        $container->add($style);

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

                WHERE pp.processo_id = :processo_id

                AND COALESCE(
                    pp.publicacao_etapa_id,
                    pub.publicacao_etapa_id,
                    andam.publicacao_etapa_id
                ) IS NOT NULL

                AND pe.id NOT IN (1, 10)

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

