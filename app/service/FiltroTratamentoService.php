<?php

class FiltroTratamentoService
{
    public static function adicionarPorcentagemEntreLetras($string) {
        $novaString = "";
    
        for ($i = 0; $i < strlen($string); $i++) {
            $novaString .= $string[$i] . "%";
        }
    
        // Remover o último "%" extra
        $novaString = substr($novaString, 0, -1);
    
        return $novaString;
    }
    public static function removerPorcentagemEntreLetras($string) {
        // Expressão regular para encontrar todos os caracteres '%'
        $padrao = '/%/';
        
        // Substitui todos os caracteres '%' por string vazia
        $novaString = preg_replace($padrao, '', $string);
    
        return $novaString;
    }
}
