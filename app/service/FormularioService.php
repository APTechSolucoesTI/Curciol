<?php

class FormularioService
{
    public static function getTipos()
    {
        return [
            'TText' => 'Texto multiplas linhas',
            'TDate' => 'Data',
            'THtmlEditor' => 'Texto estilizado',
            'TSpinner' => 'Números interios',
            'TEntry' => 'Texto simples',
            'TRadioGroup' => 'Seleção única',
            'TCheckGroup' => 'Seleção múltipla',
            'TCombo' => 'Escolha única',
            'TNumeric' => 'Números decimais'
        ];
    }
    
    public static function needOptions($id)
    {
        return in_array($id, ['TRadioGroup', 'TCheckGroup', 'TCombo']);
    }
    
    public static function getOptions(Questao $questao)
    {
        $options =  explode(';', $questao->opcoes);
        
        $items = [];
        
        foreach ($options as $option)
        {
            $items[$option] = $option;
        }
            
        return $items;
    }
    
    public static function getField(Questao $questao, Resposta $resposta = NULL)
    {
        $name = 'questao_'.$questao->id;
        
        $field =  ($questao->tipo_campo == 'TNumeric') ? new $questao->tipo_campo($name, 2, ',', '.') : new $questao->tipo_campo($name);
        
        if ($resposta)
        {
            if ($questao->tipo_campo === 'TCheckGroup')
            {
                $field->setValue(explode(',', $resposta->resposta));
            }
            else
            {
                $field->setValue($resposta->resposta);
            }
        }
        
        if ($questao->tipo_campo === 'TDate')
        {
            $field->setDatabaseMask('yyyy-mm-dd');
            $field->setMask('dd/mm/yyyy');
        }
        else if (in_array($questao->tipo_campo, ['TRadioGroup', 'TCheckGroup']))
        {
            $field->setUseButton(TRUE);
            $field->setLayout('horizontal');
        }
        else if (in_array($questao->tipo_campo, ['THtmlEditor', 'TText']))
        {
            $field->setSize('100%', 250);
        }
        else
        {
            $field->setSize('100%');
        }
        
        if (self::needOptions($questao->tipo_campo))
        {
            $field->addItems(self::getOptions($questao));
        }
        
        return $field;
    }
    
    public static function getLabel(Questao $questao)
    {
        $color =  $questao->fl_obrigatorio == 'S' ? 'red' : null;
        
        return new TLabel($questao->nome, $color, null, null, '100%');
    }
    
    public static function getLinhaInfoCadastro($criacao_user_nameval, $modificacao_user_nameval, $data_criacaoval, $data_modificacaoval){
        
        $data_criacao = new TDateTime('data_criacao');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_criacao->setEditable(false);
        $data_criacao->setSize('100%');
        $data_criacao->setValue($data_criacaoval);
        $col1 = [new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao];
        
        $criacao_user_name = new TEntry('criacao_user_name');
        $criacao_user_name->setEditable(false);
        $criacao_user_name->setSize('100%');
        $criacao_user_name->setValue($criacao_user_nameval);
        $col2 = [new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name];
        
        $data_modificacao = new TDateTime('data_modificacao');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setEditable(false);
        $data_modificacao->setSize('100%');
        $data_modificacao->setValue($data_modificacaoval);
        $col3 = [new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao];
        
        $modificacao_user_name = new TEntry('modificacao_user_name');
        $modificacao_user_name->setEditable(false);
        $modificacao_user_name->setSize('100%');
        $modificacao_user_name->setValue($modificacao_user_nameval);
        $col4 = [new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name];
        
        return $retorno = [$col1,$col2,$col3,$col4];
    }
}
