<?php

namespace middlewares;

use models\AuthToken;
use models\UserAccount;

class AuthMiddleware {
    public static function authenticate(): array {
        $user = self::getUserFromToken();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing token']);
            exit;
        }

        return $user;
    }

    public static function getUserFromToken(): ?array {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) return null;

        $token = trim(str_replace('Bearer', '', $headers['Authorization']));
        $authModel = new AuthToken();
        $userData = $authModel->getUserByToken($token);

        if (!$userData) return null;

        $userModel = new UserAccount();
        $user = $userModel->findByUsername($userData['username']);

        return $user ?: null;
    }

    public static function requireRole(array $allowedRoles): array {
        $user = self::authenticate();

        if (!in_array($user['role_id'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        return $user;
    }
}