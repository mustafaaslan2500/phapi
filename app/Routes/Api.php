<?php

namespace App\Routes;

use App\Controllers\UserControllerNew;
use App\Controllers\AddressControllerNew;
use App\Controllers\PerformanceController;
use App\Controllers\MediaController;
use App\Helpers\ApiHelpers;

class Api
{
    public function handleRequest()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPostData = $_POST;

        // ===========================================
        // API ENDPOINTS - TEMİZ MİMARİ
        // ===========================================
        
        // User Authentication (JWT)
        if ($requestUri === '/user/login' && $requestMethod === 'POST') {
            $controller = new UserControllerNew();
            $res = $controller->login();
        } else if ($requestUri === '/user/register' && $requestMethod === 'POST') {
            $controller = new UserControllerNew();
            $res = $controller->register();
        } else if ($requestUri === '/user/logout' && $requestMethod === 'POST') {
            $controller = new UserControllerNew();
            $res = $controller->logout();
        } else if ($requestUri === '/user/refresh-token' && $requestMethod === 'POST') {
            $controller = new UserControllerNew();
            $res = $controller->refreshToken();
        } else if ($requestUri === '/user/is_login' && $requestMethod === 'GET') {
            $controller = new UserControllerNew();
            $res = $controller->loginControl();
        } else if ($requestUri === '/user/google-login' && $requestMethod === 'POST') {
            $controller = new UserControllerNew();
            $res = $controller->googleLogin();
        } else if ($requestUri === '/user/me' && $requestMethod === 'GET') {
            $controller = new UserControllerNew();
            $res = $controller->getMyInfo();
        
        // Address Management
        } else if ($requestUri === '/address/list' && $requestMethod === 'GET') {
            $controller = new AddressControllerNew();
            $res = $controller->getAddresses();
        } else if ($requestUri === '/address/create' && $requestMethod === 'POST') {
            $controller = new AddressControllerNew();
            $res = $controller->createAddress();
        } else if ($requestUri === '/address/update' && $requestMethod === 'POST') {
            $controller = new AddressControllerNew();
            $res = $controller->updateAddress();
        } else if ($requestUri === '/address/delete' && $requestMethod === 'POST') {
            $controller = new AddressControllerNew();
            $res = $controller->deleteAddress();
        
        // Performance Test Routes
        } else if ($requestUri === '/performance/test' && $requestMethod === 'GET') {
            $controller = new PerformanceController();
            $res = $controller->testConnections();
        } else if ($requestUri === '/performance/info' && $requestMethod === 'GET') {
            $controller = new PerformanceController();
            $res = $controller->connectionInfo();
        
        // Media Management
        } else if ($requestUri === '/media/upload' && $requestMethod === 'POST') {
            $controller = new MediaController();
            $res = $controller->upload();
        } else if (preg_match('#^/media/([a-f0-9]{32})$#', $requestUri, $matches) && $requestMethod === 'GET') {
            $_GET['unique_id'] = $matches[1];
            $controller = new MediaController();
            $res = $controller->getMedia();
        } else if ($requestUri === '/media/list' && $requestMethod === 'GET') {
            $controller = new MediaController();
            $res = $controller->listMedia();
        } else if (preg_match('#^/media/([a-f0-9]{32})$#', $requestUri, $matches) && $requestMethod === 'DELETE') {
            $_GET['unique_id'] = $matches[1];
            $controller = new MediaController();
            $res = $controller->deleteMedia();
        
        } else {
            http_response_code(404);
            $res = ['error' => 'Route not found'];
        }

        // Güvenli JSON output (UTF-8 sanitize + error handling)
        if (is_array($res)) {
            ApiHelpers::safe_json_response($res);
        } else {
            // Controller string veya başka tip dönerse sarmala
            ApiHelpers::safe_json_response([
                'status' => true,
                'message' => 'ok',
                'data' => $res
            ]);
        }
    }
}
