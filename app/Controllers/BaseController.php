<?php

namespace App\Controllers;

use App\Http\Request;
use App\Helpers\ApiHelpers;
use App\Helpers\Lang;
use App\Middleware\AuthJWT;
use App\Services\JWTService;

/**
 * Base Controller (JWT-Based)
 * Stateless - No sessions
 */
abstract class BaseController
{
    protected Request $request;
    protected array $lang;
    protected string $locale;
    protected ?int $userId;
    protected ?string $bearerToken; // JWT token

    public function __construct()
    {
        // HTTPS zorunluluğu (production)
        $httpsError = JWTService::enforceHttps();
        if ($httpsError) {
            http_response_code(426);
            echo json_encode($httpsError);
            exit;
        }

        // Locale ayarla (header'dan veya default)
        $this->locale = $this->getLocaleFromHeader();
        Lang::setLanguage($this->locale);

        // Request nesnesi oluştur
        $this->request = new Request();

        // JWT token'ı al (bir kere)
        $this->bearerToken = JWTService::getBearerToken();

        // JWT'den authenticated user ID al
        $this->userId = AuthJWT::getUserId();
    }

    /**
     * Header'dan locale al
     */
    private function getLocaleFromHeader(): string
    {
        $headers = getallheaders();
        return $headers['Accept-Language'] ?? $headers['accept-language'] ?? 'tr';
    }

    /**
     * Language dosyasını yükle
     */
    protected function loadLang(string $langFile): void
    {
        $this->lang = Lang::importLang($langFile);
    }

    /**
     * Başarılı response dön
     */
    protected function success(string $message = '', array $data = []): array
    {
        return ApiHelpers::show_message(true, $message, $data);
    }

    /**
     * Hatalı response dön
     */
    protected function error(string $message, array $data = []): array
    {
        return ApiHelpers::show_message(false, $message, $data);
    }

    /**
     * JWT Authentication kontrolü yap
     * Giriş yapmamışsa hata dön
     */
    protected function requireAuth(): ?array
    {
        return AuthJWT::requireAuth($this->lang);
    }

    /**
     * Request parametrelerini al
     */
    protected function getParams(): array
    {
        return $this->request->all();
    }

    /**
     * Tek bir parametre al
     */
    protected function getParam(string $key, mixed $default = null): mixed
    {
        return $this->request->get($key, $default);
    }

    /**
     * JSON response dön (direkt çıktı)
     */
    protected function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
