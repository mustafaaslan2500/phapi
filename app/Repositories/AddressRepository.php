<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Address Repository
 * Adres verilerine erişim katmanı
 */
class AddressRepository extends BaseRepository
{
    protected string $table = 'addresses';

    /**
     * Ülke kodu ve şehre göre detaylı konum bilgisi getir
     */
    public function getDetailLocation(string $countryCode, string $city): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM location_details 
                WHERE country_code = :country_code 
                AND city = :city 
                LIMIT 1
            ");
            $stmt->bindParam(':country_code', $countryCode, PDO::PARAM_STR);
            $stmt->bindParam(':city', $city, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? [$result] : [];
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return [];
        }
    }

    /**
     * Kullanıcıya ait adresleri getir
     */
    public function getUserAddresses(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM addresses 
                WHERE user_id = :user_id 
                ORDER BY is_default DESC, created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return [];
        }
    }

    /**
     * ID ile adres getir
     */
    public function getAddressById(int $addressId): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM addresses 
                WHERE id = :id 
                LIMIT 1
            ");
            $stmt->bindParam(':id', $addressId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcının varsayılan adresini getir
     */
    public function getDefaultAddress(int $userId): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM addresses 
                WHERE user_id = :user_id 
                AND is_default = 1 
                LIMIT 1
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Yeni adres ekle
     */
    public function createAddress(array $addressData): int|false
    {
        try {
            // Eğer bu adres varsayılan olarak işaretlenmişse, diğerlerini kaldır
            if (!empty($addressData['is_default']) && !empty($addressData['user_id'])) {
                $this->removeDefaultStatus($addressData['user_id']);
            }

            return $this->create($addressData);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Adres güncelle
     */
    public function updateAddress(int $addressId, array $data): bool
    {
        try {
            // Eğer bu adres varsayılan olarak işaretlenmişse, diğerlerini kaldır
            if (!empty($data['is_default'])) {
                $address = $this->getAddressById($addressId);
                if ($address) {
                    $this->removeDefaultStatus($address->user_id);
                }
            }

            $setParts = [];
            foreach (array_keys($data) as $key) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE addresses SET {$setClause} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':id', $addressId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Adres sil
     */
    public function deleteAddress(int $addressId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM addresses WHERE id = :id");
            $stmt->bindParam(':id', $addressId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcının diğer adreslerinin varsayılan durumunu kaldır
     */
    private function removeDefaultStatus(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE addresses 
                SET is_default = 0 
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }
}
