<?php
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header("Access-Control-Allow-Origin: *");

ini_set('display_errors',1);
error_reporting(E_ALL);
set_error_handler("errorHandler");
ini_set('log_errors', 1);
ini_set('error_log', './logs/errors.log');

//Security
if (!isset($_REQUEST)) {
    http_response_code(404);
    die();
}

//include libraries
include_once "system/Functions.php";
include_once "system/Statistics.php";
include_once "system/LKLang.php";
include_once "system/Bot.php";

//load botM config
$config = LKLang::parse(LKLang::get("system/data/config.lklang"));

//Get data
$data = json_decode(file_get_contents('php://input'));
if(!isset($data)&&$data==null){
    die;
}

//Include bot
$bot_config = LKLang::parse(LKLang::get("bots/{$data->group_id}/data/config.lklang"));
$bot_func = new Functions($config, $bot_config);
$statistics = new Statistics($data->group_id);
include "bots/{$data->group_id}/".$bot_config["file"];
$bot = new $bot_config["classname"]($bot_func);

//Verify secret key
if($bot->verify($data->secret)==false){
    http_response_code(404);
    die();
}

$statistics->writeCallType($data->type);

if($data->type=="confirmation"){
    echo $bot_config["confirmation_code"];
    http_response_code(200);
    exit;
}

if($data->type=="message_new") {
    $bot->call($data->type, $data->object);
    if(json_decode($data->object->message->payload)->command=="--info"){
        $bot->getInfo($data->object->message);
    }else {
        $bot->onNewMessage($data->object->message, $data->object->client_info);
    }
}else if($data->type=="app_message"){
    $bot->onNewAppMessage($data->data);
    exit;
}else{
    $bot->call($data->type, $data->object);
}

$files = glob('tmp/*'); // get all file names
foreach($files as $file){ // iterate files
    if(is_file($file))
        unlink($file); // delete file
}

$statistics->close();
echo "ok";
exit;

function errorHandler($errno, $errstr, $errfile, $errline){
    $json = json_decode(file_get_contents(__DIR__."/logs/results.json"));
    $json->errors[] = array(
        $errno, $errstr, $errfile, $errline, time()
    );

    file_put_contents(__DIR__."/logs/results.json", json_encode($json));
}

