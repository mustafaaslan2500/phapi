<?php

namespace App\Controllers;

use App\Services\AddressService;

/**
 * Address Controller
 * Basit adres yönetimi
 */
class AddressControllerNew extends BaseController
{
    private AddressService $addressService;

    public function __construct()
    {
        parent::__construct();
        $this->loadLang('user_page_lang');
        $this->addressService = new AddressService($this->lang);
    }

    /**
     * Kullanıcı adreslerini listele
     */
    public function getAddresses(): array
    {
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $result = $this->addressService->getUserAddresses($this->userId);
        return $this->success($result['message'], $result);
    }

    /**
     * Yeni adres ekle
     */
    public function createAddress(): array
    {
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $params = $this->getParams();
        $result = $this->addressService->createAddress($this->userId, $params);

        return $result['status']
            ? $this->success($result['message'], $result)
            : $this->error($result['message']);
    }

    /**
     * Adres güncelle
     */
    public function updateAddress(): array
    {
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $addressId = $this->getParam('address_id');
        if (!$addressId) {
            return $this->error('Address ID gerekli');
        }

        $params = $this->getParams();
        $result = $this->addressService->updateAddress($addressId, $this->userId, $params);

        return $result['status']
            ? $this->success($result['message'])
            : $this->error($result['message']);
    }

    /**
     * Adres sil
     */
    public function deleteAddress(): array
    {
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $addressId = $this->getParam('address_id');
        if (!$addressId) {
            return $this->error('Address ID gerekli');
        }

        $result = $this->addressService->deleteAddress($addressId, $this->userId);

        return $result['status']
            ? $this->success($result['message'])
            : $this->error($result['message']);
    }
}
