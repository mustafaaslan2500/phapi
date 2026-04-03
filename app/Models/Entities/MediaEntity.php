<?php

namespace App\Models\Entities;

/**
 * Media Entity
 * Dosya (resim/döküman) varlığı
 */
class MediaEntity
{
    public int $id;
    public string $uniqueId;
    public int $userId;
    public string $fileName;
    public string $filePath;
    public string $mediaType; // image, document
    public int $fileSize;
    public string $mimeType;
    public string $createdAt;

    public function __construct(object $data)
    {
        $this->id = (int) $data->m_id;
        $this->uniqueId = $data->m_unique_id;
        $this->userId = (int) $data->m_user_id;
        $this->fileName = $data->m_file_name;
        $this->filePath = $data->m_file_path;
        $this->mediaType = $data->m_media_type;
        $this->fileSize = (int) $data->m_file_size;
        $this->mimeType = $data->m_mime_type;
        $this->createdAt = $data->m_created_at;
    }

    /**
     * Array'e çevir (API response için)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->uniqueId,
            'user_id' => $this->userId,
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
            'file_url' => $this->getFileUrl(),
            'media_type' => $this->mediaType,
            'file_size' => $this->fileSize,
            'file_size_human' => $this->getHumanFileSize(),
            'mime_type' => $this->mimeType,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Dosya URL'i oluştur
     */
    public function getFileUrl(): string
    {
        return rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/') . '/' . $this->filePath;
    }

    /**
     * Dosya boyutunu okunabilir formata çevir
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
