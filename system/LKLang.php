<?php

class LKLang
{
    public static function parse($lklang){
        $output = array();
        $lklang = trim($lklang);
        if(preg_match_all('/(?P<name>[^\ ]*)=(?P<value>[^\ ]*);/iU', $lklang, $output_array)){
            for($i = 0; $i < sizeof($output_array[0]);$i++){
                $output[trim($output_array["name"][$i])] = str_replace('\n', "\n", trim($output_array["value"][$i]));
            }
        }
        return $output;
    }

    public static function get($name) {
        ob_start(); // Начинаем сохрание выходных данных в буфер
        include ($name); // Отправляем в буфер содержимое файла
        $text = trim(trim(ob_get_clean(), "\xEF\xBB\xBF"), '﻿'); // Очищаем буфер и возвращаем содержимое
        return $text; // Возвращение текста из файла
    }
}