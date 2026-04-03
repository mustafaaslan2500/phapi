<?php

namespace App\Services;

use App\Repositories\AddressRepository;

/**
 * Address Service
 * Basit adres işlemleri
 */
class AddressService extends BaseService
{
    private AddressRepository $addressRepository;
    private array $lang;

    public function __construct(array $lang = [])
    {
        $this->addressRepository = new AddressRepository();
        $this->lang = $lang;
    }

    /**
     * Kullanıcının adreslerini getir
     */
    public function getUserAddresses(int $userId): array
    {
        $addresses = $this->addressRepository->getUserAddresses($userId);
        return $this->success('', ['addresses' => $addresses]);
    }

    /**
     * Yeni adres ekle
     */
    public function createAddress(int $userId, array $data): array
    {
        $addressData = [
            'user_id' => $userId,
            'title' => $data['title'] ?? 'Adresim',
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'address_line' => $data['address_line'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $addressId = $this->addressRepository->createAddress($addressData);

        if (!$addressId) {
            return $this->error('Failed to create address');
        }

        return $this->success('Address created successfully', ['address_id' => $addressId]);
    }

    /**
     * Adres güncelle
     */
    public function updateAddress(int $addressId, int $userId, array $data): array
    {
        $address = $this->addressRepository->getAddressById($addressId);

        if (!$address || $address->user_id != $userId) {
            return $this->error('Address not found or unauthorized');
        }

        $updateData = array_filter([
            'title' => $data['title'] ?? null,
            'full_name' => $data['full_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'address_line' => $data['address_line'] ?? null,
        ], fn($value) => $value !== null);

        if (empty($updateData)) {
            return $this->error('No valid fields to update');
        }

        $result = $this->addressRepository->updateAddress($addressId, $updateData);

        return $result 
            ? $this->success('Address updated successfully') 
            : $this->error('Failed to update address');
    }

    /**
     * Adres sil
     */
    public function deleteAddress(int $addressId, int $userId): array
    {
        $address = $this->addressRepository->getAddressById($addressId);

        if (!$address || $address->user_id != $userId) {
            return $this->error('Address not found or unauthorized');
        }

        $result = $this->addressRepository->deleteAddress($addressId);

        return $result 
            ? $this->success('Address deleted successfully') 
            : $this->error('Failed to delete address');
    }
}
