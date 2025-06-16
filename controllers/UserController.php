<?php

namespace controllers;

use core\Controller;
use models\UserAccount;
use models\AuthToken;

class UserController extends Controller {
    private ?string $token = null;
    private ?array $authUser = null;

    public function __construct() {
        $headers = getallheaders();
        $this->token = $headers['Authorization'] ?? null;

        if ($this->token) {
            $tokenModel = new AuthToken();
            $this->authUser = $tokenModel->getUserByToken($this->token);
        }
    }

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

        $tokenModel = new AuthToken();
        $token = $tokenModel->generateToken($username);

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

    public function logout() {
        if (!$this->token) {
            return $this->json(['error' => 'No token provided'], 400);
        }

        $tokenModel = new AuthToken();
        $tokenModel->invalidateToken($this->token);

        return $this->json(['success' => true]);
    }

    public function me() {
        if (!$this->authUser) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        return $this->json(['user' => $this->authUser]);
    }

    public function index() {
        if (!$this->authUser) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($this->authUser['role_id'] != 1) {
            return $this->json(['error' => 'Forbidden. Admin access required.'], 403);
        }

        $userModel = new UserAccount();
        $allUsers = $userModel->getAllUsers();

        return $this->json($allUsers);
    }
}