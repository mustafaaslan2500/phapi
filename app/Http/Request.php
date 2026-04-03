<?php

namespace App\Http;

class Request
{
    private string $rawBody;
    private array $data = [];
    private string $error;

    public function __construct()
    {
        $this->rawBody = file_get_contents('php://input') ?: '';
        $this->parseJson();
    }

    private function parseJson()
    {
        if ('' === trim($this->rawBody)) {
            // If body is empty, try to get data from $_POST or $_GET
            $this->data = array_merge($_GET, $_POST);
            return;
        }

        $decoded = json_decode($this->rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = 'Invalid JSON: ' . json_last_error_msg();
            // Fallback to $_POST and $_GET
            $this->data = array_merge($_GET, $_POST);
            return;
        }

        $this->data = ($decoded ?? []);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function isError()
    {
        return $this->error ?? null;
    }

    public function all(): array
    {
        return $this->data;
    }
}
