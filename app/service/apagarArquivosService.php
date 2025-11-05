<?php

class apagarArquivosService
{
    public static function unlinkRecursive($dir, $deleteRootToo) 
    { 
        if(!$dh = @opendir($dir)) 
        { 
            return; 
        } 
        while (false !== ($obj = readdir($dh))) 
        { 
            if($obj == '.' || $obj == '..') 
            { 
                continue; 
            } 
    
            if (!@unlink($dir . '/' . $obj)) 
            { 
                unlinkRecursive($dir.'/'.$obj, true); 
            } 
        } 
        closedir($dh); 
        if ($deleteRootToo) 
        { 
            @rmdir($dir); 
        } 
        return; 
    } 
}
