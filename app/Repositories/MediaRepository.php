<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Media Repository
 * Dosya veritabanı işlemleri
 */
class MediaRepository extends BaseRepository
{
    protected string $table = 'media';

    /**
     * Unique ID ile media getir
     */
    public function getByUniqueId(string $uniqueId): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM media 
                WHERE m_unique_id = :unique_id 
                LIMIT 1
            ");
            $stmt->bindParam(':unique_id', $uniqueId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * ID ile media getir
     */
    public function getById(int $id): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM media 
                WHERE m_id = :id 
                LIMIT 1
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcının dosyalarını getir
     */
    public function getUserMedia(int $userId, ?string $mediaType = null, int $limit = 50, int $offset = 0): array|false
    {
        try {
            $sql = "SELECT * FROM media WHERE m_user_id = :user_id";
            
            if ($mediaType) {
                $sql .= " AND m_media_type = :media_type";
            }
            
            $sql .= " ORDER BY m_created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($mediaType) {
                $stmt->bindParam(':media_type', $mediaType, PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Yeni media kaydet
     */
    public function createMedia(array $data): int|false
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO media (
                    m_unique_id, m_user_id, m_file_name, m_file_path, 
                    m_media_type, m_file_size, m_mime_type
                ) VALUES (
                    :unique_id, :user_id, :file_name, :file_path,
                    :media_type, :file_size, :mime_type
                )
            ");
            
            $stmt->bindParam(':unique_id', $data['unique_id'], PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':file_name', $data['file_name'], PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $data['file_path'], PDO::PARAM_STR);
            $stmt->bindParam(':media_type', $data['media_type'], PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $data['file_size'], PDO::PARAM_INT);
            $stmt->bindParam(':mime_type', $data['mime_type'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Media sil
     */
    public function deleteMedia(int $id, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM media 
                WHERE m_id = :id AND m_user_id = :user_id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcının toplam dosya sayısı
     */
    public function getUserMediaCount(int $userId, ?string $mediaType = null): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM media WHERE m_user_id = :user_id";
            
            if ($mediaType) {
                $sql .= " AND m_media_type = :media_type";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($mediaType) {
                $stmt->bindParam(':media_type', $mediaType, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            return $result ? (int) $result->count : 0;
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return 0;
        }
    }
}
