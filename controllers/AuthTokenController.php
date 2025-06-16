<?php

namespace controllers;

use core\Controller;
use models\AuthToken;
use models\UserAccount;

class AuthTokenController extends Controller {
    private ?string $token = null;
    private ?array $authUser = null;

    public function __construct() {
        $headers = getallheaders();
        $this->token = $headers['Authorization'] ?? null;

        if ($this->token) {
            $authTokenModel = new AuthToken();
            $this->authUser = $authTokenModel->getUserByToken($this->token);
        }
    }

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

        return $this->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'username' => $user['username'],
                'role_id' => $user['role_id'],
                'employee_id' => $user['employee_id']
            ]
        ]);
    }

    // Logout endpoint
    public function logout() {
        if (!$this->token) {
            return $this->json(['error' => 'Token required'], 400);
        }

        $authTokenModel = new AuthToken();
        $authTokenModel->invalidateToken($this->token);

        return $this->json(['success' => true]);
    }

    // Who am I?
    public function me() {
        if (!$this->authUser) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        return $this->json(['user' => $this->authUser]);
    }
}