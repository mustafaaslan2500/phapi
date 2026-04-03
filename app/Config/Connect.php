<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Connect
{
    private static $initialized = false; // Sadece bir kez çalıştırılacak kontrol

    public static function initialize()
    {
        if (self::$initialized) {
            return; // Eğer zaten başlatılmışsa, tekrar çalıştırma
        }

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'database'  => $_ENV['DB_DATABASE'] ?? 'default_database',
            'username'  => $_ENV['DB_USERNAME'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$initialized = true; // Başlatıldığını işaretle
    }
}
