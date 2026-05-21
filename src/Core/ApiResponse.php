<?php

namespace App\Core;

final class ApiResponse
{
    /**
     * Successful mutation or read. $data is the authoritative
     * post-mutation resource state the client should render.
     */
    public static function success(mixed $data, string $message = 'OK'): array
    {
        return [
            'ok'      => true,
            'message' => $message,
            'data'    => $data,
        ];
    }

    /**
     * Any failure — auth, validation, or server error.
     * $code is an HTTP status code; JS can inspect it for redirect logic.
     */
    public static function error(string $message, int $code = 400): array
    {
        return [
            'ok'      => false,
            'message' => $message,
            'data'    => null,
            'code'    => $code,
        ];
    }

    /**
     * Serialises a payload array, sets the Content-Type header,
     * sets the HTTP status code, and exits. Call once per request.
     */
    public static function send(array $payload, int $httpStatus = 200): never
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}
