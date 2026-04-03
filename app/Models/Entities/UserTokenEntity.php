<?php

namespace App\Models\Entities;

/**
 * UserToken Entity
 * user_tokens tablosunun yapısını temsil eder
 */
class UserTokenEntity
{
    public ?int $ut_user_id = null;
    public ?string $ut_token = null;

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
}
