<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * User Repository
 * Kullanıcı verilerine erişim katmanı
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    /**
     * ID ile kullanıcı getir
     */
    public function getUserById(int $userId): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*
                FROM users AS u
                WHERE u.u_id = :u_id
                LIMIT 1
            ");
            $stmt->bindParam(':u_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }



    /**
     * Email ile kullanıcı getir
     */
    public function getUserByEmail(string $email): object|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*
                FROM users AS u
                WHERE u.u_email = :email
                LIMIT 1
            ");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Email kontrolü
     */
    public function emailExists(string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u_id
                FROM users
                WHERE u_email = :email
                LIMIT 1
            ");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Username kontrolü
     */
    public function usernameExists(string $username): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u_id
                FROM users
                WHERE u_username = :username
                LIMIT 1
            ");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Username'e göre user ID getir
     */
    public function getUserIdByUsername(string $username): int|false
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u_id
                FROM users
                WHERE u_username = :username
                LIMIT 1
            ");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ? (int) $result->u_id : false;
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcının profil gizliliğini kontrol et
     */
    public function isProfilePrivate(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u_private_profile
                FROM users
                WHERE u_id = :user_id
                LIMIT 1
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ? (bool) $result->u_private_profile : false;
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Yeni kullanıcı kaydet (JWT - No DB Token)
     */
    public function createUser(array $userData): int|false
    {
        try {
            // Kullanıcı ekle (JWT kullanıldığı için DB token yok)
            $userId = $this->create($userData);
            
            if (!$userId) {
                return false;
            }

            return $userId;
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcı güncelle
     */
    public function updateUser(int $userId, array $data): bool
    {
        try {
            $setParts = [];
            foreach (array_keys($data) as $key) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE users SET {$setClause} WHERE u_id = :u_id";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':u_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Login log kaydet
     */
    public function saveLoginLog(int $userId, ?string $locationInfo, array $detailLocation): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_login_logs 
                (ull_user_id, ull_location_info, ull_country, ull_city, ull_created_at) 
                VALUES (:user_id, :location_info, :country, :city, NOW())
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':location_info', $locationInfo, PDO::PARAM_STR);
            $stmt->bindValue(':country', $detailLocation['country'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':city', $detailLocation['city'] ?? null, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kullanıcı telefon numarasını güncelle
     */
    public function updatePhoneNumber(int $userId, string $phoneNumber): bool
    {
        return $this->updateUser($userId, ['u_phone_number' => $phoneNumber]);
    }

    /**
     * Kullanıcı profil fotoğrafını güncelle
     */
    public function updateProfilePhoto(int $userId, string $photoPath): bool
    {
        return $this->updateUser($userId, ['u_profile_photo' => $photoPath]);
    }

    /**
     * Kullanıcı şifresini güncelle
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->updateUser($userId, ['u_password' => $hashedPassword]);
    }
}
