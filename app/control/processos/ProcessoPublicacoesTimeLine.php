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

            $processo = Processo::find($processo_id);

            if (!$processo)
            {
                throw new Exception('Processo não encontrado');
            }

            $tipo_processo_id = (int) $processo->tipo_processo_id;

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
                        $etapa_detalhamento = '';
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

                        if (in_array((int) $etapa_id, [1, 10, 8], true))
                        {
                            continue;
                        }

                        $etapa = PublicacaoEtapa::find($etapa_id);

                        if (!$etapa)
                        {
                            continue;
                        }

                        /*
                        * Respeita o tipo do processo.
                        *
                        * 1 = Judicial
                        * 2 = Extrajudicial
                        */
                        if ($tipo_processo_id === 1)
                        {
                            $permite_judicial = strtoupper(trim((string) ($etapa->judicial ?? 'N')));

                            if ($permite_judicial !== 'S')
                            {
                                continue;
                            }
                        }
                        elseif ($tipo_processo_id === 2)
                        {
                            $permite_extrajudicial = strtoupper(trim((string) ($etapa->extrajudicial ?? 'N')));

                            if ($permite_extrajudicial !== 'S')
                            {
                                continue;
                            }
                        }

                        $etapa_nome = $etapa->etapa_nome;
                        $etapa_obs  = $etapa->descricao ?: '-';
                        $etapa_detalhamento = $etapa->detalhamento ?: '';

                        $detailId = 'timeline_detail_' . $object->id;
                        $iconId   = 'timeline_icon_' . $object->id;

                        $etapa_nome_html = htmlspecialchars((string) $etapa_nome, ENT_QUOTES, 'UTF-8');
                        $etapa_obs_html  = nl2br(htmlspecialchars((string) $etapa_obs, ENT_QUOTES, 'UTF-8'));
                        $detalhamento_html = nl2br(htmlspecialchars((string) $etapa_detalhamento, ENT_QUOTES, 'UTF-8'));
                        $data_disp_html  = htmlspecialchars((string) $data_disponibilizacao, ENT_QUOTES, 'UTF-8');

                        $descricao_html = $etapa_obs_html;
                        $complemento_bloco_html = '';
                        $mobile_complemento_bloco_html = '';
                        $detalhamento_bloco_html = '';
                        $mobile_detalhamento_bloco_html = '';

                        if (!empty(trim((string) $etapa_detalhamento)))
                        {
                            $detalhamento_bloco_html = "
                                <div class='curciol-timeline-detail-line'>
                                    <b>Detalhamento:</b>
                                    <span>{$detalhamento_html}</span>
                                </div>
                            ";

                            $mobile_detalhamento_bloco_html = "
                                <div class='curciol-mobile-detail-block'>
                                    <div class='curciol-mobile-detail-label'>Detalhamento</div>
                                    <div class='curciol-mobile-detail-text'>{$detalhamento_html}</div>
                                </div>
                            ";
                        }

                        if (!empty(trim((string) ($object->complemento ?? ''))))
                        {
                            $complemento_html = nl2br(htmlspecialchars((string) $object->complemento, ENT_QUOTES, 'UTF-8'));

                            $complemento_bloco_html = "
                                <div class='curciol-timeline-detail-line'>
                                    <b>Informações Adicionais:</b>
                                    <span>{$complemento_html}</span>
                                </div>
                            ";

                            $mobile_complemento_bloco_html = "
                                <div class='curciol-mobile-detail-block'>
                                    <div class='curciol-mobile-detail-label'>Informações Adicionais</div>
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
                            {$detalhamento_bloco_html}

                            <div class='curciol-timeline-detail-line'>
                                <b>O que acontece nesta etapa?</b>
                                <span>{$descricao_html}</span>
                            </div>

                            {$complemento_bloco_html}
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
                                        {$mobile_detalhamento_bloco_html}

                                       <div class='curciol-mobile-detail-block'>
                                            <div class='curciol-mobile-detail-label'>O que acontece nesta etapa?</div>
                                            <div class='curciol-mobile-detail-text'>{$descricao_html}</div>
                                        </div>

                                        {$mobile_complemento_bloco_html}
                                    </div>
                                </div>
                            </div>
                        ";

                        $icon = 'fas:gavel bg-blue';
                        $position = 'left';

                    $this->timeline->addItem($id, $title, $htmlTemplate, $date, $icon, $position, $object);

                }
            }

            /*
                Organização Documental é sempre a seção mais antiga da timeline.
                Ela não nasce de uma publicação: é montada a partir do cadastro
                da etapa 8, por isso entra depois do laço, no fim da lista.
            */
            $etapa_org_doc = PublicacaoEtapa::find(8);

            if ($etapa_org_doc)
            {
                $permite_org_doc = true;

                if ($tipo_processo_id === 1)
                {
                    $permite_org_doc = strtoupper(trim((string) ($etapa_org_doc->judicial ?? 'N'))) === 'S';
                }
                elseif ($tipo_processo_id === 2)
                {
                    $permite_org_doc = strtoupper(trim((string) ($etapa_org_doc->extrajudicial ?? 'N'))) === 'S';
                }

                if ($permite_org_doc)
                {
                    /*
                        A data vem da distribuição do processo. Quando não houver,
                        a seção aparece sem data em vez de exibir um valor inventado.
                    */
                    $org_doc_date = date('Y-m-d');
                    $org_doc_data_html = '';

                    if (!empty($processo->data_distribuicao_protocolo))
                    {
                        $org_doc_date = $processo->data_distribuicao_protocolo;
                        $org_doc_data_html = htmlspecialchars(
                            date('d/m/Y', strtotime($processo->data_distribuicao_protocolo)),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    }
                    elseif (!empty($date))
                    {
                        $org_doc_date = $date;
                    }

                    $org_doc_nome_html = htmlspecialchars(
                        (string) ($etapa_org_doc->etapa_nome ?: 'Organização Documental'),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $org_doc_desc_html = nl2br(htmlspecialchars(
                        (string) ($etapa_org_doc->descricao ?: '-'),
                        ENT_QUOTES,
                        'UTF-8'
                    ));

                    $org_doc_detalhamento = (string) ($etapa_org_doc->detalhamento ?? '');

                    $org_doc_det_html = nl2br(htmlspecialchars($org_doc_detalhamento, ENT_QUOTES, 'UTF-8'));

                    $org_doc_det_bloco = '';
                    $org_doc_det_bloco_mobile = '';

                    if (!empty(trim($org_doc_detalhamento)))
                    {
                        $org_doc_det_bloco = "
                            <div class='curciol-timeline-detail-line'>
                                <b>Detalhamento:</b>
                                <span>{$org_doc_det_html}</span>
                            </div>
                        ";

                        $org_doc_det_bloco_mobile = "
                            <div class='curciol-mobile-detail-block'>
                                <div class='curciol-mobile-detail-label'>Detalhamento</div>
                                <div class='curciol-mobile-detail-text'>{$org_doc_det_html}</div>
                            </div>
                        ";
                    }

                    $orgDocDetailId = 'timeline_detail_org_doc';
                    $orgDocIconId   = 'timeline_icon_org_doc';

                    $org_doc_title = "
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
                                {$org_doc_nome_html}
                            </span>

                            <button
                                type='button'
                                class='curciol-timeline-toggle'
                                onclick=\"
                                    (function() {
                                        var detail = document.getElementById('{$orgDocDetailId}');
                                        var icon = document.getElementById('{$orgDocIconId}');

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
                                <span id='{$orgDocIconId}'>+</span>
                            </button>
                        </div>
                    ";

                    $org_doc_template = "
                        <div id='{$orgDocDetailId}' class='curciol-timeline-detail' style='display:none;'>
                            {$org_doc_det_bloco}

                            <div class='curciol-timeline-detail-line'>
                                <b>O que acontece nesta etapa?</b>
                                <span>{$org_doc_desc_html}</span>
                            </div>
                        </div>
                    ";

                    $mobileOrgDocDetailId = 'mobile_timeline_detail_org_doc';
                    $mobileOrgDocIconId   = 'mobile_timeline_icon_org_doc';

                    $org_doc_data_bloco_mobile = '';

                    if (!empty($org_doc_data_html))
                    {
                        $org_doc_data_bloco_mobile = "
                            <div class='curciol-mobile-timeline-date'>
                                {$org_doc_data_html}
                            </div>
                        ";
                    }

                    $mobileTimelineHtml .= "
                        <div class='curciol-mobile-timeline-item curciol-mobile-timeline-item-origem'>
                            {$org_doc_data_bloco_mobile}

                            <div class='curciol-mobile-timeline-card'>
                                <div class='curciol-mobile-timeline-header'>
                                    <div class='curciol-mobile-timeline-icon'>
                                        <i class='fas fa-folder-open'></i>
                                    </div>

                                    <div class='curciol-mobile-timeline-title'>
                                        {$org_doc_nome_html}
                                    </div>

                                    <button
                                        type='button'
                                        class='curciol-mobile-timeline-toggle'
                                        onclick=\"
                                            (function() {
                                                var detail = document.getElementById('{$mobileOrgDocDetailId}');
                                                var icon = document.getElementById('{$mobileOrgDocIconId}');

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
                                        <span id='{$mobileOrgDocIconId}'>+</span>
                                    </button>
                                </div>

                                <div id='{$mobileOrgDocDetailId}' class='curciol-mobile-timeline-detail' style='display:none;'>
                                    {$org_doc_det_bloco_mobile}

                                    <div class='curciol-mobile-detail-block'>
                                        <div class='curciol-mobile-detail-label'>O que acontece nesta etapa?</div>
                                        <div class='curciol-mobile-detail-text'>{$org_doc_desc_html}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ";

                    $this->timeline->addItem(
                        'org_doc',
                        $org_doc_title,
                        $org_doc_template,
                        $org_doc_date,
                        'fas:folder-open bg-blue',
                        'left'
                    );
                }
            }
            $this->timeline->setTimeDisplayMask('dd/mm/yyyy');
            $this->timeline->setFinalIcon( 'fas:flag-checkered #ffffff #de1414' );

            $container = new TVBox;

            $titulo = new TElement('span');
            $titulo->class = 'curciol-secao-titulo';
            $titulo->add('Andamento das publicações do processo');
            $container->add($titulo);

            $container->style = 'width:100%; max-width:100%; overflow-x:hidden; box-sizing:border-box; padding:0; margin:0; background:transparent;';
            $container->class = 'form-container curciol-portal curciol-timeline-page';

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

            /*
                A apresentação da timeline vive em
                app/lib/include/css/curciol-portal.css.
            */

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

