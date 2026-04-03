<?php

namespace App\Factories;

use Google_Client;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Google Client Factory
 * Google OAuth client instance'ları oluşturur
 */
class GoogleClientFactory
{
    /**
     * Güvenli Google Client oluştur (SSL ayarlarıyla)
     */
    public static function create(): Google_Client
    {
        $client = new Google_Client();
        
        // Environment'a göre SSL doğrulama
        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        
        $httpClient = new GuzzleClient([
            'verify' => $isProduction, // Production'da true, development'ta false
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => $isProduction,
                CURLOPT_SSL_VERIFYHOST => $isProduction ? 2 : 0,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]
        ]);
        
        $client->setHttpClient($httpClient);
        
        return $client;
    }
    
    /**
     * Credential'larla yapılandırılmış client oluştur
     */
    public static function createWithCredentials(): Google_Client
    {
        $client = self::create();
        
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
        
        return $client;
    }
}
