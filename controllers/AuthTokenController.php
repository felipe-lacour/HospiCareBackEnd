<?php

namespace controllers;

use core\Controller;
use models\AuthToken;
use models\UserAccount;

class AuthTokenController extends Controller {
    // Login endpoint
    public function login() {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['username'], $body['password'])) {
            return $this->json(['error' => 'Missing username or password'], 400);
        }

        $username = $body['username'];
        $password = $body['password'];

        $userModel = new UserAccount();
        $user = $userModel->getByUsername($username);

        if (!$user || !password_verify($password, $user['pwd_hash'])) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate token
        $authTokenModel = new AuthToken();
        $token = $authTokenModel->createToken($username);

        return $this->json(['token' => $token]);
    }

    // Logout (delete token)
    public function logout() {
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token required'], 400);
        }

        $authTokenModel = new AuthToken();
        $authTokenModel->invalidateToken($token);

        return $this->json(['success' => true]);
    }

    // Get current user info
    public function me() {
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token required'], 400);
        }

        $authTokenModel = new AuthToken();
        $user = $authTokenModel->getUserByToken($token);

        if (!$user) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        return $this->json(['user' => $user]);
    }
}