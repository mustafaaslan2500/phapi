<?php

namespace App\Services;

use App\Repositories\MediaRepository;
use App\Models\Entities\MediaEntity;

/**
 * Media Service
 * Dosya yükleme ve yönetim servisi
 */
class MediaService
{
    private MediaRepository $mediaRepository;
    
    // Desteklenen dosya türleri
    private const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    
    private const DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        'text/plain',
    ];
    
    // Maksimum dosya boyutları (byte)
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const MAX_DOCUMENT_SIZE = 20 * 1024 * 1024; // 20 MB
    
    public function __construct()
    {
        $this->mediaRepository = new MediaRepository();
    }
    
    /**
     * 32 karakterlik benzersiz ID oluştur
     */
    public function generateUniqueId(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Dosya doğrulama
     */
    public function validateFile(array $file): array
    {
        // Dosya var mı?
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Dosya yüklenemedi'];
        }
        
        // Upload hatası var mı?
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Dosya yükleme hatası: ' . $file['error']];
        }
        
        // MIME type kontrolü
        $mimeType = mime_content_type($file['tmp_name']);
        $mediaType = $this->getMediaType($mimeType);
        
        if (!$mediaType) {
            return ['valid' => false, 'error' => 'Desteklenmeyen dosya türü'];
        }
        
        // Boyut kontrolü
        $maxSize = $mediaType === 'image' ? self::MAX_IMAGE_SIZE : self::MAX_DOCUMENT_SIZE;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            return ['valid' => false, 'error' => "Dosya boyutu maksimum {$maxSizeMB} MB olabilir"];
        }
        
        return [
            'valid' => true,
            'media_type' => $mediaType,
            'mime_type' => $mimeType,
            'size' => $file['size'],
        ];
    }
    
    /**
     * MIME type'dan media type belirle
     */
    private function getMediaType(string $mimeType): ?string
    {
        if (in_array($mimeType, self::IMAGE_MIME_TYPES)) {
            return 'image';
        }
        
        if (in_array($mimeType, self::DOCUMENT_MIME_TYPES)) {
            return 'document';
        }
        
        return null;
    }
    
    /**
     * Dosya uzantısı al
     */
    private function getFileExtension(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
        ];
        
        return $extensions[$mimeType] ?? 'bin';
    }
    
    /**
     * Dosyayı kaydet
     */
    public function storeFile(array $file, string $uniqueId, string $mimeType): array|false
    {
        // Klasör yapısı: files/YYYY/MM/
        $year = date('Y');
        $month = date('m');
        $uploadDir = "files/{$year}/{$month}";
        $fullUploadDir = __DIR__ . "/../../{$uploadDir}";
        
        // Klasör yoksa oluştur
        if (!is_dir($fullUploadDir)) {
            if (!mkdir($fullUploadDir, 0755, true)) {
                return false;
            }
        }
        
        // Dosya adı: uniqueId.ext
        $extension = $this->getFileExtension($mimeType);
        $fileName = "{$uniqueId}.{$extension}";
        $filePath = "{$uploadDir}/{$fileName}";
        $fullFilePath = $fullUploadDir . '/' . $fileName;
        
        // Dosyayı taşı
        if (!move_uploaded_file($file['tmp_name'], $fullFilePath)) {
            return false;
        }
        
        // Dosya izinlerini ayarla
        chmod($fullFilePath, 0644);
        
        return [
            'file_path' => $filePath,
            'full_path' => $fullFilePath,
        ];
    }
    
    /**
     * Media oluştur
     */
    public function createMedia(int $userId, array $file): array|false
    {
        // Dosya doğrulama
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        
        // Unique ID oluştur
        $uniqueId = $this->generateUniqueId();
        
        // Dosyayı kaydet
        $storeResult = $this->storeFile($file, $uniqueId, $validation['mime_type']);
        if (!$storeResult) {
            return ['error' => 'Dosya kaydedilemedi'];
        }
        
        // Veritabanına kaydet
        $mediaId = $this->mediaRepository->createMedia([
            'unique_id' => $uniqueId,
            'user_id' => $userId,
            'file_name' => $file['name'],
            'file_path' => $storeResult['file_path'],
            'media_type' => $validation['media_type'],
            'file_size' => $validation['size'],
            'mime_type' => $validation['mime_type'],
        ]);
        
        if (!$mediaId) {
            // Veritabanı hatası - dosyayı sil
            @unlink($storeResult['full_path']);
            return ['error' => 'Veritabanı hatası'];
        }
        
        return [
            'unique_id' => $uniqueId,
            'media_id' => $mediaId,
            'media_type' => $validation['media_type'],
            'file_size' => $validation['size'],
        ];
    }
    
    /**
     * Unique ID ile media getir
     */
    public function getMediaByUniqueId(string $uniqueId): ?MediaEntity
    {
        $media = $this->mediaRepository->getByUniqueId($uniqueId);
        if (!$media) {
            return null;
        }
        
        return new MediaEntity($media);
    }
    
    /**
     * Kullanıcının dosyalarını getir
     */
    public function getUserMedia(int $userId, ?string $mediaType = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $mediaList = $this->mediaRepository->getUserMedia($userId, $mediaType, $perPage, $offset);
        
        if (!$mediaList) {
            return ['items' => [], 'total' => 0];
        }
        
        $items = array_map(function($media) {
            $entity = new MediaEntity($media);
            return $entity->toArray();
        }, $mediaList);
        
        $total = $this->mediaRepository->getUserMediaCount($userId, $mediaType);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Media sil
     */
    public function deleteMedia(string $uniqueId, int $userId): bool
    {
        $media = $this->mediaRepository->getByUniqueId($uniqueId);
        if (!$media || $media->m_user_id !== $userId) {
            return false;
        }
        
        // Veritabanından sil
        if (!$this->mediaRepository->deleteMedia($media->m_id, $userId)) {
            return false;
        }
        
        // Dosyayı sil
        $fullPath = __DIR__ . "/../../{$media->m_file_path}";
        @unlink($fullPath);
        
        return true;
    }
}
