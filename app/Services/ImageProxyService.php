<?php

namespace App\Services;

use Predis\Client as Redis;

/**
 * Image Proxy Service
 * imgproxy URL imzalama ve resim işleme
 */
class ImageProxyService extends BaseService
{
    private ?Redis $redis = null;
    private bool $redisChecked = false;
    private int $cacheDefaultTTL = 3600;
    private array $config;

    public function __construct()
    {
        $this->config = [
            'base_url' => rtrim($_ENV['IMGPROXY_BASE_URL'] ?? '', '/'),
            'key_hex'  => $_ENV['IMGPROXY_KEY_HEX'] ?? '',
            'salt_hex' => $_ENV['IMGPROXY_SALT_HEX'] ?? '',
            'default_ext' => $_ENV['IMGPROXY_DEFAULT_EXT'] ?? 'webp'
        ];
        
        // Redis'i lazy initialize et - constructor'da bağlanma!
    }

    /**
     * Redis bağlantısını lazy olarak başlat
     */
    private function getRedis(): ?Redis
    {
        if ($this->redisChecked) {
            return $this->redis;
        }

        $this->redisChecked = true;

        try {
            $this->redis = new Redis([
                'scheme' => 'tcp',
                'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port'   => $_ENV['REDIS_PORT'] ?? 6379,
                'timeout' => 0.1, // 100ms timeout
            ]);
            $this->redis->ping();
        } catch (\Exception $e) {
            $this->redis = null;
        }

        return $this->redis;
    }

    /**
     * Base64 URL safe encode
     */
    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * İmzalı URL üret
     */
    public function generateSignedUrl(string $src, array $options = [], ?string $ext = null, ?int $expire = null): ?string
    {
        if (!$this->config['base_url'] || !$this->config['key_hex'] || !$this->config['salt_hex']) {
            return null;
        }

        $key  = @hex2bin($this->config['key_hex']);
        $salt = @hex2bin($this->config['salt_hex']);

        if ($key === false || $salt === false) {
            return null;
        }

        if ($expire !== null) {
            $options[] = 'exp:' . intval($expire);
        }

        $processing = implode('/', $options);
        if ($processing === '') {
            $processing = 'plain';
        }

        $encodedSrc = $this->b64url($src);
        $extension = $ext ?? $this->config['default_ext'];
        $path = "/{$processing}/{$encodedSrc}";
        
        if ($extension) {
            $path .= ".{$extension}";
        }

        $signature = hash_hmac('sha256', $salt . $path, $key, true);
        $encodedSig = $this->b64url($signature);

        return $this->config['base_url'] . '/' . $encodedSig . $path;
    }

    /**
     * Resize işlemi için URL üret
     */
    public function resize(
        string $imageUrl,
        int $width = 800,
        int $height = 600,
        string $resizeType = 'fill',
        int $quality = 85,
        ?int $cacheTTL = null,
        ?string $format = null
    ): ?string {
        $cacheKey = "imgproxy:resize:" . md5($imageUrl . $width . $height . $resizeType . $quality . $format);
        
        // Cache kontrolü
        $redis = $this->getRedis();
        if ($redis) {
            try {
                $cached = $redis->get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            } catch (\Exception $e) {
                // Cache hatası, devam et
            }
        }

        $options = [
            "rs:{$resizeType}:{$width}:{$height}",
            "q:{$quality}"
        ];

        $ttl = $cacheTTL ?? $this->cacheDefaultTTL;
        $expire = time() + $ttl;
        
        $url = $this->generateSignedUrl($imageUrl, $options, $format, $expire);

        // Cache'e kaydet
        if ($url && $redis) {
            try {
                $redis->setex($cacheKey, $ttl, $url);
            } catch (\Exception $e) {
                // Cache hatası, URL'i döndür
            }
        }

        return $url;
    }

    /**
     * Thumbnail üret
     */
    public function thumbnail(string $imageUrl, int $size = 150): ?string
    {
        return $this->resize($imageUrl, $size, $size, 'fill', 80, 7200, 'webp');
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(string $imageUrl): bool
    {
        $redis = $this->getRedis();
        if (!$redis) {
            return false;
        }

        try {
            $pattern = "imgproxy:resize:" . md5($imageUrl . "*");
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
