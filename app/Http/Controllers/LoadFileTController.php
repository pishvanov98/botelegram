<?php

namespace App\Http\Controllers;



class LoadFileTController extends Controller
{

    public function load($id){

        $get = array(
            'file_id'  => $id
        );

        $ch = curl_init('https://api.telegram.org/bot'.env('TELEGRAM_TOKEN').'/getFile?' . http_build_query($get));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($html, true);

        if(!empty($result['result']['file_path'])){
            $ch = curl_init('https://api.telegram.org/file/bot'.env('TELEGRAM_TOKEN').'/'.$result['result']['file_path']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $html_file = curl_exec($ch);
            curl_close($ch);


            file_put_contents(public_path('file\Nomenclatura.zip'), $html_file);


            $zip = new \ZipArchive;

if ($zip->open(public_path('file\Nomenclatura.zip')) === TRUE) {

    // путь к каталогу, в который будут помещены файлы
    $zip->extractTo(public_path('file'));

    $zip->close();
}
            return true;
        }else{
            return false;
        }

    }

}
