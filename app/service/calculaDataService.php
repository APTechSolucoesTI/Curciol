<?php

class calculaDataService
{
    
    public static function seDiaUtil($dia, $mes, $ano)
    {
        $data = mktime(0, 0, 0, $mes, $dia, $ano); 
        $dia_semana = date("N", $data);
         
        // verifica sábado e domingo
        if($dia_semana == 6 || $dia_semana == 7){
            return false;
        }else{
            return true;
        }
    }
    
    public static function primeiroDiaUtil($mes, $ano)
    {
        $primeiro = mktime(0, 0, 0, $mes, 1, $ano); 
        $dia = date("j", $primeiro);
        $dia_semana = date("N", $primeiro);
         
        // verifica sábado e domingo
        if($dia_semana == 6){
            $dia++;
            $dia++;
        }
        if($dia_semana == 7){
            $dia++;
        }
        
        $primeiro = mktime(0, 0, 0, $mes, $dia, $ano);
        
        return date("d/m/Y", $primeiro);
    }
}
