<?php

namespace App\Middleware;

use App\Helpers\ApiHelpers;
use App\Helpers\Lang;

/**
 * Authentication Middleware
 * Session kontrolü ve kullanıcı doğrulaması
 */
class AuthMiddleware
{
    /**
     * Kullanıcının giriş yapıp yapmadığını kontrol et
     */
    public static function check(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Giriş yapmış kullanıcı ID'sini al
     */
    public static function getUserId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Kullanıcı token'ını al
     */
    public static function getUserToken(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION['user_token'] ?? null;
    }

    /**
     * Giriş yapmış kullanıcı gerekli
     * Giriş yapmamışsa hata dön
     */
    public static function requireAuth(): array|null
    {
        if (!self::check()) {
            $lang = Lang::importLang("user_page_lang");
            return ApiHelpers::show_message(false, $lang['please_login'] ?? 'Please login');
        }

        return null;
    }

    /**
     * Session başlat (eğer başlamamışsa)
     */
    public static function initSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Kullanıcı çıkış yap (session temizle)
     */
    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_destroy();
    }

    /**
     * Session set et
     */
    public static function setSession(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Session al
     */
    public static function getSession(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION[$key] ?? $default;
    }
}
