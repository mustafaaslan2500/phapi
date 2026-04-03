<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * JWT Authentication Service
 * Stateless, ultra-fast, pure JWT - No Redis, No Database
 */
class JWTService
{
    private string $secretKey;
    private string $issuer;
    private int $accessTokenExpiry = 900; // 15 dakika
    private int $refreshTokenExpiry = 604800; // 7 gün

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET_KEY'] ?? 'your-secret-key-change-in-production';
        $this->issuer = $_ENV['APP_URL'] ?? 'localhost';
    }

    /**
     * Access token oluştur (kısa ömürlü - API çağrıları için)
     */
    public function createAccessToken(int $userId, string $email, ?string $passwordHash = null): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenExpiry;

        $payload = [
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'email' => $email,
            'type' => 'access',
            'pwd_hash' => $passwordHash ? substr(md5($passwordHash), 0, 8) : null // Şifre değişince invalidate
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Refresh token oluştur (uzun ömürlü - token yenilemek için)
     */
    public function createRefreshToken(int $userId): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->refreshTokenExpiry;

        $payload = [
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Token'ı doğrula ve decode et (güvenlik kontrolleri ile)
     */
    public function verifyToken(string $token, ?string $currentPasswordHash = null): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Şifre değişmişse token'ı invalidate et
            if ($currentPasswordHash && isset($decoded->pwd_hash)) {
                $currentHash = substr(md5($currentPasswordHash), 0, 8);
                if ($decoded->pwd_hash !== $currentHash) {
                    return null; // Şifre değişmiş, token geçersiz
                }
            }
            
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Token'dan user ID al
     */
    public function getUserIdFromToken(string $token): ?int
    {
        $decoded = $this->verifyToken($token);
        return $decoded ? (int) $decoded->user_id : null;
    }

    /**
     * Logout - Client-side token silme yeterli (stateless)
     * Blacklist yok, token expiry ile güvenlik sağlanır
     */
    public function logout(): bool
    {
        // JWT stateless - logout client-side token silme ile yapılır
        // Token 15 dakika sonra otomatik expire olur
        return true;
    }

    /**
     * Bearer token'ı header'dan al
     */
    public static function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Token pair oluştur (access + refresh)
     */
    public function createTokenPair(int $userId, string $email, ?string $passwordHash = null): array
    {
        return [
            'access_token' => $this->createAccessToken($userId, $email, $passwordHash),
            'refresh_token' => $this->createRefreshToken($userId),
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry
        ];
    }
    
    /**
     * HTTPS zorunluluğunu kontrol et (production)
     */
    public static function enforceHttps(): ?array
    {
        $isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                   || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
                   || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        
        if ($isProduction && !$isHttps) {
            http_response_code(426);
            return [
                'status' => false,
                'message' => 'HTTPS required for secure token transmission'
            ];
        }
        
        return null;
    }
}
