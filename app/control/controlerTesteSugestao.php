<?php

class controlerTesteSugestao extends TPage
{
    public function onShow($param = null)
    {
        TTransaction::open('escritorio');
        //PublicacaoSugestaoPrazo::where('publicacao_id','=',$publicacao->id)->delete();
        $configBusca = ConfigIaBuscaPrazo::where('id','=',1)->first();
        $posicao = 0;
        $busca = strtoupper("agravo de instrumento");
        $texto = strtoupper("Nº 0105814-41.2024.8.26.9061 - Processo Digital - Agravo de Instrumento - Americana - Agravante: Germânica Locadora de Veículos Limitada - Agravada: Natália Tardelli Fonseca - AGRAVO DE INSTRUMENTO. ACIDENTE DE VEÍCULO. AÇÃO DE REPARAÇÃO DE DANOS PROPOSTA PERANTE A 3.ª VARA CÍVEL DA COMARCA DE AMERICANA. INDEFERIMENTO DO PEDIDO DE DENUNCIAÇÃO DA LIDE. INTERPOSIÇÃO DE RECURSO NO COLÉGIO RECURSAL. INCOMPETÊNCIA ABSOLUTA. RECURSO NÃO CONHECIDO COM DETERMINAÇÃO. DECISÃO MONOCRÁTICA N.º 0822 Trata-se de agravo de instrumento interposto à r. decisão que, em ação de ressarcimento de danos, indeferiu o pedido de denunciação da lide deduzido pela requerida. Alega a agravante o cabimento de denunciação da lide da Prefeitura Municipal de Americana, por força de contrato de locação do automóvel envolvido no acidente, firmado através de procedimento licitatório na modalidade Pregão Presencial n.º 044/2019, processo administrativo n.º 64.771/2019, de modo que preenchido o requisito do inciso II do artigo 125 do Código de Processo Civil. Agravo tempestivo e preparado. É O RELATÓRIO. O recurso não comporta conhecimento. Com efeito. Prescrevem os artigos 35 e 103, ambos do Regimento Interno do Egrégio Tribunal de Justiça do estado de São Paulo: Art. 35. As Câmaras julgam os recursos das decisões de primeiro grau, os embargos declaratórios e os infringentes no processo criminal opostos a seus acórdãos, as ações rescisórias, as reclamações por descumprimento de seus julgados, os agravos internos e regimentais, habeas corpus, mandados de segurança e demais feitos de competência originária. (Redação dada pelo Assento Regimental nº 562/2017). Art. 103. A competência dos diversos órgãos do Tribunal firma-se pelos termos do pedido inicial, ainda que haja reconvenção ou ação contrária ou o réu tenha arguido fatos ou circunstâncias que possam modificá-la. Na espécie, trata-se de ação de reparação de danos oriundo de acidente de veículo proposta por Natália Tardelli Fonseca contra Germânica Locadora de Veículo Ltda, em trâmite perante a 3.ª Vara Cível da Comarca de Americana, em que desafiada a decisão de indeferimento do pedido de denunciação à lide, de modo que a competência recursal seria de uma das Câmaras da Subseção de Direito Privado III do Egrégio Tribunal de Justiça, consoante Normas de Trabalho em Segunda Instância, instituídas pelo Provimento n.º 71/2007 (D.J.E. 11/07/2007), cujo objetivo é informar o rol de competência do Órgão Especial, Câmara Especial e Seções do Tribunal, conforme Resolução nº 623/2013 do Tribunal de Justiça, e artigos 13 e 33 do Regimento Interno do Tribunal de Justiça em vigência (extraído de:https://www. tjsp.jus.br/Download/Normas2Grau/Instrucoes/IT%20SEJ0001%20INSTRU%C3%87%C3%83O-TRABALHO-1.htm). Patente o erro de interposição do presente instrumento no Colégio Recursal, ainda que o valor da causa não exceda a quarenta vezes o salário mínimo (Lei n.º 9.099/95, artigo 3.º, inciso I), isso porque a autora optou em propor a ação no juízo comum. Ante o exposto, não conheço do recurso, determinando a redistribuição a uma das Colendas Câmaras da Seção de Direito Público do Egrégio Tribunal de Justiça de São Paulo. Intime-se. - Magistrado(a) Celso Alves de Rezende - Colégio Recursal - Advs: Manuela Barbosa de Oliveira (OAB: 339221/SP) - Rodrigo Evangelista Marques (OAB: 211433/SP) - Jose Almir Curciol (OAB: 126722/SP) - Sala 2100");
        
        
        $resultado = str_replace($busca,
            "<span style='background-color:#fff000;'>".$busca."</span>",
            $texto);
        
        echo "$resultado";
        TTransaction::close();
    }
}
