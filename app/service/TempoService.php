<?php

class TempoService
{
    public static function getSiglaMeses()
    {
        return [
            '01' => 'Jan',
            '02' => 'Fev',
            '03' => 'Mar',
            '04' => 'Abr',
            '05' => 'Mai',
            '06' => 'Jul',
            '07' => 'Jun',
            '08' => 'Ago',
            '09' => 'Set',
            '10' => 'Out',
            '11' => 'Nov',
            '12' => 'Dez',
        ];
    }
    
    public static function getMeses()
    {
        return [
            '01'=>'Janeiro',
            '02'=>'Fevereiro',
            '03'=>'Março',
            '04'=>'Abril',
            '05'=>'Maio',
            '06'=>'Junho',
            '07'=>'Julho',
            '08'=>'Agosto',
            '09'=>'Setembro',
            '10'=>'Outubro',
            '11'=>'Novembro',
            '12'=>'Dezembro'
        ];
    }
    
    public static function getMesAno($anomes)
    {
        $siglas = self::getSiglaMeses();
        if(!empty($siglas[substr($anomes, 4,2)]))
        {
            $mes = $siglas[substr($anomes, 4,2)];
            $ano = substr($anomes, 2,2);
            return $mes . '/' . $ano;    
        }
        
        return '';
    }
    
    public static function getAnos()
    {
        $anoAtual = date('Y');
        $anoAtual -= 5;
        $anos = [];
        for($anoAtual; $anoAtual <= date('Y'); $anoAtual++)
        {
            $anos[$anoAtual] = $anoAtual;
        }
        
        for($anoAtual; $anoAtual <= date('Y') + 5; $anoAtual++)
        {
            $anos[$anoAtual] = $anoAtual;
        }
        
        return $anos;
    }
}
