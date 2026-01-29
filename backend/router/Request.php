<?php

class Request
{
    public string $method;
    public string $path;
    public array $body;
    public array $headers;
    public array $params;
    public array $query;
    public ?int $userID = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->path = $this->parsePath();
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
        $this->query = $_GET;
        $this->params = [];
    }

    private function parsePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);

        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        return $path;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    private function parseBody(): array
    {
        if ($this->method === 'GET') {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                return $parsed;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['Authorization'] ?? null;
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }

        return $auth;
    }
}
