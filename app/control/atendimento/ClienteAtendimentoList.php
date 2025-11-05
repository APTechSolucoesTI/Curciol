<?php

class ClienteAtendimentoList extends TPage
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Atendimento';
    private $limit = 20;

    public function __construct($param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 20;

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_atendimento_procedimento_procedimento_to_string = new TDataGridColumn('atendimento_procedimento_procedimento_to_string', "Procedimento", 'left');
        $column_profissional_nome = new TDataGridColumn('profissional->nome', "Profissional", 'left');
        $column_dt_inicio_transformed = new TDataGridColumn('dt_inicio', "Atendido em", 'left');

        $column_dt_inicio_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });        

        $this->datagrid->addColumn($column_atendimento_procedimento_procedimento_to_string);
        $this->datagrid->addColumn($column_profissional_nome);
        $this->datagrid->addColumn($column_dt_inicio_transformed);

        $this->datagrid->class = 'table';

        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Meus Atendimentos");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
        $panel->add($this->datagrid_form);

        $panel->addFooter($this->pageNavigation);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        }
        $container->add($panel);

        $container->style='width: 75%; margin:auto; margin-top:100px';

        parent::add($container);

    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'escritorio'
            TTransaction::open(self::$database);

            // creates a repository for Atendimento
            $repository = new TRepository(self::$activeRecord);
            // creates a criteria
            $criteria = new TCriteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';    
            }
            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            $cont = 1;

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";

                    $documentos = Documento::where('atendimento_id', '=', $object->id)->load();
                    $anexos = Anexo::where('atendimento_id', '=', $object->id)->load();
                    $imagensEstudo = ImagemEstudo::where('atendimento_id', '=', $object->id)->load();

                    $html = new TElement('div');

                    $html->style = 'padding-left:60px; padding-right:20px;';

                    if($anexos)
                    {
                        $html->add(new TFormSeparator('Arquivos do atendimento'));

                        foreach($anexos as $anexo)
                        {
                            $file_name = explode('/',$anexo->arquivo);
                            $file_name = end($file_name);
                            $link = new THyperLink($file_name, $anexo->arquivo, '#333', '14px', '', 'fas:file-download #000000');
                            $link->class = 'btn btn-link';
                            $html->add($link);
                        }
                    }

                    if($documentos)
                    {
                        $html->add(new TFormSeparator('Laudos do atendimento'));

                        foreach($documentos as $documento)
                        {
                            $link = new THyperLink("Ver Laudo", "engine.php?class=ClienteAtendimentoList&method=gerarDocumento&id={$documento->id}", '#333', '14px', '', 'fas:file-download #000000');
                            $link->class = 'btn btn-link';
                            $html->add($link);
                        }
                    }

                    if($imagensEstudo)
                    {
                        $html->add(new TFormSeparator('Imagens do atendimento'));
                        $html->add('<small>Para visualizar os exames diretamente é necessario que o visualizador <a href="https://nroduit.github.io/en/getting-started/">Weasis</a> esteja instalado.</small><br>');

                        $contImagem = 0;
                        foreach($imagensEstudo as $imagemEstudo)
                        {
                            $contImagem++;
                            $imagemEstudo->uiid;
                            $link = new THyperLink("Abrir Imagem {$contImagem}", "engine.php?class=ClienteAtendimentoList&method=verImagem&id={$imagemEstudo->id}", '#333', '14px', '', 'fas:file-download #000000');
                            $link->class = 'btn btn-link';

                            $html->add($link);

                            $imagemEstudo->uiid;
                            $link = new THyperLink("Download da Imagem {$contImagem}", "engine.php?class=ClienteAtendimentoList&method=downloadImagem&id={$imagemEstudo->id}", '#333', '14px', '', 'fas:file-download #000000');
                            $link->class = 'btn btn-link';

                            $html->add($link);
                            $html->add('<hr>');
                        }
                    }

                    if($anexos || $documentos)
                    {
                        $row = new TTableRow;
                        $cell = $row->addCell($html);
                        $cell->colspan = 3;
                        $cell->style='padding:10px;';

                        // insert the new row
                        $this->datagrid->insert($cont + 1, $row);    
                    }

                    $cont+=3;

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new Atendimento($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

    public static function openDocument($param)
    {
        $file              = $param['file']??'';
        $info              = pathinfo($file);
        $extension         = $info['extension'];
        $content_type_list = ['pdf' => 'application/pdf'];

        if (file_exists($file) AND in_array(strtolower($extension), array_keys($content_type_list)))
        {
            $basename = basename($file);
            $filesize = filesize($file);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: " . $content_type_list[strtolower($extension)] );
            header("Content-Length: {$filesize}");
            header("Content-disposition: inline; filename=\"{$basename}\"");
            header("Content-Transfer-Encoding: binary");

            echo file_get_contents($file);
        }
        else
        {
            echo 'Oopss: Essa solicitação não é válida'; die;
        }
    }

    public static function gerarDocumento($param)
    {
        try 
        {
            TTransaction::open(self::$database);

            $documento = new Documento($param['id']);
            if($documento->atendimento->agendamento->cliente_id != TSession::getValue('cliente_id'))
            {
                throw new Exception('Permissão negada!');
            }

            TTransaction::close();
            $document = DocumentoDocument::onGenerate(['returnFile' => 1, 'key' => $documento->id]);

             $file             = $document;
            $info              = pathinfo($file);
            $extension         = $info['extension'];
            $content_type_list = ['pdf' => 'application/pdf'];

            if (file_exists($file) AND in_array(strtolower($extension), array_keys($content_type_list)))
            {
                $basename = basename($file);
                $filesize = filesize($file);

                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Content-type: " . $content_type_list[strtolower($extension)] );
                header("Content-Length: {$filesize}");
                header("Content-disposition: inline; filename=\"{$basename}\"");
                header("Content-Transfer-Encoding: binary");

                echo file_get_contents($file);
            }
            else
            {
                echo 'Oopss: Essa solicitação não é válida'; die;
            }

        } 
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function verImagem($param)
    {
        try 
        {
            TTransaction::open(self::$database);

            $imagensEstudo = new ImagemEstudo($param['id']);
            if($imagensEstudo->atendimento->agendamento->cliente_id != TSession::getValue('cliente_id'))
            {
                throw new Exception('Permissão negada!');
            }

            TTransaction::close();

            $ret = BuilderHttpClientService::post('https://auth.escritorioatenas.com.br/auth/realms/dcm4che/protocol/openid-connect/token', [], null, [], [
                    CURLOPT_POSTFIELDS => http_build_query([
                    'client_id' => 'patient-cli',
                    'client_secret' => '2618d2ce-4832-4bf7-95f9-9f38e467b55d',
                    'grant_type' => 'password',
                    'scope' => 'openid',
                    'username' => 'user-patient-exam',
                    'password' => 'R3s#rvuf&24mR3s#rvuf&24m'
                ])
            ]);

            echo "<script> location.href='https://pacs.escritorioatenas.com.br/weasis-pacs-connector/weasis?studyUID={$imagensEstudo->uuid}&access_token={$ret->access_token}' </script>";
        } 
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function downloadImagem($param)
    {
        try 
        {
            TTransaction::open(self::$database);

            $imagensEstudo = new ImagemEstudo($param['id']);
            if($imagensEstudo->atendimento->agendamento->cliente_id != TSession::getValue('cliente_id'))
            {
                throw new Exception('Permissão negada!');
            }

            TTransaction::close();

            $ret = BuilderHttpClientService::post('https://auth.escritorioatenas.com.br/auth/realms/dcm4che/protocol/openid-connect/token', [], null, [], [
                    CURLOPT_POSTFIELDS => http_build_query([
                    'client_id' => 'patient-cli',
                    'client_secret' => '2618d2ce-4832-4bf7-95f9-9f38e467b55d',
                    'grant_type' => 'password',
                    'scope' => 'openid',
                    'username' => 'user-patient-exam',
                    'password' => 'R3s#rvuf&24mR3s#rvuf&24m'
                ])
            ]);

            echo "<script> location.href='https://pacs.escritorioatenas.com.br/dcm4chee-arc/aets/ATENAS/rs/studies/{$imagensEstudo->uuid}?accept=application/zip&access_token={$ret->access_token}' </script>";
        } 
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

}

