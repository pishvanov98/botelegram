<?php

namespace App\Conversations;
use app\messengerUser;

use App\messengerUser as database;
use App\nomenklatura;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;

use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;

use App\Http\Controllers\XmlDecodeController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class mainConversation extends conversation
{

    public $response = [];

    public function run () {

        try {
            switch ($this->bot->getConversationAnswer()) {
                case "/NomenclaturaFile":
                    $this->NomenclaturaFile();
                    break;
                case "/start":
                    $this->ShowButton();
                    break;
                default:
                    $this->ShowButton();
            }
        } catch (Exception $e) {
            $this->bot->reply("Ошибка");
        }

    }

    private function ShowConversationStart(){
        $question = BotManQuestion::create("Введите код номенклатуры");

        $this->ask( $question, function ( BotManAnswer $answer ) {
            if( $answer->getText () != '' ){
//                array_push ($this->response, $answer->getText());
//                info($answer->getText());
                $this->extracted($answer);
            }
        });
    }





    //метод для загрузки номенклатуры для заполнения бота местаХранения
    private function NomenclaturaFile(){
        $XmlDecodeController=new XmlDecodeController();
        if (file_exists(public_path('file\Nomenclatura.xml'))){
            $decode_xml_nomenklatura= $XmlDecodeController->decode(public_path('file\Nomenclatura.xml'));
            $this->bot->reply("Найдено ".count($decode_xml_nomenklatura['Товар'])." записей, Обновляю базу...");
            $this->LoadToDbNomenklatura($decode_xml_nomenklatura['Товар']);
        }else{
            $this->bot->reply("Файла для обновления нет...");
        }
        return true;

    }

//    private function setName() {
//        $question = BotManQuestion::create("Привет! Как тебя зовут?");
//
//        $this->ask( $question, function ( BotManAnswer $answer ) {
//            if( $answer->getText () != '' ){
//                array_push ($this->response, $answer->getText());
//
//                $this->askWeather ();
//            }
//        });
//    }

    private function ShowButton () {
        $question = BotManQuestion::create("Выберите что нужно сделать");

        $question->addButtons( [
            Button::create('Показать места номенклатуры')->value(1)//,
            //Button::create('Нет')->value(2)
        ]);

        $this->ask($question, function (BotManAnswer $answer) {

            if($answer->getText() == 1){
                $this->ShowConversationStart();
            }else{
                $this->run();
            }

        });
    }

//    private function exit() {
//        $db = new database();
//        $db->id_chat    = $this->bot->getUser()->getId();
//        $db->name       = $this->response[0];
//        $db->response   = $this->response[1];
//        $db->save();
//
//        $attachment = new Image('https://gykov.ru/projects/botelegram.png');
//
//        $message = OutgoingMessage::create('До новых встреч!')
//            ->withAttachment($attachment);
//        $this->bot->reply($message);
//
//        return true;
//    }

    private function LoadToDbNomenklatura($data){
//        info($data);
        if(!empty($data)){
            $now = Carbon::now();
            $update_file=DB::table("update_file")->where('name_file', 'file\Nomenclatura.xml')->value('name_file');
            if (!empty($update_file)){
                $date_file=DB::table("update_file")->where('name_file', 'file\Nomenclatura.xml')->whereDate('updated_at', '<', $now)->value('updated_at');
                if(!empty($date_file)){
//                    info($date_file);
                    info("записи устарели, удаляем и обновляем на новые");
                    DB::table('nomenklatura')->truncate();
                    $this->addToBdXmlNomenclatura($data);
                    DB::table("update_file")->where('name_file', 'file\Nomenclatura.xml')->update(['updated_at'=>$now]);
                    $this->bot->reply("База обновлена.");
                }else{
//                    info($date_file);
                    info("день еще не прошел с последнего обновления");
                    $this->bot->reply("База не обновлена, день еще не прошел с последнего обновления.");
                }
            }else{
                DB::table('nomenklatura')->truncate();
                $this->addToBdXmlNomenclatura($data);

                DB::table('update_file')->insert([
                    'name_file' => "file\Nomenclatura.xml",
                    'updated_at' => $now,  // remove if not using timestamps
                    'created_at' => $now   // remove if not using timestamps
                ]);
                $this->bot->reply("База обновлена.");
            }

        }

    }
    private function addToBdXmlNomenclatura($data){
        $now = Carbon::now();
        $mass_nomenclature=array();
        foreach ($data as $item){
            $mass_nomenclature[] = [
                'kod_nomenklatura' => $item['@attributes']['Код'],
                'name_nomenklatura' => $item['@attributes']['Номенклатура'],
                'harakteristic_nomenklatura' => $item['@attributes']['ХарактеристикаНоменклатуры'],
                'storage_nomenklatura' => $item['@attributes']['МестоХранения'],
                'updated_at' => $now,  // remove if not using timestamps
                'created_at' => $now   // remove if not using timestamps
            ];


        }

        foreach (array_chunk($mass_nomenclature,1000) as $t)
        {
            DB::table('nomenklatura')->insert($t);
        }
    }

    private function CheckFileOldNomenclatura(){
        $now = Carbon::now();
        $name_file=DB::table("update_file")->where('name_file', 'file\Nomenclatura.xml')->whereDate('updated_at', '<', $now)->value('name_file');
        if(!empty($name_file)){
            $XmlDecodeController=new XmlDecodeController();
            if (file_exists(public_path('file\Nomenclatura.xml'))){
                $decode_xml_nomenklatura= $XmlDecodeController->decode(public_path('file\Nomenclatura.xml'));
                if(!empty($decode_xml_nomenklatura)){
                    DB::table('nomenklatura')->truncate();
                    $this->addToBdXmlNomenclatura($decode_xml_nomenklatura['Товар']);

                    DB::table('update_file')->where('name_file','file\Nomenclatura.xml')->update([
                        'updated_at' => $now  // remove if not using timestamps
                    ]);

                }
            }
        }
    }


    private function getNomenklatura($num){

       $data=DB::table('nomenklatura')->where('kod_nomenklatura', 'LIKE', '%'  .$num. '%')->get()->toArray();

        if(!empty($data)){
            return $data;
        }else{
            $question = BotManQuestion::create('Не найдено, попробуйте еще раз');

            $this->ask( $question, function ( BotManAnswer $answer ) {
                if( $answer->getText () != '' ){
                    $this->extracted($answer);
                }
            });
        }


    }

    private function OutNomenklatura($data){
    $text='';
    $i=0;
    $message_mass=array();
    foreach ($data as $item_out){
        $resultArray = json_decode(json_encode((array)$item_out), true);
        $key=$resultArray['kod_nomenklatura'].$resultArray['harakteristic_nomenklatura'];//проверка, если у товара всё одинаково кроме места хранения дописываем элементу массива еще одно место хванение, если различие есть в характеристике то создаем новый элемент
        if(!empty($message_mass[$key]) && $message_mass[$key]['name_nomenklatura'] == $resultArray['name_nomenklatura'] && $message_mass[$key]['harakteristic_nomenklatura'] == $resultArray['harakteristic_nomenklatura'] && $message_mass[$key]['storage_nomenklatura'] != $resultArray['storage_nomenklatura']){

            $message_mass[$key]['storage_nomenklatura']=$message_mass[$key]['storage_nomenklatura']. ' , '.$resultArray['storage_nomenklatura'];

        }else{
            $message_mass[$key]=$resultArray;
        }
    }
        //info($message_mass);

        foreach ($message_mass as $item_message){

            $text.='<b>Код номенклатуры: </b>'.$item_message['kod_nomenklatura']. PHP_EOL ;
            $text.='<b>Наименование: </b>'.$item_message['name_nomenklatura']. PHP_EOL ;
            $text.='<b>Характеристика: </b>'.$item_message['harakteristic_nomenklatura']. PHP_EOL;
            $text.='<b>Место хранения: </b>'.$item_message['storage_nomenklatura']. PHP_EOL.PHP_EOL;
            $i++;
        }



    if($i > 1){
        $text= 'Найдено несколько результатов:'.PHP_EOL.PHP_EOL.$text;
    }

        $question = BotManQuestion::create($text);

        $this->ask( $question, function ( BotManAnswer $answer ) {
            if( $answer->getText () != '' ){
                $this->extracted($answer);
            }
        },['parse_mode' => 'HTML']);




    }

    /**
     * @param BotManAnswer $answer
     * @return void
     */
    private function extracted(BotManAnswer $answer): void
    {
        if ($answer->getText() == 'stop'|| $answer->getText() == '/start') {
//            $this->stopsConversation();
              $this->run();
        } else {
            if (is_numeric($answer->getText())) {
                if(strlen($answer->getText()) <= 4){
                    $question = BotManQuestion::create("В коде номенклатуры должно содержаться более 4 цифр");
                    $this->ask( $question, function ( BotManAnswer $answer ) {
                        if( $answer->getText () != '' ){
                            $this->extracted($answer);
                        }
                    });
                }else{
                    $this->CheckFileOldNomenclatura();
                    $info_nimenklatura = $this->getNomenklatura($answer->getText());
                    if (!empty($info_nimenklatura)) {
                        $this->OutNomenklatura($info_nimenklatura);
                    }
                }
            } else {
                $this->ShowConversationStart();
            }
        }
    }


}