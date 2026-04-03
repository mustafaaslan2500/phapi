<?php

namespace App\Helpers;

class ApiHelpers
{
    public static function show_message($bool_status, $str_message, $data = [])
    {
        $output_data = [
            'status' => $bool_status,
            'message' => $str_message,
        ];

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $output_data[$key] = $value;
            }
        }

        return $output_data;
    }

    /**
     * Recursively ensure all strings are valid UTF-8 (avoid json_encode silent failure -> blank output)
     */
    public static function utf8_sanitize($value)
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                // Preserve original key but make sure it's string-safe
                $clean_key = is_string($k) ? self::force_utf8($k) : $k;
                $clean[$clean_key] = self::utf8_sanitize($v);
            }
            return $clean;
        }
        if (is_object($value)) {
            foreach ($value as $prop => $v) {
                $value->$prop = self::utf8_sanitize($v);
            }
            return $value;
        }
        if (is_string($value)) {
            return self::force_utf8($value);
        }
        return $value;
    }

    private static function force_utf8($str)
    {
        // If already valid UTF-8 return as is
        if (mb_detect_encoding($str, 'UTF-8', true) !== false) {
            return $str;
        }
        // Try convert from common encodings
        $encodings = ['ISO-8859-9', 'ISO-8859-1', 'Windows-1254', 'Windows-1252'];
        foreach ($encodings as $enc) {
            $converted = @iconv($enc, 'UTF-8//IGNORE', $str);
            if ($converted !== false) {
                return $converted;
            }
        }
        // Fallback: strip invalid bytes
        return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    /**
     * Safe json encode with error handling – returns array with raw + encoded or error
     */
    public static function safe_json_response(array $payload)
    {
        $sanitized = self::utf8_sanitize($payload);
        $json = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $err = json_last_error_msg();
            // Build minimal fallback
            $fallback = [
                'status' => false,
                'message' => 'JSON encode error: ' . $err,
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($fallback, JSON_UNESCAPED_UNICODE);
            return false;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo $json;
        return true;
    }

    public static function send($url, $data, $method = 'POST')
    {
        $curl = curl_init();

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'GET':
                if (!empty($data)) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ['error' => true, 'message' => $error_msg];
        }

        curl_close($curl);

        return ['error' => false, 'response' => $response];
    }

    public static function normalizeMediaPath(string $url): string
    {
        $url = trim($url);
        if ($url === '') return $url;

        // @ işaretinden sonraki uzantıyı (örn: @webp) kaldır (imgproxy zaten ekleyecek)
        $url = preg_replace('/@([a-zA-Z0-9]+)$/', '', $url);

        // file/ prefix kaldır
        if (str_starts_with($url, 'file/')) {
            $url = substr($url, 5);
        }
        if (str_starts_with($url, '/file/')) {
            $url = substr($url, 6);
        }

        // local:/// zaten varsa döndür
        if (str_starts_with($url, 'local:///')) return $url;

        // Absolute URL ise dokunma
        if (preg_match('#^https?://#i', $url)) return $url;

        // Başta tek / varsa temizle
        $url = ltrim($url, '/');

        return 'local:///' . $url;
    }
}
