<?php

class TratamentosService
{
    public static function removerAcentos($string) {
        // decompoe (NFD) e tira as marcas de acento: cobre ç/Ç e textos ja gravados decompostos (cadastros antigos),
        // igual ao unaccent() do banco, usado do outro lado dos filtros
        if (class_exists('Normalizer')) {
            $decomposto = Normalizer::normalize((string) $string, Normalizer::FORM_D);
            if ($decomposto !== false) {
                return preg_replace('/\p{Mn}+/u', '', $decomposto);
            }
        }
        $string = str_replace(['ç', 'Ç'], ['c', 'C'], (string) $string);
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
