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
        $authHeader = $headers['Authorization'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid token header']);
            exit;
        }

        $this->token = substr($authHeader, 7);
        if ($this->token) {
            $tokenModel = new AuthToken();
            $this->authUser = $tokenModel->getUserByToken($this->token);
        }

        if (!$this->authUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
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

    public function pass() {
        if (!$this->authUser) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['employee_id'])) {
            return $this->json(['error' => 'Missing employee_id'], 400);
        }

        $employeeId = (int) $body['employee_id'];

        // Asegurarse que el empleado que quiere modificar la contraseña es él mismo
        if ((int)$this->authUser['employee_id'] !== $employeeId) {
            return $this->json(['error' => 'You can only update your own password'], 403);
        }

        // Validar campos requeridos
        if (empty($body['current_password']) || empty($body['new_password'])) {
            return $this->json(['error' => 'Current and new passwords are required'], 400);
        }

        if (strlen($body['new_password']) < 6) {
            return $this->json(['error' => 'New password must be at least 6 characters long'], 400);
        }

        // Verificar contraseña actual
        $userModel = new UserAccount();
        $user = $userModel->findByUsername($this->authUser['username']);

        if (!$user || !password_verify($body['current_password'], $user['pwd_hash'])) {
            return $this->json(['error' => 'Current password is incorrect'], 403);
        }

        // Guardar nueva contraseña hasheada
        $hashedPassword = password_hash($body['new_password'], PASSWORD_BCRYPT);
        $userModel->setPassword($employeeId, $hashedPassword);

        return $this->json(['success' => true]);
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