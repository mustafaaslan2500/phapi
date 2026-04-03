<?php

namespace App\Controllers;

use App\Helpers\ApiHelpers;
use App\Services\MediaService;
use App\Middleware\AuthJWT;

/**
 * Media Controller
 * Dosya yükleme ve yönetim
 */
class MediaController extends BaseController
{
    private MediaService $mediaService;

    public function __construct()
    {
        parent::__construct();
        $this->mediaService = new MediaService();
    }

    /**
     * Dosya yükle
     * POST /media/upload
     */
    public function upload(): array
    {
        // JWT authentication
        $userId = AuthJWT::authenticate($this->bearerToken);
        if (!$userId) {
            return AuthJWT::requireAuth();
        }

        // Dosya var mı?
        if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
            return ApiHelpers::show_message(false, 'Dosya bulunamadı');
        }

        $file = $_FILES['file'];

        // Dosya yükle
        $result = $this->mediaService->createMedia($userId, $file);

        if (!$result || isset($result['error'])) {
            return ApiHelpers::show_message(
                false,
                $result['error'] ?? 'Dosya yüklenemedi'
            );
        }

        return ApiHelpers::show_message(
            true,
            'Dosya başarıyla yüklendi',
            [
                'unique_id' => $result['unique_id'],
                'media_type' => $result['media_type'],
                'file_size' => $result['file_size'],
            ]
        );
    }

    /**
     * Media bilgisi getir
     * GET /media/{unique_id}
     */
    public function getMedia(): array
    {
        $userId = AuthJWT::authenticate($this->bearerToken);
        if (!$userId) {
            return AuthJWT::requireAuth();
        }

        $uniqueId = $this->request->get('unique_id');

        if (!$uniqueId) {
            return ApiHelpers::show_message(false, 'unique_id gerekli');
        }

        $media = $this->mediaService->getMediaByUniqueId($uniqueId);

        if (!$media) {
            return ApiHelpers::show_message(false, 'Dosya bulunamadı');
        }

        return ApiHelpers::show_message(
            true,
            'Başarılı',
            $media->toArray()
        );
    }

    /**
     * Kullanıcının dosyalarını listele
     * GET /media/list
     */
    public function listMedia(): array
    {
        // JWT authentication
        $userId = AuthJWT::authenticate($this->bearerToken);
        if (!$userId) {
            return AuthJWT::requireAuth();
        }

        $mediaType = $this->request->get('media_type'); // image | document
        $page = (int) $this->request->get('page', 1);
        $perPage = (int) $this->request->get('per_page', 20);

        // Limit per_page
        $perPage = min($perPage, 100);

        $result = $this->mediaService->getUserMedia($userId, $mediaType, $page, $perPage);

        return ApiHelpers::show_message(
            true,
            'Başarılı',
            $result
        );
    }

    /**
     * Dosya sil
     * DELETE /media/{unique_id}
     */
    public function deleteMedia(): array
    {
        // JWT authentication
        $userId = AuthJWT::authenticate($this->bearerToken);
        if (!$userId) {
            return AuthJWT::requireAuth();
        }

        $uniqueId = $this->request->get('unique_id');

        if (!$uniqueId) {
            return ApiHelpers::show_message(false, 'unique_id gerekli');
        }

        $deleted = $this->mediaService->deleteMedia($uniqueId, $userId);

        if (!$deleted) {
            return ApiHelpers::show_message(false, 'Dosya silinemedi veya bulunamadı');
        }

        return ApiHelpers::show_message(true, 'Dosya başarıyla silindi');
    }
}
