<?php namespace Waka\MsGraph\Classes\Cloud;

use File;
use Storage;

/**
 * Description of Gd
 *
 * @author charles saint olive
 */
class MsDrive
{
    
    /**
     * Absence d'interface pour le moement les fonctionq communes aux autres Classes de Cloud sont dans cette première partie.
     */
    public function put($pathAndFileName, $content) {
        
        return \MsGraphAdmin::files()->upload($pathAndFileName, $content);
    }
    public function listFolderItems($folderPath)
    {
        return \MsGraphAdmin::files()->getChilds($folderPath);
    }
    public function getRawFile($pathAndFIleName)
    {
        return \MsGraphAdmin::files()->getFileUrlContent($pathAndFIleName);
    }
    //
    


    /**
     * Interne *****************************************************************
     */
}
