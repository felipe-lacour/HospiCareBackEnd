<?php
namespace core;

class Controller {
    protected function json($data, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}