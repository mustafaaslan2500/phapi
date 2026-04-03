<?php

namespace App\Models\Entities;

/**
 * User Entity
 * users tablosunun yapısını temsil eder
 */
class UserEntity
{
    public ?int $u_id = null;
    public ?string $u_first_name = null;
    public ?string $u_last_name = null;
    public ?string $u_username = null;
    public ?string $u_email = null;
    public ?string $u_password = null;
    public ?string $u_phone = null;
    public ?int $u_email_verified = 0;
    public ?int $u_phone_verified = 0;
    public ?string $u_register_date = null;
    public ?string $u_last_login = null;
    public ?int $u_status = 1; // 0: inactive, 1: active

    /**
     * Array'den entity oluştur
     */
    public static function fromArray(array $data): self
    {
        $entity = new self();
        foreach ($data as $key => $value) {
            if (property_exists($entity, $key)) {
                $entity->$key = $value;
            }
        }
        return $entity;
    }

    /**
     * Entity'yi array'e çevir
     */
    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn($value) => $value !== null);
    }

    /**
     * Güvenli kullanıcı bilgisi (şifresiz)
     */
    public function toSafeArray(): array
    {
        $data = $this->toArray();
        unset($data['u_password']);
        return $data;
    }
}
