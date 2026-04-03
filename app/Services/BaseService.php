<?php

namespace App\Services;

/**
 * Base Service
 * Tüm servis sınıflarının temel sınıfı
 */
abstract class BaseService
{
    /**
     * Başarılı response formatı
     */
    protected function success(string $message = '', array $data = []): array
    {
        return [
            'status' => true,
            'message' => $message,
            ...$data
        ];
    }

    /**
     * Hatalı response formatı
     */
    protected function error(string $message, array $data = []): array
    {
        return [
            'status' => false,
            'message' => $message,
            ...$data
        ];
    }

    /**
     * Validation hatası formatı
     */
    protected function validationError(array $errors): array
    {
        return [
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ];
    }
}
