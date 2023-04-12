<?php

namespace App\Http\Controllers;



class XmlDecodeController extends Controller
{

    public function decode(){
        $xmlDataString = file_get_contents(public_path('file\Nomenclatura.xml'));
        $xmlObject = simplexml_load_string($xmlDataString);

        $json = json_encode($xmlObject);
        $phpDataArray = json_decode($json, true);
        return $phpDataArray;
    }

}
