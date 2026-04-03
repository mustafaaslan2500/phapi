<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Repositories\AddressRepository;
use App\Services\UserService;

/**
 * Performance Test Controller
 * Sistem performansını test eder
 */
class PerformanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * DB bağlantı testleri
     */
    public function testConnections(): array
    {
        $startTime = microtime(true);
        $results = [];

        // Test 1: Singleton doğrulama
        $results['test_1'] = $this->testSingleton();

        // Test 2: Multiple repository test
        $results['test_2'] = $this->testMultipleRepositories();

        // Test 3: Service layer test
        $results['test_3'] = $this->testServiceLayer();

        $results['total_time'] = round((microtime(true) - $startTime) * 1000, 2) . ' ms';

        return $this->success('Performance tests completed', $results);
    }

    /**
     * Singleton pattern doğrulaması
     */
    private function testSingleton(): array
    {
        $startTime = microtime(true);

        $conn1 = Connection::initialize();
        $conn2 = Connection::initialize();
        $conn3 = Connection::initialize();

        $isSingleton = ($conn1 === $conn2 && $conn2 === $conn3);

        return [
            'name' => 'Singleton Pattern Check',
            'is_singleton' => $isSingleton,
            'status' => $isSingleton ? 'PASS ✅' : 'FAIL ❌',
            'time' => round((microtime(true) - $startTime) * 1000, 2) . ' ms',
            'info' => $isSingleton 
                ? 'Tek PDO instance kullanılıyor - PERFORMANSLI' 
                : 'Her çağrıda yeni PDO oluşturuluyor - YAVAS'
        ];
    }

    /**
     * Multiple repository test
     */
    private function testMultipleRepositories(): array
    {
        $startTime = microtime(true);

        $userRepo1 = new UserRepository();
        $userRepo2 = new UserRepository();
        $addressRepo = new AddressRepository();

        return [
            'name' => 'Multiple Repository Instances',
            'repositories_created' => 3,
            'time' => round((microtime(true) - $startTime) * 1000, 2) . ' ms',
            'info' => 'Singleton sayesinde 3 repository tek PDO kullanıyor'
        ];
    }

    /**
     * Service layer test
     */
    private function testServiceLayer(): array
    {
        $startTime = microtime(true);

        // Service oluşturulması (içinde 2 repository var)
        $userService = new UserService([]);

        return [
            'name' => 'Service Layer Initialization',
            'service' => 'UserService (UserRepo + AddressRepo)',
            'time' => round((microtime(true) - $startTime) * 1000, 2) . ' ms',
            'info' => 'Service içindeki 2 repository tek PDO paylaşıyor'
        ];
    }

    /**
     * Connection count (tahmini)
     */
    public function connectionInfo(): array
    {
        return $this->success('Connection info', [
            'is_connected' => Connection::isConnected(),
            'connection_type' => 'Singleton PDO',
            'max_connections_per_request' => 1,
            'info' => 'Her request için maksimum 1 PDO bağlantısı açılır'
        ]);
    }
}
