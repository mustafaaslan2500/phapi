<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Exception;

/**
 * GeoLocation Service
 * IP bazlı konum tespit işlemleri
 */
class GeoLocationService extends BaseService
{
    private string $databasePath;

    public function __construct()
    {
        $this->databasePath = $_ENV['GEO_LIB_PATH'] ?? '';
    }

    /**
     * IP adresinden konum bilgisi al
     */
    public function getLocationInfo(?string $ipAddress = null): array
    {
        $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'];

        if (empty($this->databasePath) || !file_exists($this->databasePath)) {
            return $this->error('GeoIP database not configured');
        }

        try {
            $reader = new Reader($this->databasePath);
            $record = $reader->city($ipAddress);

            return $this->success('Location info retrieved', [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
                'city' => $record->city->name,
                'postal_code' => $record->postal->code,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timezone' => $record->location->timeZone,
            ]);
        } catch (Exception $e) {
            return $this->error('Failed to get location info: ' . $e->getMessage());
        }
    }

    /**
     * Sadece ülke bilgisi al (daha hızlı)
     */
    public function getCountryInfo(?string $ipAddress = null): array
    {
        $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'];

        if (empty($this->databasePath) || !file_exists($this->databasePath)) {
            return $this->error('GeoIP database not configured');
        }

        try {
            $reader = new Reader($this->databasePath);
            $record = $reader->country($ipAddress);

            return $this->success('Country info retrieved', [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
            ]);
        } catch (Exception $e) {
            return $this->error('Failed to get country info: ' . $e->getMessage());
        }
    }
}
