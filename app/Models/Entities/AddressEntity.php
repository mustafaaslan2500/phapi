<?php

namespace App\Models\Entities;

/**
 * Address Entity
 * addresses tablosunun yapısını temsil eder
 */
class AddressEntity
{
    public ?int $id = null;
    public ?int $user_id = null;
    public ?string $title = null;
    public ?string $full_name = null;
    public ?string $phone = null;
    public ?string $city = null;
    public ?string $address_line = null;
    public ?string $created_at = null;

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
