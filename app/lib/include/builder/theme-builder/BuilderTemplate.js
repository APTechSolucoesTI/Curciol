window.BuilderTemplate = ( function() {

    var resizeTimeout;

    const resizeBuilderMenu = function() {

        clearTimeout(resizeTimeout);

        resizeTimeout = setTimeout( function() {

            if($(document).width() <= 992) {
                if ($('#top-menu.container-menu').length > 0) {
                    $('#navbar-builder-top-submenu-collapse').insertAfter('#navbar-builder-top-menu-collapse').addClass('builder-top-module-menu-mobile');
                }
                $('#navbar-builder-top-menu-collapse').removeClass("show-arrows");
                return;
            } else {
                $('.header-track').css('display', 'flex');
                $('.builder-top-module-menu-mobile').insertBefore($('#adianti_content')).removeClass('builder-top-module-menu-mobile');
            }

            var topMenu = $('#navbar-builder-top-menu-collapse>#top-menu,#navbar-builder-top-menu-collapse>#top-submenu');

            if(typeof topMenu[0] != 'undefined' && topMenu[0].scrollWidth > topMenu[0].clientWidth ) 
            {
                $('#navbar-builder-top-menu-collapse').addClass("show-arrows");
            } 
            else 
            {
                $('#navbar-builder-top-menu-collapse').removeClass("show-arrows");
            }

            var topSubMenu = $('#navbar-builder-top-submenu-collapse #top-submenu');

            if(typeof topSubMenu[0] != 'undefined' && topSubMenu[0].scrollWidth > topSubMenu[0].clientWidth ) 
            {
                $('#navbar-builder-top-submenu-collapse').addClass("show-arrows");
            } 
            else 
            {
                $('#navbar-builder-top-submenu-collapse').removeClass("show-arrows");
            }
        }, 500);
    }
    
    const clickBuilderMenuOption = function(element) 
    {
        let left = $(element).offset().left;
        $(element).closest('li').find('ul:first').css('left', left  + 'px');
    }
    
    const initTopMenu = function()
    {
        $(document).ready(function () {
            setTimeout(BuilderTemplate.resizeBuilderMenu, 500);

            $(window).on('resize', function(){
                BuilderTemplate.resizeBuilderMenu()
            });

            $('.navbar-custom-menu').remove();

            $('#navbar-builder-top-menu-collapse>.container-submenu>ul>li').on('mouseenter', function(){
                if ($('body').width() > 992) {
                    $(this).find('ul:first').css('left', $(this).offset().left + 'px');
                }
            });
            
            $('.container-menu a[top-menu-target]').on('click', function(){
                const target = $(this).attr('top-menu-target');
                const checked = $(this).hasClass('checked');

                BuilderTemplate.resizeBuilderMenu();

                if ($(`.container-submenu [top-module-menu="${target}"]`).length > 0 && ! checked) {
                    $('#top-submenu.container-submenu').show();

                    $(`.container-submenu [top-module-menu]:not([top-module-menu="${target}"])`).removeClass('open');
                    $(`.container-submenu [top-module-menu="${target}"]`).addClass('open');
            
                    $(`.container-menu a[top-menu-target]:not([top-menu-target="${target}"])`).removeClass('checked');
                    $(`.container-menu a[top-menu-target="${target}"]`).addClass('checked'); 
                } else {
                    $('#top-submenu.container-submenu').hide();
                }

                if (checked) {
                    $(this).removeClass('checked')
                }
            });

            $('#navbar-builder-top-submenu-collapse .container-submenu>ul>li').on('click', function(evt) {
                if (this === evt.target.parentNode) {
                    $('#navbar-builder-top-submenu-collapse .container-submenu ul li').removeClass('checked')
                } else {
                    $('#navbar-builder-top-submenu-collapse .container-submenu>ul>li').removeClass('checked')
                    $(this).find("ul li").each(function(i,e){
                        if (e != evt.target.parentNode) {
                            $(e).removeClass('checked');
                        }
                    });
                }
                
                $(this).addClass('checked');
            });

            $('#navbar-builder-top-menu-collapse .container-submenu ul li').on('click', function(evt) {
                if (this === evt.target.parentNode) {
                    $('#navbar-builder-top-menu-collapse .container-submenu ul li').removeClass('checked')
                    const nativeElement = this;
                    
                    if ($(this).attr('top-module-menu') ) {
                        $(this).closest('ul').find('li').each(function(i, e){
                            if(nativeElement===e) {
                                $(e).addClass('checked');
                            } else {
                                if (this !== evt.target.parentNode) {
                                    $(e).removeClass('checked');
                                }
                            }
                        });
                    } else {
                        $(this).addClass('checked');
                    }
    
                    if (this === evt.target.parentNode) {
                        $(this).addClass('checked');
                    }
                } else {
                    $(this).addClass('checked');
                }
            });

            $('#navbar-builder-top-submenu-collapse li').on('click', function() {
                $(this).addClass('checked');
            });

            $('#navbar-builder-top-menu-collapse .arrow-menus-scroll:last').click(function(e){
                const menu = $('#navbar-builder-top-menu-collapse>#top-menu')[0] ?? $('.container-submenu#top-submenu')[0];
                menu.scrollLeft += 75; 
            });
             
            $('#navbar-builder-top-menu-collapse .arrow-menus-scroll:first').click(function(e){
                const menu = $('#navbar-builder-top-menu-collapse>#top-menu')[0] ?? $('.container-submenu#top-submenu')[0];
                menu.scrollLeft -= 75
            });

            $('#navbar-builder-top-submenu-collapse .arrow-menus-scroll:last').click(function(e){
                $('#navbar-builder-top-submenu-collapse>#top-submenu')[0].scrollLeft += 75; 
            });
             
            $('#navbar-builder-top-submenu-collapse .arrow-menus-scroll:first').click(function(e){
                $('#navbar-builder-top-submenu-collapse>#top-submenu')[0].scrollLeft -= 75
            });
        });
    }
    
    // Curciol (10/11/2026): balao do APChat. Nao redesenha com o mouse em cima (a lista sumia antes do clique): guarda e
    // atualiza quando o mouse sai; e a lista fica aberta 400 ms depois que o mouse sai (atravessar ate um ticket nao fecha).
    let balaoPendente = false;
    let balaoTimer = null;
    const balaoAberto = function() {
        return $('#envelope_messages .fast-drop.apchat-aberto, #envelope_messages .fast-drop:hover').length > 0;
    }
    $(document).on('mouseenter', '#envelope_messages .fast-drop', function() {
        clearTimeout(balaoTimer);
        $(this).addClass('apchat-aberto');
    });
    $(document).on('mouseleave', '#envelope_messages .fast-drop', function() {
        const balao = this;
        clearTimeout(balaoTimer);
        balaoTimer = setTimeout(function() {
            $(balao).removeClass('apchat-aberto');
            if (balaoPendente) { balaoPendente = false; updateMessagesMenu(); }
        }, 400);
    });


    const updateMessagesMenu = function() {
        if (document.hidden || balaoAberto()) { balaoPendente = true; return; }
        $.get('engine.php?class=MessageList&theme=theme-builder', function(data) {
            if (balaoAberto()) { balaoPendente = true; return; }
            $('#envelope_messages').html(data);
        });
    }
    
    // Curciol (10/11/2026): envelope do APMail, a cada 10 s, com as mesmas protecoes do balao do APChat (aba escondida nao
    // pergunta; com o mouse em cima nao redesenha; a lista fica aberta 400 ms depois que o mouse sai). Ao voltar para a aba da
    // Curciol (11/11/2026): atualiza na hora e direto no APMail (fresco), para quem leu ou concluiu um e-mail la.
    let mailPendente = false;
    let mailTimer = null;
    const mailAberto = function() {
        return $('#envelope_mail .fast-drop.apmail-aberto, #envelope_mail .fast-drop:hover').length > 0;
    }
    $(document).on('mouseenter', '#envelope_mail .fast-drop', function() {
        clearTimeout(mailTimer);
        $(this).addClass('apmail-aberto');
    });

    // Curciol (11/11/2026): um balao por vez. Ao passar o mouse em qualquer icone do topo, o balao que ainda estava aberto
    // por causa dos 400 ms de outro icone (APChat ou APMail) fecha na hora, para os dois nao ficarem abertos e sobrepostos.
    const fecharBalao = function() {
        clearTimeout(balaoTimer);
        if (! $('#envelope_messages .fast-drop.apchat-aberto').length) { return; }
        $('#envelope_messages .fast-drop').removeClass('apchat-aberto');
        if (balaoPendente) { balaoPendente = false; updateMessagesMenu(); }
    }
    const fecharMail = function() {
        clearTimeout(mailTimer);
        if (! $('#envelope_mail .fast-drop.apmail-aberto').length) { return; }
        $('#envelope_mail .fast-drop').removeClass('apmail-aberto');
        if (mailPendente) { mailPendente = false; updateMailMenu(); }
    }
    $(document).on('mouseenter', '.fast-drop', function() {
        if (! $(this).closest('#envelope_messages').length) { fecharBalao(); }
        if (! $(this).closest('#envelope_mail').length) { fecharMail(); }
    });
    $(document).on('mouseleave', '#envelope_mail .fast-drop', function() {
        const envelope = this;
        clearTimeout(mailTimer);
        mailTimer = setTimeout(function() {
            $(envelope).removeClass('apmail-aberto');
            if (mailPendente) { mailPendente = false; updateMailMenu(); }
        }, 400);
    });

    const updateMailMenu = function(fresco) {
        if (document.hidden || mailAberto()) { mailPendente = true; return; }
        $.get('engine.php?class=MailList&theme=theme-builder' + (fresco === true ? '&fresco=1' : ''), function(data) {
            if (mailAberto()) { mailPendente = true; return; }
            $('#envelope_mail').html(data);
        });
    }

    const updateNotificationsMenu = function() {
        $.get('engine.php?class=NotificationList&theme=theme-builder', function(data) {
            $('#envelope_notifications').html(data);
        });
    }

    // Curciol (11/11/2026): topo em UM pedido (balao do APChat, envelope do APMail e sino), a cada 10 s, so com a aba a vista.
    // Cada parte vem com a assinatura do conteudo: so redesenha a que mudou; com o mouse em cima, guarda e redesenha ao sair.
    // Aba escondida (outra aba, janela minimizada): nao pergunta nada; ao voltar, tudo na hora e o APMail direto (fresco).
    let topoPendente = false;
    const notificacoesAbertas = function() {
        return $('#envelope_notifications .fast-drop:hover').length > 0;
    }
    const aplicarTopo = function(seletor, parte, aberto, marcarPendente) {
        if (!parte || typeof parte.html !== 'string') { return; }
        if ($(seletor).data('assinatura') === parte.assinatura) { return; }
        if (aberto()) { marcarPendente(); return; }
        $(seletor).html(parte.html).data('assinatura', parte.assinatura);
    }
    const updateTopo = function(fresco) {
        if (document.hidden) { topoPendente = true; return; }
        $.getJSON('engine.php?class=TopoNotificacoes&method=onCarregar&static=1' + (fresco === true ? '&fresco=1' : ''), function(r) {
            aplicarTopo('#envelope_messages', r.mensagens, balaoAberto, function() { balaoPendente = true; });
            aplicarTopo('#envelope_mail', r.mail, mailAberto, function() { mailPendente = true; });
            aplicarTopo('#envelope_notifications', r.notificacoes, notificacoesAbertas, function() { topoPendente = true; });
        });
    }
    // ao voltar para a aba: tudo na hora (o balaoVoltou de 11/11 virou o do topo inteiro)
    const balaoVoltou = function() {
        if (!document.hidden) { topoPendente = false; updateTopo(true); }
    }
    document.addEventListener('visibilitychange', balaoVoltou);

    const initLeftMenu = function() {
        $(document).ready(function(){
            $('.toggle-menu').on('click', function(){
                $('.master-menu-content').toggleClass('open');
                $('.header-track').hide();
                if ($('.master-menu-content').hasClass('open')) {
                    $(this).find('i').removeClass('fa-bars');
                    $(this).find('i').addClass('fa-times');
                    $(".menu-elastic").slimScroll({height: "auto", position: "left", "distance": '0px'});
                } else {
                    $(this).find('i').addClass('fa-bars');
                    $(this).find('i').removeClass('fa-times');
                }
            });

            $('.toggle-top-menu').on('click', function(){
                if ( $('.header-track').is(':visible') ) {
                    $('.header-track').hide();
                } else {
                    $('.header-track').css('display', 'flex');
                }
            });

            $('.master-menu-content .container-submenu>ul>li').on('click', function(evt) {
                if (this === evt.target.parentNode) {
                    $('.master-menu-content .container-submenu ul li').removeClass('checked')
                }
            });

            $('.master-menu-content .container-submenu ul li').on('click', function() {
                $(this).addClass('checked');
            });

            if ( $('.container-menu .menu-elastic a[menu-target]').length == 0) {
                $('.container-menu:not(#top-menu)').hide();
            }

            $('.container-menu a[menu-target]').on('click', function(){
                const target = $(this).attr('menu-target');

                if ($(`.container-submenu [module-menu=${target}]`).length > 0) {
                    $(`.container-submenu [module-menu]:not([module-menu=${target}])`).removeClass('open');
                    $(`.container-submenu [module-menu=${target}]`).addClass('open');
            
                    $(`.container-menu a[menu-target]:not([menu-target=${target}])`).removeClass('checked');
                    $(`.container-menu a[menu-target=${target}]`).addClass('checked'); 
                }
            });
        
            $('.container-submenu a.sub').on('click', function(){
                const action = $(this);

                if (action.hasClass('open')) {
                    action.removeClass('open');
                    action.parent().removeClass('checked');
                    action.next().slideUp(300);
                } else {
                    action.addClass('open');
                    action.next().slideDown(300);
                }
            });
        
            $('.container-submenu a').on('click', function(e){
                if (! $(this).hasClass('sub')) {
                    if ($('body').width() < 992) {
                        $('.master-menu-content').removeClass('open');
                        $('.header-track').hide();
    
                        $('.toggle-menu i').addClass('fa-bars');
                        $('.toggle-menu i').removeClass('fa-times');
                    }
                }

                $('.master-menu-content .container-submenu li').removeClass('checked');
                $(this).closest('li').addClass('checked');
            });
        });

        $('.sidebar-mini .master-menu-content,.header-logo').on('mouseenter', function() {
            if ($('body').width() > 992) {
                $('.sidebar-mini .master-menu-content,.header-logo').addClass('open');
            }
        });

        $('.sidebar-mini .master-menu-content,.header-logo').on('mouseleave', function() {
            if ($('body').width() > 992) {
                $('.sidebar-mini .master-menu-content,.header-logo').removeClass('open');
            }
        });

        $('.fixed-sidebar-mini-menu').on('click', function() {
            if ($('body').width() > 992) {
                BuilderTemplate.resizeBuilderMenu();
                if ($('.sidebar-mini').hasClass('fixed')) {
                    $('.sidebar-mini').removeClass('fixed');

                    $('.fixed-sidebar-mini-menu i').removeClass('fa-bars');
                    $('.fixed-sidebar-mini-menu i').addClass('fa-thumbtack');
                } else {
                    $('.sidebar-mini').addClass('fixed');

                    $('.fixed-sidebar-mini-menu i').removeClass('fa-thumbtack');
                    $('.fixed-sidebar-mini-menu i').addClass('fa-bars');
                }
            }
        });

        $(".menu-elastic").slimScroll({height: "auto", position: "left", "distance": '0px'});

        $(window).on('resize', function(){
            $(".menu-elastic").slimScroll({height: "auto", position: "left", "distance": '0px'});
        });
    }

    const initFirebase = function()
    {
        if(BuilderTemplate.firebase_config)
        {
            window.addEventListener('firebaseLoaded', async function() {

                await FirebaseService.init({
                    token: BuilderTemplate.firebase_token,
                    config: BuilderTemplate.firebase_config
                }, function(){
                    
                    setTimeout(function() { if(BuilderTemplate.chat_enabled)
                        {
                            ChatApp.init();
                        }
                        else
                        {
                            ChatApp.disable();
                        } }, 1000);
                    

                    $(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
                        const params = new URLSearchParams(ajaxOptions.url.split('?')[1]);
                        const result = `${params.get('class')}::${params.get('method')}`;

                        FirebaseService.setUserAttribute('last_action', result);
                    });
                });
            });
        }
    }

    const init = function(options)
    {
        if(options.mad_debug_console)
        {
            initDebugConsole();            
        }

        if(options.top_menu)
        {
            BuilderTemplate.initTopMenu();
        }
        else if(options.public_layout == false)
        {
            BuilderTemplate.loadSearchBar();
        }

        BuilderTemplate.users = options.users;
        BuilderTemplate.chat_enabled = options.chat_enabled;
        BuilderTemplate.firebase_token = options.firebase_token;
        BuilderTemplate.single_tab_mode = options.single_tab_mode;
        BuilderTemplate.firebase_config = options.firebase_config;
        BuilderTemplate.application_name = options.application_name;

        if(BuilderTemplate.single_tab_mode == true)
        {
            setTimeout(function() { System.checkMultipleTabs(options.application_name); });
        }

        initFirebase();
        BuilderTemplate.initLeftMenu();
        BuilderTemplate.processTheme(options);
        BuilderTemplate.processFastDrop();

        if(options.public_layout == false)
        {
            // Curciol (11/11/2026): um pedido so para o topo inteiro (antes: balao, envelope e sino em 3 intervalos)
            BuilderTemplate.updateTopo();
            if(options.verify_messages_menu || options.verify_notifications_menu)
            {
                BuilderTemplate.intervalUpdateTopo = setInterval(BuilderTemplate.updateTopo, 10000);
            }
        }

        if(!options.use_tabs && ! options.use_mdi_windows)
        {
            $('.adianti_tabs_container').hide();
            $('.adianti_tabs_container').css('height', '0px');
        }

        if(typeof options.dialog_box_type != 'undefined' && options.dialog_box_type == 'sweetalert')
        {
            BuilderTemplate.enableSweetAlert();
        }

        __adianti_set_name(options.application_name);
        __adianti_init_tabs(options.use_tabs, options.store_tabs, options.use_mdi_windows);
        __adianti_set_language(options.language);
        __adianti_set_debug(options.debug);
    }

    const loadSearchBar = function()
    {
        $.get('engine.php?class=SearchBox', function(data)
        {
            $('.navbar-custom-menu').append(data).show();
            var search_box = $('.navbar-nav').nextAll('div');
            search_box.css('padding-top', '10px');
            search_box.css('padding-left', '25px');
            search_box.css('display', 'table');
            search_box.css('float', 'right');
            search_box.attr('id', 'search-box');
        });
    }

    const changeStyle = function(button)
    {
        var theme = $(button).attr('theme');
        window.localStorage.setItem('style_template', theme);
        
        if(theme)
        {
            BuilderTemplate.defineTheme(theme);
            __adianti_load_page('engine.php?class=BuilderConfigForm&method=setTheme&static=1&theme='+theme);
        }
    }

    const defineTheme = function(theme)
    {
        let time = new Date().getTime();
        
        $('.change-theme').removeClass('checked');
        $('[theme-style]').attr('href', 'app/templates/theme-builder/themes/' + theme + '.css?time='+time);
        $('[theme=' + theme + ']').addClass('checked');
    }

    const processTheme = function(options) {
        //Drop do menu
       $(".change-theme").click(function() 
       {
           BuilderTemplate.changeStyle(this);
       });

       if (options.theme)
       {
            BuilderTemplate.defineTheme(options.theme);
       }

       if (! options.mobile)
       {
           if ( $('.builder-list-themes .fast-drop-open a').length <= 1)
           {
               $('.builder-list-themes').remove();
           }
       }
   }

    const processFastDrop = function() {
        setTimeout(function(){
            $('.fast-drop').off('mouseenter');
            $('.fast-drop').on('mouseenter', function(e) {
                const positionTop = $(this).offset().top;
                const positionLeft = $(this).offset().left;
                
                const vertivalMiddle = $(window).height() / 2;
                const horizontalMiddle = $(window).width() / 2;
        
                if ( positionTop > vertivalMiddle ) {
                    $(this).addClass('force-top');
                    $(this).removeClass('force-bottom');
                } else {
                    $(this).removeClass('force-top');
                    $(this).addClass('force-bottom');
                }
        
                if ( positionLeft > horizontalMiddle ) {
                    $(this).removeClass('force-left');
                    $(this).addClass('force-right');
                } else {
                    $(this).addClass('force-left');
                    $(this).removeClass('force-right');
                }
            });
        }, 500);
    }

    const enableSweetAlert = function()
    {
        __adianti_dialog = function ( options )
        {
            setTimeout( function() {
                swal({
                html: true,
                title: options.title,
                text: options.message,
                type: options.type,
                allowEscapeKey: (typeof options.callback == 'undefined'),
                allowOutsideClick: (typeof options.callback == 'undefined')
                },
                function(){
                    if (typeof options.callback != 'undefined') {
                        options.callback();
                    }
                });
            }, 100);
        }

        __adianti_question = function (title, message, callback_yes, callback_no, label_yes, label_no)
        {
            setTimeout( function() {
                swal({
                html: true,
                title: title,
                text: message,
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: label_yes,
                cancelButtonText: label_no
                },
                function(isConfirm){
                if (isConfirm) {
                    if (typeof callback_yes != 'undefined') {
                        callback_yes();
                    }
                } else {
                    if (typeof callback_no != 'undefined') {
                        callback_no();
                    }
                }
                });
            }, 100);
        }
    }

    const initDebugConsole = function() {
        
        System.initDebugConsole();

    }

    return {
        updateMessagesMenu: updateMessagesMenu,
        updateNotificationsMenu: updateNotificationsMenu,
        updateMailMenu: updateMailMenu,
        updateTopo: updateTopo,
        loadSearchBar: loadSearchBar,
        init: init,
        resizeBuilderMenu: resizeBuilderMenu,
        clickBuilderMenuOption: clickBuilderMenuOption,
        initTopMenu: initTopMenu,
        initLeftMenu: initLeftMenu,
        processTheme: processTheme,
        changeStyle: changeStyle,
        defineTheme: defineTheme,
        processFastDrop: processFastDrop,
        enableSweetAlert: enableSweetAlert,
    };

})();

