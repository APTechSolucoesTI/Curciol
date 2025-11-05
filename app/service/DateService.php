<?php

class DateService  extends TDate
{
    const dayWeek = [
        1 => 'Segunda-Feira',
        2 => 'Terça-Feira',
        3 => 'Quarta-Feira',
        4 => 'Quinta-Feira',
        5 => 'Sexta-Feira',
        6 => 'Sábado',
        0 => 'Domingo'
    ];
    
    
    const month = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    ];
    
    const CARDINALS = [ 
        1 => 'First' ,
        2 => 'Second',
        3 => 'Third' ,
        4 => 'Fourth',
        5 => 'Fifth' ,
        6 => 'Sixth' ,
        7 => 'Seventh'
    ];
    


    const DAY_OF_MONTH = 'm';
    const DAY_OF_WEEK  = 's';
    
    public static function getWeekOfMonth($date)
    {
        $week = self::weekOfMonth($date);
        
        $translate = [
            'first sunday' => '1º Domingo',
            'first monday' => '1ª Segunda-Feira',
            'first tuesday' => '1ª Terça-Feira',
            'first wednesday' => '1ª Quarta-Feira',
            'first thursday' => '1ª Quinta-Feira',
            'first friday' => '1ª Sexta-Feira',
            'first saturday' => '1º Sábado',
            'second sunday' => '2º Domingo',
            'second monday' => '2ª Segunda-Feira',
            'second tuesday' => '2ª Terça-Feira',
            'second wednesday' => '2ª Quarta-Feira',
            'second thursday' => '2ª Quinta-Feira',
            'second friday' => '2ª Sexta-Feira',
            'second saturday' => '2° Sábado',
            'third sunday' => '3º Domingo',
            'third monday' => '3ª Segunda-Feira',
            'third tuesday' => '3ª Terça-Feira',
            'third wednesday' => '3ª Quarta-Feira',
            'third thursday' => '3ª Quinta-Feira',
            'third friday' => '3ª Sexta-Feira',
            'third saturday' => '3º Sábado',
            'fourth sunday' => '4º Domingo',
            'fourth monday' => '4ª Segunda-Feira',
            'fourth tuesday' => '4ª Terça-Feira',
            'fourth wednesday' => '4ª Quarta-Feira',
            'fourth thursday' => '4ª Quinta-Feira',
            'fourth friday' => '4ª Sexta-Feira',
            'fourth saturday' => '4º Sábado',
            'fifth sunday' => '5º Domingo',
            'fifth monday' => '5ª Segunda-Feira',
            'fifth tuesday' => '5ª Terça-Feira',
            'fifth wednesday' => '5ª Quarta-Feira',
            'fifth thursday' => '5ª Quinta-Feira',
            'fifth friday' => '5ª Sexta-Feira',
            'fifth saturday' => '5º Sábado',
            'sixth sunday' => '6º Domingo',
            'sixth monday' => '6ª Segunda-Feira',
            'sixth tuesday' => '6ª Terça-Feira',
            'sixth wednesday' => '6ª Quarta-Feira',
            'sixth thursday' => '6ª Quinta-Feira',
            'sixth friday' => '6ª Sexta-Feira',
            'sixth saturday' => '6º Sábado',
            'seventh sunday' => '7º Domingo',
            'seventh monday' => '7ª Segunda-Feira',
            'seventh tuesday' => '7ª Terça-Feira',
            'seventh wednesday' => '7ª Quarta-Feira',
            'seventh thursday' => '7ª Quinta-Feira',
            'seventh friday' => '7ª Sexta-Feira',
            'seventh saturday' => '7º Sábado',
            'last sunday' => 'último Domingo',
            'last monday' => 'última Segunda-Feira',
            'last tuesday' => 'última Terça-Feira',
            'last wednesday' => 'última Quarta-Feira',
            'last thursday' => 'última Quinta-Feira',
            'last friday' => 'última Sexta-Feira',
            'last saturday' => 'último Sábado'
        ];
        
        
        return $translate[strtolower($week)]??$week;
    }
    
    public static function getDayWeek($date = null)
    {
        if ($date)
        {
            $diasemana_numero = date('w', strtotime($date));
            $diasemana_nome = date('D', strtotime($date));
            
            switch ($diasemana_nome) {
                case 'Sun':
                    $diasemana_nome = 'Domingo';
                    break;
                case 'Mon':
                    $diasemana_nome = 'Segunda-Feira';
                    break;
                case 'Tue':
                    $diasemana_nome = 'Terca-Feira';
                    break;
                case 'Wed':
                    $diasemana_nome = 'Quarta-Feira';
                    break;
                case 'Thu':
                    $diasemana_nome = 'Quinta-Feira';
                    break;
                case 'Fri':
                    $diasemana_nome = 'Sexta-Feira';
                    break;
                case 'Sat':
                    $diasemana_nome = 'Sábado';
                    break;
            }
            return $diasemana_nome;
        }
        
        return $diasemana_nome;
    }
    
    public static function getMonthName($date = null)
    {
        if ($date)
        {
            return self::month[(int)(explode('-',$date))[1]];
        }
        
        return (explode('-',$date))[1];
    }
    
    /**
     * Gera um periodo de datas mensais
     * 
     * @author lucas.tomasi
     * 
     * @param  [date]  $start_date       [data inicial do periodo]
     * @param  [int]  $quantidade        [quantidade de repetições]
     * @param  [char]  $type             [DateService::DAY_OF_MONTH || DateService::DAY_OF_WEEK]
     * @param  [integer] $month_interval [numero do intervalo de meses a ser gerado]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesMensaisByQuantidade($start_date, $quantidade, $type, $month_interval = 1 )
    {
        $months = $quantidade * $month_interval;
        $end_date =  date('Y-m-d', strtotime("{$start_date} + {$months} months")); 
        
        $datesRepeticoes = self::gerarRepeticoesMensais( $start_date, $end_date, $type, $month_interval);
        
        $dates = [];
        
        foreach ($datesRepeticoes as $dt )
        {
            if ($dt > $start_date)
            {
                $dates[] = $dt;
            }
            
            if (count($dates) >= $quantidade)
            {
                break;
            }
        }
    
        return $dates;
    }
    
     /**
     * Gera um periodo de datas mensais
     * 
     * @author lucas.tomasi
     * 
     * @param  [date]  $start_date       [data inicial do periodo]
     * @param  [date]  $end_date         [data final do periodo]
     * @param  [char]  $type             [DateService::DAY_OF_MONTH || DateService::DAY_OF_WEEK]
     * @param  [integer] $month_interval [numero do intervalo de meses a ser gerado]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesMensais( $start_date, $end_date, $type, $month_interval = 1 )
    {
        $dates = [];
        $begin = new DateTime($start_date);
        $end   = new DateTime($end_date);

        if( $type == DateService::DAY_OF_WEEK )
        {
            $interval = DateInterval::createFromDateString(self::weekOfMonth($start_date) . " of +{$month_interval} month");
            $periods  = new DatePeriod($begin, $interval, $end);
        }
        else
        {
            $periods = self::getDaysOfMonths($begin, $end, $month_interval);
        }
        
        if( $periods )
        {
            foreach ( $periods as $dt )
            {
                $dates[] = $dt->format('Y-m-d');
            }
        }

        $dates = array_diff($dates, [$start_date]);
        return $dates;
    }
    
    /**
     * Gera um periodo de datas semanais
     *
     * @author lucas.tomasi
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [date]  $dt_final             [data final do periodo]
     * @param  [array]  $dias_semana         [dias da semana que são para repetir]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesDiarias($dt_inicial, $dt_final, $dias_semana )
    {
        $datas      = [];
        
        $inicio = new DateTime($dt_inicial);
        $fim    = new DateTime($dt_final);

        while( TRUE )
        {
            $inicio = $inicio->modify('+1 day');
            
            if (in_array($inicio->format('w'), $dias_semana))
            {
                $datas[] = $inicio->format('Y-m-d');
            }
            
            if ($inicio->format('Y-m-d') >= $fim->format('Y-m-d'))
            {
                break;
            }
        }
        
        $datas = array_diff($datas, [$dt_inicial]);
        sort($datas);
        return $datas;
    }
    
    /**
     * Gera um periodo de datas semanais
     *
     * @author lucas.tomasi
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [int]  $quantidade           [quantidade de repetições]
     * @param  [array]  $dias_semana         [dias da semana que são para repetir]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesDiariasByQuantidade($dt_inicial, $quantidade, $dias_semana )
    {
        $datas      = [];
        
        $inicio = new DateTime($dt_inicial);

        while( TRUE )
        {
            $inicio = $inicio->modify('+1 day');
            
            if (in_array($inicio->format('w'), $dias_semana) AND $inicio->format('Y-m-d') != $dt_inicial)
            {
                $datas[] = $inicio->format('Y-m-d');
            }
            
            if (count($datas) >= $quantidade)
            {
                break;
            }
        }
        
        $datas = array_diff($datas, [$dt_inicial]);
        sort($datas);
        return $datas;
    }
    
    
    
    /**
     * Gera um periodo de datas anual
     *
     * @author lucas.tomasi
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [date]  $dt_final             [data final do periodo]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesAnuais($dt_inicial, $dt_final)
    {
        $datas      = [];
        
        $inicio = new DateTime($dt_inicial);
        $fim    = new DateTime($dt_final);

        while( TRUE )
        {
            $inicio = $inicio->modify('+1 year');
            
            if ($inicio->format('Y-m-d') > $fim->format('Y-m-d'))
            {
                break;
            }
            
            $datas[] = $inicio->format('Y-m-d');
        }
        
        $datas = array_diff($datas, [$dt_inicial]);
        sort($datas);
        return $datas;
    }
    
    /**
     * Gera um periodo de datas anual
     *
     * @author lucas.tomasi
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [int]  $quantidade             [quantidade de repetições]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesAnuaisByQuantidade($dt_inicial, $quantidade)
    {
        $datas      = [];
        
        $inicio = new DateTime($dt_inicial);
        $fim    = new DateTime($dt_final);

        while( TRUE )
        {
            $inicio = $inicio->modify('+1 year');
            
            if ($inicio->format('Y-m-d') > $dt_inicial)
            {
                $datas[] = $inicio->format('Y-m-d');
            }
            
            if (count($datas) >= $quantidade)
            {
                break;
            }
        }
        
        $datas = array_diff($datas, [$dt_inicial]);
        sort($datas);
        return $datas;
    }
    
    /**
     * Gera um periodo de datas semanais
     *
     * @author lucas.tomasi
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [int]  $quantidade           [quantidade de repetições]
     * @param  [array]  $dias_semana         [dias da semana que são para repetir]
     * @param  [integer] $frequencia_semanal [numero do intervalo de semanas a ser gerado]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesSemanaisByQuantidade($dt_inicial, $quantidade, $dias_semana )
    {
        $datas = [];
        
        $semanas = $quantidade + 2;
        
        $inicio = new DateTime($dt_inicial);
        $dt_final = $inicio->modify("+{$semanas} week");
        
        $datasRepeticoes = self::gerarRepeticoesSemanais($dt_inicial, $dt_final->format('Y-m-d'), $dias_semana, $frequencia_semanal = 'P1W' );
      
        if ($datasRepeticoes)
        {
            foreach($datasRepeticoes as $data)
            {
                if ($data > $dt_inicial) {
                    $datas[] = $data;
                }
                
                if (count($datas) >= $quantidade)
                {
                    break;
                }
            }
        }
    
        sort($datas);
        return $datas;
    }
    
    
    /**
     * Gera um periodo de datas semanais
     *
     * @author artur.comunello
     * 
     * @param  [date]  $dt_inicial           [data inicial do periodo]
     * @param  [date]  $dt_final             [data final do periodo]
     * @param  [array]  $dias_semana         [dias da semana que são para repetir]
     * @param  [integer] $frequencia_semanal [numero do intervalo de semanas a ser gerado]
     * 
     * @return [array] datas entre a data inicial e final no periodo especificado.
     */
    public static function gerarRepeticoesSemanais($dt_inicial, $dt_final, $dias_semana, $frequencia_semanal = 'P1W' )
    {
        $datas      = [];
        $semana     = $dias_semana;
        $frequencia = $frequencia_semanal;
        
        $inicio = new DateTime($dt_inicial);
        $fim    = new DateTime($dt_final);

        foreach ($semana as $dia)
        {
            $inicial = new stdClass;
            $inicial = clone $inicio;
            $inicial = $inicial->modify('this week');

            $inicial = $inicial->modify('this ' . $dia);
            $fim = $fim->modify('+1 day');


            $intervalo = new DateInterval($frequencia);
            $daterange = new DatePeriod($inicial, $intervalo ,$fim);

            foreach( $daterange as $date )
            {
                if( $date >= $inicio )
                {
                    $datas[] = $date->format('Y-m-d');
                }
            }
        }
        
        $datas = array_diff($datas, [$dt_inicial]);
        sort($datas);
        return $datas;
    }
    
    /**
     * retorna todos os dias iguais da ($start_date) dos próximos meses
     * 
     * @author lucas.tomasi
     * 
     * @param  [date] $start_date     [data inicial do periodo]
     * @param  [date] $end_date       [data final do periodo]
     * @param  [int] $month_interval  [intervalo de meses para a busca]
     * 
     * @return [array] array com as datas com o dia igual incluindo a data inicial
     */
    private static function getDaysOfMonths( $start_date, $end_date, $month_interval )
    {
        $dates    = []; 
        $day      = $start_date->format('d'); 
        $interval = $month_interval; 

        while( TRUE )
        {
            $date = date('Y-m-d' , strtotime("+{$interval} month " . $start_date->format('Y-m-d')));

            if( $date <= $end_date->format('Y-m-d') )
            {
                $interval = $interval + $month_interval;

                if( date('d', strtotime($date)) == $day )
                {
                    $start_date = new DateTime($date);
                    $interval   = $month_interval;
                    $dates[]    = $start_date;
                }
            }
            else
            {
                break;
            }
        }

        return $dates;
    }
    
    
    /**
     * retorna o dia da semana e sua ocorrencica ex.: last monday, first sunday...
     *
     * @author lucas.tomasi
     * 
     * @param  [date] $date [data para a verificacao]
     * @return [string]     [descricao da ocorrencia]
     */
    public static function weekOfMonth($date)
    {
        $week       = date('l'     , strtotime($date));
        $start_date = date('Y-m-01', strtotime($date));
        $end_date   = date('Y-m-d' , strtotime("+1 day last day next month {$start_date}"));

        $begin = new DateTime($start_date);
        $end   = new DateTime($end_date);

        $intervalerval = DateInterval::createFromDateString("next {$week}");

        $periods = new DatePeriod($begin, $intervalerval, $end);

        $number = 0;
        $count  = 0;

        foreach ( $periods as $dt )
        {
            if(  $week == $dt->format('l') )
            {
                $count++;

                if( $dt->format('Y-m-d') == date('Y-m-d' , strtotime($date)) )
                {
                    $number = $count;
                }
            }
        }

        if( $count == $number ) {
            $number = 'Last';
        } else {
            $number = DateService::CARDINALS[ $number ];
        }

        return "{$number} {$week}";
    }
}
