<?php
/**
 * Envelope de e-mail do topo (10/11/2026): reservado para a futura integracao com o APMail. Por enquanto so avisa "em breve".
 */
class MailList extends TElement
{
    public function __construct($param)
    {
        parent::__construct('div');
        $this->class = 'fast-drop';

        $a = new TElement('a');
        $a->{'class'} = 'button-circle';
        $a->{'href'} = '#';
        // logo do APMail (10/11/2026)
        $a->add(LogoSistemaService::apmail(22));
        $this->add(TElement::tag('div', $a, ['class' => 'fast-drop-label']));

        $lista = new TElement('div');
        $lista->{'class'} = 'fast-drop-open';
        $lista->add(TElement::tag('div', 'E-mails', ['class' => 'header']));
        $lista->add(TElement::tag('div', 'Integração com o APMail em breve.', ['style' => 'padding:10px 14px;color:#777;']));
        $this->add($lista);

        TScript::create('BuilderTemplate.processFastDrop();');
    }
}
