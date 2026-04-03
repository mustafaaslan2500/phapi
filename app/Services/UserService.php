<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AddressRepository;
use App\Services\GeoLocationService;
use App\Services\ImageProxyService;
use App\Services\JWTService;
use App\Helpers\ApiHelpers;
use App\Helpers\Validator;
use App\Factories\GoogleClientFactory;

/**
 * User Service (JWT-Based)
 * Stateless authentication - No sessions, no DB tokens
 */
class UserService extends BaseService
{
    private UserRepository $userRepository;
    private AddressRepository $addressRepository;
    private GeoLocationService $geoLocationService;
    private JWTService $jwtService;
    private ?ImageProxyService $imageProxyService = null; // Lazy loading
    private array $lang;

    public function __construct(array $lang = [])
    {
        $this->userRepository = new UserRepository();
        $this->addressRepository = new AddressRepository();
        $this->geoLocationService = new GeoLocationService();
        $this->jwtService = new JWTService();
        $this->lang = $lang;
    }

    /**
     * ImageProxyService'i lazy olarak al
     */
    private function getImageProxyService(): ImageProxyService
    {
        if ($this->imageProxyService === null) {
            $this->imageProxyService = new ImageProxyService();
        }
        return $this->imageProxyService;
    }

    /**
     * Kullanıcı bilgilerini al
     */
    public function getUserInfo(int $userId): array
    {
        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            return $this->error($this->lang['user_not_found'] ?? 'User not found');
        }