// Curciol (12/11/2026): links do APChat e do APMail so pelo app instalado (app/lib/util/LinkApp.php). O link web+apchat:// ou
// web+apmail:// vai para o sistema operacional, que entrega ao app instalado (manifesto do app com protocol_handlers); sem app,
// nada abre. Se a janela da Curciol nao perder o foco em 2,5 s, o app nao abriu: avisa para instalar.
window.CurciolAbrirApp = function(link, nome) {
    if (!/^web\+(apchat|apmail):\/\//.test(String(link))) { return; }
    var abriu = false;
    var marcar = function() { abriu = true; };
    var oculto = function() { if (document.hidden) { abriu = true; } };
    window.addEventListener('blur', marcar);
    document.addEventListener('visibilitychange', oculto);
    var a = document.createElement('a');
    a.href = link;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function() {
        window.removeEventListener('blur', marcar);
        document.removeEventListener('visibilitychange', oculto);
        if (!abriu && document.hasFocus()) {
            var texto = 'O ' + nome + ' não abriu neste computador. Instale o app do ' + nome + ': abra o ' + nome + ' no Chrome, Edge ou Brave e use o botão '
                      + '"Instalar app" na barra de endereço. Depois clique de novo aqui. Se o navegador perguntar se pode abrir o ' + nome + ', escolha Abrir e marque para lembrar.';
            if (typeof __adianti_warning === 'function') { __adianti_warning(nome, texto); } else { alert(texto); }
        }
    }, 2500);
};
