<?php

class DocumentoDocument extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'Documento';
    private static $primaryKey = 'id';
    private static $htmlFile = 'app/documents/DocumentoDocumentTemplate.html';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {

    }

    public static function onGenerate($param)
    {
        try 
        {
            TTransaction::open(self::$database);

            $class = self::$activeRecord;
            $object = new $class($param['key']);

            $html = new AdiantiHTMLDocumentParser(self::$htmlFile);
            $html->setMaster($object);

            $pageSize = 'A4';
            $document = 'tmp/'.uniqid().'.pdf'; 

            $html->process();

            $html->saveAsPDF($document, $pageSize, 'portrait');

            TTransaction::close();

            if(empty($param['returnFile']))
            {
                parent::openFile($document);

                new TMessage('info', _t('Document successfully generated'));    
            }
            else
            {
                return $document;
            }
        } 
        catch (Exception $e) 
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());

            // undo all pending operations
            TTransaction::rollback();
        }
    }

}

