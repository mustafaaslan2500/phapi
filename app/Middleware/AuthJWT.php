<?php

namespace App\Middleware;

use App\Services\JWTService;
use App\Helpers\ApiHelpers;

/**
 * JWT Authentication Middleware
 * Stateless auth - session yok, DB query yok
 */
class AuthJWT
{
    private static JWTService $jwtService;

    /**
     * JWT servisini lazy olarak al
     */
    private static function getJWTService(): JWTService
    {
        if (!isset(self::$jwtService)) {
            self::$jwtService = new JWTService();
        }
        return self::$jwtService;
    }

    /**
     * Token'ı doğrula ve user ID döndür
     */
    public static function authenticate(): ?int
    {
        $token = JWTService::getBearerToken();

        if (!$token) {
            return null;
        }

        $jwtService = self::getJWTService();
        return $jwtService->getUserIdFromToken($token);
    }

    /**
     * Auth gerekli - yoksa error dön
     */
    public static function requireAuth(array $lang = []): ?array
    {
        $userId = self::authenticate();

        if (!$userId) {
            http_response_code(401);
            return ApiHelpers::show_message(
                false, 
                $lang['please_login'] ?? 'Unauthorized - Please provide valid JWT token'
            );
        }

        return null; // Auth OK
    }

    /**
     * Authenticated user ID al
     */
    public static function getUserId(): ?int
    {
        return self::authenticate();
    }
}
