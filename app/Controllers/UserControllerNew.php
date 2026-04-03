<?php

namespace App\Controllers;

use App\Services\UserService;

/**
 * User Controller (Refactored)
 * Sadece HTTP request/response handling
 * Tüm business logic UserService'de
 */
class UserControllerNew extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->loadLang('user_page_lang');
        $this->userService = new UserService($this->lang);
    }

    /**
     * Kullanıcı bilgilerini getir
     * GET /user/info
     */
    public function getMyInfo(): array
    {
        // Auth kontrolü
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $result = $this->userService->getUserInfo($this->userId);
        
        return $result['status'] 
            ? $this->success($result['message'], $result)
            : $this->error($result['message']);
    }

    /**
     * Kullanıcı girişi
     * POST /user/login
     */
    public function login(): array
    {
        $params = $this->getParams();
        $result = $this->userService->login($params);

        return $result['status']
            ? $this->success($result['message'], $result)
            : $this->error($result['message']);
    }

    /**
     * Google ile giriş
     * POST /user/google-login
     */
    public function googleLogin(): array
    {
        $accessToken = $this->getParam('access_token');

        if (!$accessToken) {
            return $this->error('Access token gerekli');
        }

        $result = $this->userService->googleLogin($accessToken);

        return $result['status']
            ? $this->success($result['message'], $result)
            : $this->error($result['message']);
    }

    /**
     * Kullanıcı kaydı
     * POST /user/register
     */
    public function register(): array
    {
        $params = $this->getParams();
        $result = $this->userService->register($params);

        return $result;

        return $result['status']
            ? $this->success($result['message'], $result)
            : $this->error($result['message'], $result);
    }

    /**
     * Kullanıcı çıkışı (Stateless)
     * POST /user/logout
     */
    public function logout(): array
    {
        // JWT stateless - client token'ı siler
        $result = $this->userService->logout();
        
        return $this->success($result['message']);
    }

    /**
     * Refresh token ile yeni access token al
     * POST /user/refresh-token
     * 
     * Token gönderme seçenekleri:
     * 1. Header: Authorization: Bearer <refresh_token>
     * 2. Body: {"refresh_token": "..."}
     */
    public function refreshToken(): array
    {
        // Önce header'dan Bearer token'ı kontrol et (BaseController'da alındı)
        $refreshToken = $this->bearerToken;
        
        // Header'da yoksa body'den al
        if (!$refreshToken) {
            $refreshToken = $this->getParam('refresh_token');
        }

        if (!$refreshToken) {
            return $this->error('Refresh token gerekli (Header: Authorization Bearer veya Body: refresh_token)');
        }

        $result = $this->userService->refreshToken($refreshToken);

        return $result['status']
            ? $this->success($result['message'], $result)
            : $this->error($result['message']);
    }

    /**
     * JWT giriş kontrolü (token validasyonu)
     * GET /user/is_login
     */
    public function loginControl(): array
    {
        $isLoggedIn = ($this->userId !== null);

        return $this->success('', [
            'is_login' => $isLoggedIn,
            'user_id' => $this->userId
        ]);
    }

    /**
     * Profil güncelle
     * POST /user/update-profile
     */
    public function updateProfile(): array
    {
        // Auth kontrolü
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $params = $this->getParams();
        $result = $this->userService->updateProfile($this->userId, $params);

        return $result['status']
            ? $this->success($result['message'])
            : $this->error($result['message']);
    }

    /**
     * Profil fotoğrafı güncelle
     * POST /user/update-photo
     */
    public function updateProfilePhoto(): array
    {
        // Auth kontrolü
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        // Dosya yükleme işlemi
        if (!isset($_FILES['photo'])) {
            return $this->error('Fotoğraf seçilmedi');
        }

        // Dosya upload logic (basitleştirilmiş)
        $uploadDir = 'uploads/profiles/';
        $fileName = uniqid() . '_' . $_FILES['photo']['name'];
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
            $result = $this->userService->updateProfilePhoto($this->userId, $filePath);
            
            return $result['status']
                ? $this->success($result['message'])
                : $this->error($result['message']);
        }

        return $this->error('Dosya yüklenemedi');
    }

    /**
     * Şifre güncelle
     * POST /user/update-password
     */
    public function updatePassword(): array
    {
        // Auth kontrolü
        if ($authError = $this->requireAuth()) {
            return $authError;
        }

        $oldPassword = $this->getParam('old_password');
        $newPassword = $this->getParam('new_password');

        if (!$oldPassword || !$newPassword) {
            return $this->error('Eski ve yeni şifre gerekli');
        }

        $result = $this->userService->updatePassword($this->userId, $oldPassword, $newPassword);

        return $result['status']
            ? $this->success($result['message'])
            : $this->error($result['message']);
    }
}
