<?php
/**
 * O que abre sem login de equipe (09/11/2026). Fica no codigo, e nao no public_classes do application.ini (o ini da producao e
 * preservado nas publicacoes e ainda tem a lista antiga do Mad Builder). Quem esta logado como equipe nao passa por aqui: vale a
 * permissao dos grupos.
 *
 * - todo mundo: login, landing (Home, com o login do portal pelo CPF), entrada publica (so redireciona ao login) e os links de
 *   confirmar/cancelar o agendamento;
 * - cliente logado no portal: as telas do portal e os servicos do framework que elas usam (busca, autocompletar, envio de arquivo).
 */
class AcessoPublicoService
{
    const TODOS = ['LoginForm', 'PublicView', 'Home', 'AgendamentoView'];

    const PORTAL = ['MeusDocumentoList', 'ProcessoViewHeaderList', 'ProcessosFormView', 'ProcessosFormViewInterno', 'ProcessoPublicacoesTimeLine',
                    'AdiantiMultiSearchService', 'AdiantiUploaderService', 'AdiantiAutocompleteService'];

    /** Telas da equipe que abrem dentro de outras telas e nunca tiveram permissao propria (estavam na lista publica do ini). */
    const DENTRO_DE_OUTRAS = ['ProcessoPublicacoesForm', 'PublicacaoAlterEtapaForm', 'AndamentoAlterEtapaForm', 'ContratoDateForm',
                              'ParceirosHeaderList', 'RequisicaoPagamentoVisualizacaoAba', 'RequisicaoPagamentoRelatorioDinamico'];

    /** Equipe logada: alem das permissoes do grupo, o que e de todos, do portal e as telas abertas dentro de outras. */
    public static function logadoPode(string $classe): bool
    {
        return in_array($classe, self::TODOS, true) || in_array($classe, self::PORTAL, true) || in_array($classe, self::DENTRO_DE_OUTRAS, true);
    }

    public static function anonimoPode(string $classe, bool $portalLogado): bool
    {
        return in_array($classe, self::TODOS, true) || ($portalLogado && in_array($classe, self::PORTAL, true));
    }

    /** Cliente do portal logado nesta sessao. */
    public static function portalLogado(): bool
    {
        return !empty(TSession::getValue('portal_cliente_id'));
    }
}
