<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * Singleton pattern ile tek PDO instance'ı paylaşır
 * PERFORMANS: Her request için sadece 1 PDO bağlantısı
 */
class Connection
{
    private static ?PDO $pdo = null;
    private static ?string $currentDb = null;

    /**
     * Singleton PDO bağlantısını döndür
     * Aynı request içinde birden fazla çağrıldığında aynı instance'ı döner
     */
    public static function initialize($dbName = null): PDO
    {
        $dbName = $dbName ?? $_ENV['DB_DATABASE'];

        // Eğer bağlantı zaten var ve aynı DB için ise, mevcut instance'ı döndür
        if (self::$pdo !== null && self::$currentDb === $dbName) {
            return self::$pdo;
        }

        // Yeni bağlantı oluştur
        try {
            $host = $_ENV['DB_HOST'];
            $username = $_ENV['DB_USERNAME'];
            $password = $_ENV['DB_PASSWORD'];
            $charset = $_ENV['DB_CHARSET'];

            self::$pdo = new PDO(
                "mysql:host=$host;dbname=$dbName;charset=$charset",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false, // Connection pooling için true yapılabilir
                ]
            );

            self::$currentDb = $dbName;
            return self::$pdo;

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Bağlantıyı kapat (testing için)
     */
    public static function close(): void
    {
        self::$pdo = null;
        self::$currentDb = null;
    }

    /**
     * Aktif bağlantı var mı?
     */
    public static function isConnected(): bool
    {
        return self::$pdo !== null;
    }
}
