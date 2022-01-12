<?php namespace Waka\MsGraph\Classes\Helpers;

use File;
use Storage;

/**
 * Description of Gd
 *
 * @author charles saint olive
 */
class ParseMsData
{
    public static function listSites($value)
    {
        if(!$value) {
            return [];
        }
        $finalValues = [];
        $values = $value['value'];
        //trace_log($values);
        foreach($values as $item) {
            if($item['displayName'] ?? false) {
                array_push($finalValues, ['name' => $item['displayName'], 'code' => $item['id']]);
            }
           
        }
        return $finalValues;
    }

    public static function listGroups($value)
    {
        if(!$value) {
            return [];
        }
        $finalValues = [];
        $values = $value['value'];
        //trace_log($values);
        foreach($values as $item) {
            if($item['displayName'] ?? false) {
                array_push($finalValues, ['name' => $item['displayName'], 'code' => 'groups/'.$item['id']]);
            }
           
        }
        return $finalValues;
    }

    // public static function listGroups($value, $prepend)
    // {
    //     if(!$value) {
    //         return [];
    //     }
    //     $finalValues = [];
    //     $values = $value['value'];
    //     foreach($values as $value) {
    //         array_push($finalValues, ['name' => $value['displayName'], 'code' => $value['webUrl']]);
    //     }
    //     return $finalValues;
    // }

}