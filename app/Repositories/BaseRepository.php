<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Base Repository
 * Tüm repository sınıflarının temel sınıfı
 * PERFORMANS: Singleton PDO kullanır, her repository yeni bağlantı açmaz
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table;

    public function __construct()
    {
        // Singleton pattern: Aynı PDO instance'ını kullan
        $this->pdo = Connection::initialize();
    }

    /**
     * ID ile tek bir kayıt getir
     */
    public function findById(int $id): object|false
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Tüm kayıtları getir
     */
    public function findAll(int $limit = 100, int $offset = 0): array|false
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
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
     * Belirli bir kolona göre kayıt getir
     */
    public function findBy(string $column, mixed $value): object|false
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1");
            $stmt->bindParam(':value', $value);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Yeni kayıt ekle
     */
    public function create(array $data): int|false
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            $stmt->execute();
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kayıt güncelle
     */
    public function update(int $id, array $data): bool
    {
        try {
            $setParts = [];
            foreach (array_keys($data) as $key) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Kayıt sil
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Transaction başlat
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Transaction commit
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Transaction rollback
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Hata loglama (ileride dosya veya veritabanına yazılabilir)
     */
    protected function logError(string $method, PDOException $e): void
    {
        error_log("Repository Error [{$method}]: " . $e->getMessage());
    }
}
