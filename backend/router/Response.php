<?php

class Response
{
    private int $statusCode = 200;
    private bool $sent = false;

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function json($data, int $status = null): void
    {
        if ($this->sent) return;
        $this->sent = true;

        if ($status !== null) {
            $this->statusCode = $status;
        }

        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function success($data, string $message = null): void
    {
        $response = ['success' => true];
        if ($message !== null) {
            $response['message'] = $message;
        }
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
        $this->json($response);
    }

    public function error(string $message, int $status = 400, array $extra = []): void
    {
        $response = array_merge(['success' => false, 'error' => $message], $extra);
        $this->json($response, $status);
    }
}
