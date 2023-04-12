<?php

namespace App\Conversations;
use app\messengerUser;

use App\messengerUser as database;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;

use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;

use App\Http\Controllers\XmlDecodeController;

class mainConversation extends conversation
{

    public $response = [];

    public function run () {

        try {
            switch ($this->bot->getConversationAnswer()) {
                case "/NomenclaturaFile":
                    $this->NomenclaturaFile();
                    break;
                default:
                    $this->setName();
            }
        } catch (Exception $e) {
            $this->bot->reply("Ошибка");
        }



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

    private function setName() {
        $question = BotManQuestion::create("Привет! Как тебя зовут?");

        $this->ask( $question, function ( BotManAnswer $answer ) {
            if( $answer->getText () != '' ){
                array_push ($this->response, $answer->getText());

                $this->askWeather ();
            }
        });
    }

    private function askWeather () {
        $question = BotManQuestion::create("Тебе нравится погода на улице?");

        $question->addButtons( [
            Button::create('Да')->value(1),
            Button::create('Нет')->value(2)
        ]);

        $this->ask($question, function (BotManAnswer $answer) {
            // здесь можно указать какие либо условия, но нам это не нужно сейчас

            array_push ($this->response, $answer);

            $this->exit();
        });
    }

    private function exit() {
        $db = new database();
        $db->id_chat    = $this->bot->getUser()->getId();
        $db->name       = $this->response[0];
        $db->response   = $this->response[1];
        $db->save();

        $attachment = new Image('https://gykov.ru/projects/botelegram.png');

        $message = OutgoingMessage::create('До новых встреч!')
            ->withAttachment($attachment);
        $this->bot->reply($message);

        return true;
    }

    private function LoadToDbNomenklatura($data){
        info("yes");
    }

}