        $userData = $this->formatUserData($user);
        return $this->success('', ['user_data' => $userData]);
    }

    /**
     * Kullanıcı giriş yap (JWT)
     */
    public function login(array $credentials): array
    {
        // Email ve şifre ile giriş
        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            return $this->error($this->lang['email_address_or_password_cannot_be_empty'] ?? 'Email or password is required');
        }

        // Email validation
        if (!filter_var($credentials['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->error($this->lang['invalid_email'] ?? 'Invalid email address');
        }

        $user = $this->userRepository->getUserByEmail($credentials['email']);

        if (!$user) {
            return $this->error($this->lang['the_email_address_or_password_is_incorrect'] ?? 'Invalid credentials');
        }

        if (!password_verify($credentials['password'], $user->u_password)) {
            return $this->error($this->lang['the_email_address_or_password_is_incorrect'] ?? 'Invalid credentials');
        }

        // JWT token pair oluştur (şifre hash'i ile - şifre değişince invalidate)
        $tokens = $this->jwtService->createTokenPair($user->u_id, $user->u_email, $user->u_password);

        $userData = $this->formatUserData($user);

        // Login log kaydet (async - response sonrası)
        $this->saveLoginLog($user->u_id);

        return $this->success($this->lang['login_successful'] ?? 'Login successful', [
            'user_data' => $userData,
            'tokens' => $tokens
        ]);
    }

    /**
     * Refresh token ile yeni access token al
     */
    public function refreshToken(string $refreshToken): array
    {
        $decoded = $this->jwtService->verifyToken($refreshToken);

        if (!$decoded || $decoded->type !== 'refresh') {
            return $this->error($this->lang['invalid_token'] ?? 'Invalid refresh token');
        }

        $user = $this->userRepository->getUserById($decoded->user_id);

        if (!$user) {
            return $this->error($this->lang['user_not_found'] ?? 'User not found');
        }

        // Yeni token pair oluştur (şifre hash'i ile)
        $tokens = $this->jwtService->createTokenPair($user->u_id, $user->u_email, $user->u_password);

        return $this->success('Token refreshed', ['tokens' => $tokens]);
    }

    /**
     * Google ile giriş (JWT)
     */
    public function googleLogin(string $accessToken): array
    {
        try {
            $googleEmail = $this->getGoogleUserEmail($accessToken);

            if (!$googleEmail) {
                return $this->error('Google hesabından email alınamadı');
            }

            $user = $this->userRepository->getUserByEmail($googleEmail);

            if (!$user) {
                return $this->error('Google hesabı ile kayıtlı kullanıcı bulunamadı.');
            }

            // JWT token pair oluştur
            $tokens = $this->jwtService->createTokenPair($user->u_id, $user->u_email, $user->u_password);

            $userData = $this->formatUserData($user);

            // Login log kaydet (async - response sonrası)
            $this->saveLoginLog($user->u_id);

            return $this->success('Giriş başarılı', [
                'user_data' => $userData,
                'tokens' => $tokens
            ]);
        } catch (\Exception $e) {
            return $this->error('Google giriş hatası: ' . $e->getMessage());
        }
    }

    /**
     * Google API'den email al (helper metod)
     */
    private function getGoogleUserEmail(string $accessToken): ?string
    {
        $client = GoogleClientFactory::create();
        $client->setAccessToken(['access_token' => $accessToken]);

        $oauth2 = new \Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        return $userInfo->email ?? null;
    }

    /**
     * Kullanıcı kayıt
     */
    public function register(array $data): array
    {

        // Validation
        $validationResult = $this->validateRegistration($data);
        if (!$validationResult['status']) {
            return $validationResult;
        }

        // Username temizle
        $data['username'] = $this->sanitizeUsername($data['username']);

        // Email ve username kontrolü
        if ($this->userRepository->emailExists($data['user_email'])) {
            return $this->error($this->lang['email_already_used'] ?? 'Bu e-posta adresi kullanılmaktadır.');
        }

        if ($this->userRepository->usernameExists($data['username'])) {
            return $this->error($this->lang['username_already_used'] ?? 'Bu kullanıcı adı kullanılmaktadır.');
        }

        // Şifreyi hashle
        $hashedPassword = password_hash($data['user_password'], PASSWORD_DEFAULT);

        // Kullanıcı verilerini hazırla
        $userData = [
            'u_first_name' => $data['user_first_name'],
            'u_last_name' => $data['user_last_name'],
            'u_email' => $data['user_email'],
            'u_username' => $data['username'],
            'u_phone_number' => $data['user_phone_number'] ?? null,
            'u_password' => $hashedPassword,
            'u_register_date' => date('Y-m-d H:i:s'),
            'u_private_profile' => 0,
            'u_admin' => 0
        ];

        // Kullanıcıyı kaydet
        $userId = $this->userRepository->createUser($userData);

        if (!$userId) {
            return $this->error($this->lang['registration_failed'] ?? 'Registration failed');
        }

        // Otomatik giriş yap - JWT token pair oluştur
        $user = $this->userRepository->getUserById($userId);
        $tokens = $this->jwtService->createTokenPair($user->u_id, $user->u_email, $user->u_password);

        $userData = $this->formatUserData($user);

        return $this->success($this->lang['registration_successful'] ?? 'Registration successful', [
            'user_data' => $userData,
            'tokens' => $tokens
        ]);
    }

    /**
     * Kayıt validation
     */
    private function validateRegistration(array $data): array
    {
        $validator = new Validator(
            $data,
            [
                'user_first_name' => 'required|regex:/^[\pL\s]+$/u|max:200',
                'user_last_name' => 'required|alpha|max:200',
                'user_email' => 'required|email|max:250',
                'username' => 'required|max:50',
                'user_password' => 'required|min:6|max:100',
                'user_phone_number' => 'digits:10|starts_with:5',
            ],
            [],
            []
        );

        if ($validator->fails()) {
            return $this->error(implode(', ', $validator->errors()));
        }

        return $this->success();
    }

    /**
     * Username temizle
     */
    private function sanitizeUsername(string $username): string
    {
        $username = str_replace(' ', '', $username);

        $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'];
        $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'];
        $username = str_replace($turkish, $english, $username);

        $username = strtolower($username);
        $username = preg_replace('/[^a-z0-9_.]/', '', $username);

        return $username;
    }

    /**
     * Kullanıcı çıkış (Stateless - Client-side token silme)
     */
    public function logout(): array
    {
        // JWT stateless - client token'ı silecek
        // Token 15 dakika sonra otomatik expire olur
        return $this->success($this->lang['logout_successful'] ?? 'Logout successful');
    }

    /**
     * Kullanıcı bilgilerini güncelle
     */
    public function updateProfile(int $userId, array $data): array
    {
        $allowedFields = ['u_first_name', 'u_last_name', 'u_bio', 'u_phone_number', 'u_private_profile'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return $this->error('No valid fields to update');
        }

        $result = $this->userRepository->updateUser($userId, $updateData);

        if (!$result) {
            return $this->error('Update failed');
        }

        return $this->success('Profile updated successfully');
    }

    /**
     * Profil fotoğrafı güncelle
     */
    public function updateProfilePhoto(int $userId, string $photoPath): array
    {
        $result = $this->userRepository->updateProfilePhoto($userId, $photoPath);

        if (!$result) {
            return $this->error('Failed to update profile photo');
        }

        return $this->success('Profile photo updated successfully');
    }

    /**
     * Şifre güncelle
     */
    public function updatePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            return $this->error('User not found');
        }

        if (!password_verify($oldPassword, $user->u_password)) {
            return $this->error('Current password is incorrect');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->userRepository->updatePassword($userId, $hashedPassword);

        if (!$result) {
            return $this->error('Failed to update password');
        }

        return $this->success('Password updated successfully');
    }

    /**
     * Login log kaydet (gerçek işlem)
     */
    private function saveLoginLog(int $userId): void
    {
        try {
            $locationResult = $this->geoLocationService->getLocationInfo();

            if ($locationResult['status']) {
                $detailLocation = $this->addressRepository->getDetailLocation(
                    $locationResult['country_code'],
                    $locationResult['city']
                );

                $locationJson = json_encode([
                    'country_code' => $locationResult['country_code'],
                    'country_name' => $locationResult['country_name'],
                    'city' => $locationResult['city'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                $this->userRepository->saveLoginLog($userId, $locationJson, $detailLocation[0] ?? []);
            }
        } catch (\Exception $e) {
            // Log hatası response'u etkilemesin
            error_log('Login log error: ' . $e->getMessage());
        }
    }

    /**
     * Kullanıcı verilerini formatla
     */
    private function formatUserData(object $user): array
    {
        $profilePhoto = null;
        if ($user->u_profile_photo) {
            $imageService = $this->getImageProxyService();
            $normalizedPath = ApiHelpers::normalizeMediaPath($user->u_profile_photo);
            $profilePhoto = $imageService->resize(
                $normalizedPath,
                80,
                80,
                'fill',
                85,
                3600,
                'webp'
            );
        }

        return [
            'id' => (int) $user->u_id,
            'name' => $user->u_first_name,
            'surname' => $user->u_last_name,
            'username' => $user->u_username,
            'email' => $user->u_email,
            'phone' => $user->u_phone_number,
            'bio' => $user->u_bio ?? null,
            'profile_photo' => $profilePhoto,
            'private_account' => (bool) $user->u_private_profile,
            'is_admin' => (bool) $user->u_admin,
        ];
    }
}
