<?php

class ProcessoPublicacoesTimeLine extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'ProcessoPublicacoes';
    private static $primaryKey = 'id';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null )
    {
        try
        {
            parent::__construct();

            TTransaction::open(self::$database);

            if(!empty($param['target_container']))
            {
                $this->adianti_target_container = $param['target_container'];
            }

            $this->timeline = new TTimeline;
            $this->timeline->setItemDatabase(self::$database);
            $this->timelineCriteria = new TCriteria;

            if(!empty($param["processo_id"] ?? ""))
        {
            TSession::setValue(__CLASS__.'load_filter_processo_id', $param["processo_id"] ?? "");
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_processo_id');
            $this->timelineCriteria->add(new TFilter('processo_id', '=', $filterVar));

/*

            $limit = 0;

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'date_log asc');

*/
          $limit = 0; 

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'id desc');

            $processo_id = null;

            if (!empty($param['processo_id']))
            {
                $processo_id = (int) $param['processo_id'];
                TSession::setValue(__CLASS__.'load_filter_processo_id', $processo_id);
            }
            else
            {
                $processo_id = TSession::getValue(__CLASS__.'load_filter_processo_id');
            }

            if (empty($processo_id))
            {
                throw new Exception('Processo não informado');
            }

/*

            $objects = ProcessoPublicacoes::getObjects($this->timelineCriteria);

            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $id = $object->id;
                    $title = "{id}";
                    $htmlTemplate = "{id}";
                    $date = $object->date_log;
                    $icon = 'fa:arrow-right bg-green';
                    $position = 'right';

*/              
                $mobileTimelineHtml = '';

                $objects = ProcessoPublicacoes::getObjects($this->timelineCriteria);

                /* 
                    Compara as datas dentro da Publicação e do Andamento, vendo quais são iguais, por critério de desempate
                    ele considera o ID para desempatar por esse return $dataB <=> $dataA;
                */
                if ($objects)
                {
                    $objects = is_array($objects) ? $objects : iterator_to_array($objects);

                    usort($objects, function($a, $b) {
                        $dataA = 0;
                        $dataB = 0;

                        if (!empty($a->publicacao_id) && empty($a->andamento_id)) {
                            $pubA = Publicacao::find($a->publicacao_id);

                            if ($pubA && !empty($pubA->data_disponibilizacao)) {
                                $dataA = strtotime($pubA->data_disponibilizacao);
                            }
                        }

                        if (!empty($a->andamento_id) && empty($a->publicacao_id)) {
                            $andA = Andamento::find($a->andamento_id);

                            if ($andA && !empty($andA->data_andamento)) {
                                $dataA = strtotime($andA->data_andamento);
                            }
                        }

                        if (!empty($b->publicacao_id) && empty($b->andamento_id)) {
                            $pubB = Publicacao::find($b->publicacao_id);

                            if ($pubB && !empty($pubB->data_disponibilizacao)) {
                                $dataB = strtotime($pubB->data_disponibilizacao);
                            }
                        }

                        if (!empty($b->andamento_id) && empty($b->publicacao_id)) {
                            $andB = Andamento::find($b->andamento_id);

                            if ($andB && !empty($andB->data_andamento)) {
                                $dataB = strtotime($andB->data_andamento);
                            }
                        }

                        if ($dataA === $dataB) {
                            return ($b->id ?? 0) <=> ($a->id ?? 0);
                        }

                        return $dataB <=> $dataA;
                    });

                    foreach ($objects as $object)
                    {
                        $id = $object->id;

                        $publicacao = null;
                        $andamento = null;

                        $eh_publicacao = !empty($object->publicacao_id) && empty($object->andamento_id);
                        $eh_andamento  = !empty($object->andamento_id) && empty($object->publicacao_id);

                        if (!$eh_publicacao && !$eh_andamento)
                        {
                            continue;
                        }

                        $etapa_nome = 'Etapa não informada';
                        $etapa_obs = '-';
                        $data_disponibilizacao = '-';
                        $data_evento = '-';
                        $titulo_publicacao = '-';

                        $etapa_id = null;
                        $date = date('Y-m-d H:i:s');

                        if ($eh_publicacao)
                        {
                            $publicacao = Publicacao::find($object->publicacao_id);

                            if (!$publicacao)
                            {
                                continue;
                            }

                            $etapa_verificada = strtoupper(trim($publicacao->etapa_verificada ?? 'N'));

                            if ($etapa_verificada !== 'S')
                            {
                                continue;
                            }

                            $etapa_id = !empty($object->publicacao_etapa_id)
                                ? (int) $object->publicacao_etapa_id
                                : (int) ($publicacao->publicacao_etapa_id ?? 0);

                            $titulo_publicacao = $publicacao->titulo ?? '-';

                            if (!empty($publicacao->data_disponibilizacao))
                            {
                                $date = $publicacao->data_disponibilizacao;
                                $data_disponibilizacao = date('d/m/Y', strtotime($publicacao->data_disponibilizacao));
                                $data_evento = $data_disponibilizacao;
                            }
                        }

                        if ($eh_andamento)
                        {
                            $andamento = Andamento::find($object->andamento_id);

                            if (!$andamento)
                            {
                                continue;
                            }

                            $etapa_verificada = strtoupper(trim($andamento->etapa_verificada ?? 'N'));

                            if ($etapa_verificada !== 'S')
                            {
                                continue;
                            }

                            $etapa_id = !empty($object->publicacao_etapa_id)
                                ? (int) $object->publicacao_etapa_id
                                : (int) ($andamento->publicacao_etapa_id ?? 0);

                            $titulo_publicacao = '-';

                            if (!empty($andamento->data_andamento))
                            {
                                $date = $andamento->data_andamento;

                                /*
                                    Aqui é o ponto principal:
                                    andamento usa data_andamento, mas aparece no HTML como Disponibilização.
                                */
                                $data_disponibilizacao = date('d/m/Y', strtotime($andamento->data_andamento));
                                $data_evento = $data_disponibilizacao;
                            }
                        }

                        if (empty($etapa_id))
                        {
                            continue;
                        }

                        if (in_array((int) $etapa_id, [1, 10], true))
                        {
                            continue;
                        }

                        $etapa = PublicacaoEtapa::find($etapa_id);

                        if ($etapa)
                        {
                            $etapa_nome = $etapa->etapa_nome;
                            $etapa_obs  = $etapa->descricao ?: '-';
                        }

                        $detailId = 'timeline_detail_' . $object->id;
                        $iconId   = 'timeline_icon_' . $object->id;

                        $etapa_nome_html = htmlspecialchars((string) $etapa_nome, ENT_QUOTES, 'UTF-8');
                        $etapa_obs_html  = nl2br(htmlspecialchars((string) $etapa_obs, ENT_QUOTES, 'UTF-8'));
                        $data_disp_html  = htmlspecialchars((string) $data_disponibilizacao, ENT_QUOTES, 'UTF-8');

                        $descricao_html = $etapa_obs_html;
                        $complemento_bloco_html = '';
                        $mobile_complemento_bloco_html = '';

                        if (!empty(trim((string) ($object->complemento ?? ''))))
                        {
                            $complemento_html = nl2br(htmlspecialchars((string) $object->complemento, ENT_QUOTES, 'UTF-8'));

                            $complemento_bloco_html = "
                                <div class='curciol-timeline-detail-line'>
                                    <b>Complemento:</b>
                                    <span>{$complemento_html}</span>
                                </div>
                            ";

                            $mobile_complemento_bloco_html = "
                                <div class='curciol-mobile-detail-block'>
                                    <div class='curciol-mobile-detail-label'>Complemento</div>
                                    <div class='curciol-mobile-detail-text'>{$complemento_html}</div>
                                </div>
                            ";
                        }
                        $title = "
                            <div 
                                class='curciol-timeline-title-row'
                                style='
                                    display:flex;
                                    align-items:center;
                                    justify-content:space-between;
                                    width:100%;
                                    gap:12px;
                                    box-sizing:border-box;
                                '
                            >
                                <span 
                                    class='curciol-timeline-title'
                                    style='
                                        flex:1;
                                        min-width:0;
                                        font-weight:600;
                                        color:#16325c;
                                        font-size:15px;
                                        line-height:1.25;
                                        white-space:normal;
                                        word-break:normal;
                                        overflow-wrap:break-word;
                                    '
                                >
                                    {$etapa_nome_html}
                                </span>

                                <button
                                    type='button'
                                    class='curciol-timeline-toggle'
                                    onclick=\"
                                        (function() {
                                            var detail = document.getElementById('{$detailId}');
                                            var icon = document.getElementById('{$iconId}');

                                            if (detail.style.display === 'none' || detail.style.display === '') {
                                                detail.style.display = 'block';
                                                icon.innerHTML = '-';
                                            } else {
                                                detail.style.display = 'none';
                                                icon.innerHTML = '+';
                                            }
                                        })();
                                        return false;
                                    \"
                                    style='
                                        flex:0 0 auto;
                                        border:1px solid #cbd5e1;
                                        background:#ffffff;
                                        color:#334155;
                                        border-radius:6px;
                                        width:28px;
                                        height:28px;
                                        cursor:pointer;
                                        font-weight:bold;
                                        line-height:1;
                                        font-size:14px;
                                        box-shadow:0 1px 3px rgba(15,23,42,.08);
                                    '
                                >
                                    <span id='{$iconId}'>+</span>
                                </button>
                            </div>
                        ";

                       $htmlTemplate = "
                        <div id='{$detailId}' class='curciol-timeline-detail' style='display:none;'>
                            <div class='curciol-timeline-detail-line'>
                                <b>Descrição:</b>
                                <span>{$descricao_html}</span>
                            </div>

                            {$complemento_bloco_html}

                            <div class='curciol-timeline-detail-line'>
                                <b>Disponibilização:</b>
                                <span>{$data_disp_html}</span>
                            </div>
                        </div>
                    ";

                        $mobileDetailId = 'mobile_timeline_detail_' . $object->id;
                        $mobileIconId   = 'mobile_timeline_icon_' . $object->id;

                        $mobileTimelineHtml .= "
                            <div class='curciol-mobile-timeline-item'>
                                <div class='curciol-mobile-timeline-date'>
                                    {$data_disp_html}
                                </div>

                                <div class='curciol-mobile-timeline-card'>
                                    <div class='curciol-mobile-timeline-header'>
                                        <div class='curciol-mobile-timeline-icon'>
                                            <i class='fas fa-gavel'></i>
                                        </div>

                                        <div class='curciol-mobile-timeline-title'>
                                            {$etapa_nome_html}
                                        </div>

                                        <button
                                            type='button'
                                            class='curciol-mobile-timeline-toggle'
                                            onclick=\"
                                                (function() {
                                                    var detail = document.getElementById('{$mobileDetailId}');
                                                    var icon = document.getElementById('{$mobileIconId}');

                                                    if (detail.style.display === 'none' || detail.style.display === '') {
                                                        detail.style.display = 'block';
                                                        icon.innerHTML = '-';
                                                    } else {
                                                        detail.style.display = 'none';
                                                        icon.innerHTML = '+';
                                                    }
                                                })();
                                                return false;
                                            \"
                                        >
                                            <span id='{$mobileIconId}'>+</span>
                                        </button>
                                    </div>

                                    <div id='{$mobileDetailId}' class='curciol-mobile-timeline-detail' style='display:none;'>
                                       <div class='curciol-mobile-detail-block'>
                                            <div class='curciol-mobile-detail-label'>Descrição</div>
                                            <div class='curciol-mobile-detail-text'>{$descricao_html}</div>
                                        </div>

                                        {$mobile_complemento_bloco_html}

                                        <div class='curciol-mobile-detail-block'>
                                            <div class='curciol-mobile-detail-label'>Disponibilização</div>
                                            <div class='curciol-mobile-detail-text'>{$data_disp_html}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ";

                        $icon = 'fas:gavel bg-blue';
                        $position = 'left';

                    $this->timeline->addItem($id, $title, $htmlTemplate, $date, $icon, $position, $object);

                }
            }

            $this->timeline->setTimeDisplayMask('dd/mm/yyyy');
            $this->timeline->setFinalIcon( 'fas:flag-checkered #ffffff #de1414' );

            $container = new TVBox;

            $titulo = new TLabel('Andamento das Publicações do Processo');
            $titulo->style = 'font-size:16px; font-weight:700; margin:0 0 10px 0; padding:0; display:block; width:100%; max-width:100%; white-space:normal; word-break:normal; line-height:1.3; color:#0f172a; box-sizing:border-box;';
            $container->add($titulo);

            $container->style = 'width:100%; max-width:100%; overflow-x:hidden; box-sizing:border-box; padding:0; margin:0; background:transparent;';
            $container->class = 'form-container curciol-timeline-page';

            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }

            $desktopTimeline = new TElement('div');
            $desktopTimeline->class = 'curciol-desktop-timeline-wrapper';
            $desktopTimeline->add($this->timeline);
            $container->add($desktopTimeline);

            $mobileTimeline = new TElement('div');
            $mobileTimeline->class = 'curciol-mobile-timeline-wrapper';

            if (!empty($mobileTimelineHtml))
            {
                $mobileTimeline->add($mobileTimelineHtml);
            }
            else
            {
                $mobileTimeline->add('<div class="curciol-mobile-empty">Nenhum andamento encontrado.</div>');
            }

            $container->add($mobileTimeline);

            $style = new TElement('style');
            $style->add('
                .curciol-desktop-timeline-wrapper {
                    display: block;
                }

                .curciol-mobile-timeline-wrapper {
                    display: none;
                }

                .curciol-timeline-page {
                    background: transparent;
                    border-radius: 0;
                    color: #1E2843;
                    font-family: Helvetica, Arial, sans-serif !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    box-sizing: border-box !important;
                }

                .curciol-timeline-title-row,
                .curciol-timeline-title,
                .curciol-timeline-detail,
                .curciol-timeline-detail-line,
                .curciol-timeline-detail-line b,
                .curciol-timeline-detail-line span {
                    font-family: Helvetica, Arial, sans-serif !important;
                    box-sizing: border-box !important;
                }

                .curciol-timeline-page i,
                .curciol-timeline-page .fa,
                .curciol-timeline-page .fas,
                .curciol-timeline-page .far {
                    font-family: FontAwesome, "Font Awesome 5 Free" !important;
                    font-weight: 900 !important;
                }

                .curciol-timeline-title-row {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    box-sizing: border-box;
                }

                .curciol-timeline-title {
                    flex: 1;
                    min-width: 0;
                    font-weight: 600;
                    color: #1E2843;
                    font-size: 15px;
                    line-height: 1.25;
                    white-space: normal;
                    word-break: normal;
                    overflow-wrap: break-word;
                }

                .curciol-timeline-toggle {
                    flex: 0 0 auto;
                    border: 1px solid #d4dbe5;
                    background: #ffffff;
                    color: #0D4069;
                    border-radius: 8px;
                    width: 30px;
                    height: 30px;
                    cursor: pointer;
                    font-weight: 600;
                    line-height: 1;
                    box-shadow: 0 2px 5px rgba(30, 40, 67, .10);
                }

                .curciol-timeline-detail {
                    padding: 14px 16px 16px 16px;
                    text-align: left;
                    border-top: 1px solid #e5e9ef;
                    box-sizing: border-box;
                    background: #ffffff;
                }

                .curciol-timeline-detail-line {
                    display: block;
                    font-size: 14px;
                    color: #465266;
                    line-height: 1.55;
                    margin-bottom: 14px;
                    word-break: normal;
                    overflow-wrap: break-word;
                    white-space: normal;
                }

                .curciol-timeline-detail-line:last-child {
                    margin-bottom: 0;
                }

                .curciol-timeline-detail-line b {
                    display: block;
                    color: #0D4069;
                    font-size: 11px;
                    font-weight: 600;
                    line-height: 1.2;
                    text-transform: uppercase;
                    letter-spacing: .55px;
                    margin: 0 0 5px 0;
                }

                .curciol-timeline-detail-line span {
                    display: block;
                    color: #465266;
                    font-size: 14px;
                    font-weight: 300;
                    line-height: 1.55;
                }

            @media (max-width: 768px) {
                .curciol-desktop-timeline-wrapper {
                    display: none !important;
                }

                .curciol-mobile-timeline-wrapper {
                    display: block !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    position: relative !important;
                    box-sizing: border-box !important;
                }

                .curciol-timeline-page {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border-radius: 0 !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                .curciol-timeline-page > span,
                .curciol-timeline-page > label {
                    display: block !important;
                    width: 100% !important;
                    margin: 0 0 10px 0 !important;
                    padding: 0 2px !important;
                    color: #1E2843 !important;
                    font-size: 14px !important;
                    font-weight: 700 !important;
                    line-height: 1.3 !important;
                    white-space: normal !important;
                    box-sizing: border-box !important;
                }

                .curciol-mobile-timeline-item {
                    position: relative !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    padding-left: 26px !important;
                    margin: 0 0 12px 0 !important;
                    box-sizing: border-box !important;
                }

                .curciol-mobile-timeline-item:before {
                    content: "";
                    position: absolute;
                    left: 9px !important;
                    top: 0;
                    bottom: -12px;
                    width: 3px;
                    background: #d7dee8;
                    border-radius: 999px;
                }

                .curciol-mobile-timeline-date {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 0 6px 0;
                    padding: 5px 10px;
                    border-radius: 999px;
                    background: #eef4fb;
                    color: #0D4069;
                    font-size: 12px;
                    font-weight: 600;
                    line-height: 1;
                    letter-spacing: .2px;
                }

                .curciol-mobile-timeline-card {
                    position: relative !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #ffffff;
                    border: 1px solid #dfe5ee;
                    border-left: 4px solid #0D4069;
                    border-radius: 12px;
                    box-shadow: 0 3px 10px rgba(30, 40, 67, .08);
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                .curciol-mobile-timeline-header {
                    width: 100%;
                    min-height: 56px;
                    padding: 11px 10px !important;
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) 32px;
                    gap: 8px;
                    align-items: center;
                    box-sizing: border-box;
                    background: #ffffff;
                    border-radius: 12px;
                }

                .curciol-mobile-timeline-icon {
                    position: absolute !important;

                    /* card começa depois do padding-left do item.
                    Esse left centraliza o círculo exatamente na linha cinza. */
                    left: -28px !important;

                    top: 50% !important;
                    transform: translateY(-50%) !important;

                    width: 22px !important;
                    height: 22px !important;
                    min-width: 22px !important;

                    border-radius: 50% !important;
                    background: #0D4069 !important;
                    color: #ffffff !important;

                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;

                    font-size: 9px !important;
                    z-index: 5 !important;
                    box-shadow: 0 3px 8px rgba(13, 64, 105, .22) !important;
                }

                .curciol-mobile-timeline-icon i,
                .curciol-mobile-timeline-icon .fa,
                .curciol-mobile-timeline-icon .fas {
                    font-family: FontAwesome, "Font Awesome 5 Free" !important;
                    font-weight: 900 !important;
                    color: #ffffff !important;
                    font-size: 9px !important;
                    line-height: 1 !important;
                    transform: translateY(.5px) !important;
                }
                .curciol-mobile-timeline-title {
                    color: #1E2843;
                    font-size: 15px;
                    font-weight: 700;
                    line-height: 1.22;
                    white-space: normal !important;
                    word-break: normal !important;
                    overflow-wrap: break-word !important;
                    hyphens: none !important;
                    min-width: 0;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .curciol-mobile-timeline-toggle {
                    width: 32px;
                    height: 32px;
                    min-width: 32px;
                    padding: 0;
                    border: 1px solid #d4dbe5;
                    border-radius: 9px;
                    background: #ffffff;
                    color: #0D4069;
                    font-size: 17px;
                    font-weight: 600;
                    line-height: 1;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 5px rgba(30, 40, 67, .10);
                }

                .curciol-mobile-timeline-detail {
                    border-top: 1px solid #e5e9ef;
                    padding: 12px 12px 14px 12px;
                    background: #ffffff;
                    box-sizing: border-box;
                    border-radius: 0 0 12px 12px;
                }

                .curciol-mobile-detail-block {
                    margin-bottom: 14px;
                }

                .curciol-mobile-detail-block:last-child {
                    margin-bottom: 0;
                }

                .curciol-mobile-detail-label {
                    display: block;
                    color: #0D4069;
                    font-size: 11px;
                    font-weight: 600;
                    line-height: 1.2;
                    text-transform: uppercase;
                    letter-spacing: .55px;
                    margin: 0 0 5px 0;
                }

                .curciol-mobile-detail-text {
                    color: #465266;
                    font-size: 14px;
                    font-weight: 300;
                    line-height: 1.55;
                    white-space: normal !important;
                    word-break: normal !important;
                    overflow-wrap: break-word !important;
                    hyphens: none !important;
                }

                .curciol-mobile-detail-text b {
                    color: #0D4069;
                    font-weight: 600;
                }

                .curciol-mobile-empty {
                    width: 100%;
                    padding: 14px;
                    border-radius: 12px;
                    background: #ffffff;
                    border: 1px solid #dfe5ee;
                    color: #465266;
                    font-size: 13px;
                    box-sizing: border-box;
                }
            }

            @media (max-width: 390px) {
               .curciol-mobile-timeline-item {
                    padding-left: 24px !important;
                }

                .curciol-mobile-timeline-item:before {
                    left: 8px !important;
                }

                .curciol-mobile-timeline-icon {
                    left: -26px !important;
                    top: 50% !important;
                    transform: translateY(-50%) !important;
                    width: 20px !important;
                    height: 20px !important;
                    min-width: 20px !important;
                    font-size: 8px !important;
                }

                .curciol-mobile-timeline-icon i,
                    .curciol-mobile-timeline-icon .fa,
                    .curciol-mobile-timeline-icon .fas {
                        font-size: 8px !important;
                        transform: translateY(.5px) !important;
                    }

               .curciol-mobile-timeline-header {
                    position: relative !important;
                    width: 100%;
                    min-height: 56px;
                    padding: 11px 10px !important;
                    display: grid;
                    grid-template-columns: minmax(0, 1fr) 32px;
                    gap: 8px;
                    align-items: center;
                    box-sizing: border-box;
                    background: #ffffff;
                    border-radius: 12px;
                }

                .curciol-mobile-timeline-title {
                    font-size: 14px;
                }

                .curciol-mobile-timeline-toggle {
                    width: 30px;
                    height: 30px;
                    min-width: 30px;
                    font-size: 16px;
                }

                .curciol-mobile-detail-text {
                    font-size: 13.5px;
                    line-height: 1.55;
                }
            }
            ');

            $container->add($style);

             //<onAfterAddTimeline>
/*

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }
            $container->add($this->timeline);

/*

            //</onBeforeAddTimeline>

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }
            $container->add($this->timeline);

            //<onAfterAddTimeline>

/*

            //</onBeforeAddTimeline>

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }
            $container->add($this->timeline);

            //<onAfterAddTimeline>

/*

            //</onBeforeAddTimeline>

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }
            $container->add($this->timeline);

            //<onAfterAddTimeline>

/*
            //</onBeforeAddTimeline>

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Processos","ProcessoPublicacoesTimeLine"]));
            }
            $container->add($this->timeline);

            //<onAfterAddTimeline>
*/

            TTransaction::close();

            parent::add($container);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {

    } 

}

