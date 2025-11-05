<?php

class TratamentosService
{
    public static function removerAcentos($string) {
        $string = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
        return $string;
    }

      public static function expandirAcentos($string)
    {
        $map = [
            'a' => '[aáàâãä]',
            'A' => '[AÁÀÂÃÄ]',
            'e' => '[eéèêë]',
            'E' => '[EÉÈÊË]',
            'i' => '[iíìîï]',
            'I' => '[IÍÌÎÏ]',
            'o' => '[oóòôõö]',
            'O' => '[OÓÒÔÕÖ]',
            'u' => '[uúùûü]',
            'U' => '[UÚÙÛÜ]',
            'c' => '[cç]',
            'C' => '[CÇ]',
        ];

        return strtr($string, $map);
    }
}
