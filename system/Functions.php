<?php

class Functions
{
    public $keyboard = array(
        "one_time" => false,
        "inline"=>false,
        "buttons" => array(
            array(
                array(
                    "action"=>array(
                        "type"=>"text",
                        "label"=>"Помощь",
                        "payload"=>"{\"command\":\"--info\"}"
                    ),
                    "color" =>"primary"
                )
            )
        )
    );

    private $lang = "ru";

    private $config;

    private $access_token;

    private $bot_cfg;

    public function __construct($config, $bot_cfg)
    {
        $this->bot_cfg = $bot_cfg;
        $this->access_token = $bot_cfg["access_token"];
        $this->config = $config;
        if(isset($bot_cfg["keyboard_help_button"])&&$bot_cfg["keyboard_help_button"]=="false"){
            unset($this->keyboard["buttons"][0]);
        }
        if(isset($bot_cfg["vk_app_id"])){
            $label = "Открыть бота в VK";
            if(isset($bot_cfg["vk_app_label"])) {
                $label = $bot_cfg["vk_app_label"];
            }

            $this->keyboard["buttons"][0][] = array(
                "action" => array(
                    "type" => "open_app",
                    "app_id" => $bot_cfg["vk_app_id"],
                    "owner_id"=> -184913990,
                    "hash" => "sendKeyboard",
                    "label" => $label
                )
            );
        }
    }

    function createUserLink($peer_id, $name_case = "nom"){
        $userdata = $this->request("users.get", array("user_ids"=>$peer_id, "name_case"=>$name_case));
        return "[id{$peer_id}|{$userdata->response[0]->first_name} {$userdata->response[0]->last_name}]";
    }

    function setOutline($boolean){
        if ($this->keyboard!=null)
            $this->keyboard["inline"] = $boolean;
    }

    public function init_client_info($client_info){
        $langs = array("ru", "uk", "be", "en", "es", "fi", "de", "it");
        $this->lang = $langs[$client_info->lang_id];
        if(isset($client_info->keyboard)&&$client_info->keyboard){
            if(isset($client_info->inline_keyboard))
                $this->keyboard["inline"] = true;
        }else{
            $this->keyboard = null;
        }
    }

    public function getLang($class_dir){
        return LKLang::parse(LKLang::get($class_dir."/lang/strings.lklang"));
    }

    public function getFile($class_dir, $filename){
        return LKLang::get($class_dir."/data/$filename");
    }

    public function sendMessage($peer_id, $message, $attachment=""){
        return $this->request("messages.send", array("attachment"=>$attachment,"message"=>$message, "peer_id"=>$peer_id, "keyboard"=>json_encode($this->keyboard), "random_id"=>rand(0, 1000)+$peer_id));
    }

    public function  loadModule($module, $data){
        $module->init($data->peer_id);
        return $module;
    }

    public function sendMessageWithParams($peer_id, $params){
        if($params!=''||null){
            $params["peer_id"] = $peer_id;
            $params["random_id"] = rand(0, 1000)+$peer_id;
            $params["keyboard"] = json_encode($this->keyboard);
            return $this->request("messages.send", $params);
        }
        return array();
    }

    public function sendMessageForApp($array){
        echo json_encode(array("message"=>$array));
        die;
    }

    public function request($method, $params = array()){
        if(!isset($params["access_token"]))
            $params["access_token"] = $this->access_token;
        $params["v"] = $this->config['api_version'];
        $params = http_build_query($params);
        return $this->_request($this->config['api_url'].'method/'.$method.'?'.$params);
    }

    public function _request($url){
        $result = file_get_contents($url);
        if($result){
            $result = trim($result, "\xEF\xBB\xBF");
            return json_decode($result);
        }
        return array();
    }

    function vkApi_photosGetMessagesUploadServer($peer_id) {
        return $this->request('photos.getMessagesUploadServer', array(
            'peer_id' => $peer_id,
        ));
    }

    function vkApi_photosGetOwnerCoverPhotoUploadServer($group_id) {
        return $this->request('photos.getOwnerCoverPhotoUploadServer', array(
            'group_id' => $group_id,
            "crop_x" => 0,
            "crop_y" => 0,
            "crop_x2" => 6000,
            "crop_y2" => 1509,
        ));
    }

    function vkApi_photosSaveMessagesPhoto($photo, $server, $hash) {
        return $this->request('photos.saveMessagesPhoto', array(
            'photo' => $photo,
            'server' => $server,
            'hash' => $hash,
        ));
    }

    function vkApi_photosSaveOwnerCoverPhoto($photo, $hash) {
        return $this->request('photos.saveOwnerCoverPhoto', array(
            'photo' => $photo,
            'hash' => $hash,
        ));
    }

    function vkApi_upload($url, $file_name) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile(realpath($file_name))));
        $json = curl_exec($curl);
        if($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            $this->sendMessage(432176401, "cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($curl);
        return json_decode($json);
    }

    //Загрузка фотографии в сообщение от бота
    function uploadPhoto($user_id, $file_name) {
        $upload_server_response = $this->vkApi_photosGetMessagesUploadServer($user_id);
        $upload_response = $this->vkApi_upload($upload_server_response->response->upload_url, $file_name);
        $save_response = $this->vkApi_photosSaveMessagesPhoto($upload_response->photo, $upload_response->server, $upload_response->hash);
        return $save_response;
    }

    function uploadCover($group_id, $file_name) {
        $upload_server_response = $this->vkApi_photosGetOwnerCoverPhotoUploadServer($group_id);
        $upload_response = $this->vkApi_upload($upload_server_response->response->upload_url, $file_name);
        $save_response = $this->vkApi_photosSaveOwnerCoverPhoto($upload_response->photo, $upload_response->hash);
        return $save_response;
    }

    function saveTMPFile($url){
        $random_name = rand(0, 100).".png";
        $img = './tmp/'.$random_name;
        copy($url, $img);
        return array("name"=>$random_name, "dir"=>$img);
    }

    function saveTMPFile2($url){
        $random_name = rand(0, 100).".ogg";
        $img = './tmp/'.$random_name;
        copy($url, $img);
        return array("name"=>$random_name, "dir"=>$img);
    }
}