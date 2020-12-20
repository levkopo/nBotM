<?php

interface Bot
{
    public function __construct($func);

    public function verify($secretKey);

    public function onNewMessage($data, $client_info);

    public function call($type, $data);

    public function VKApp_call($data);

    public function getInfo($data);

    public function onNewAppMessage($data);
}