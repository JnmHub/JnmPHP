<?php

namespace App\Core;

class Request
{
    public static function formData(){
        $jsonData = file_get_contents('php://input');
        define("JSON", empty($jsonData) ? [] : json_decode($jsonData, true));
        define("GET",$_GET);
        define("POST",$_POST);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 解析失败，返回错误信息（HTTP 400 表示“请求参数错误”）
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'message' => 'JSON格式错误: ' . json_last_error_msg()
            ]);
            exit;
        }
    }
}