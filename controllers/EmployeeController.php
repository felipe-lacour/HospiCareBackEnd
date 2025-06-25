<?php

namespace controllers;

use core\Controller;
use models\Employee;
use models\Person;
use models\UserAccount;
use models\AuthToken;

class EmployeeController extends Controller {
    protected Employee $employeeModel;

    public function __construct() {
        $this->employeeModel = new Employee();
    }

    public function store() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can add employees'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        $required = ['first_name', 'last_name', 'dni', 'birth_date', 'address', 'phone', 'email'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            if ($this->employeeModel->emailExists($body['email'])) {
                return $this->json(['error' => 'Email already exists'], 400);
            }
            $personModel = new Person();
            if ($personModel->dniExists($body['dni'])) {
                return $this->json(['error' => 'DNI already exists'], 400);
            }

            $result = $this->employeeModel->createWithAccount($body, 3);

            $this->json([
                'success' => true,
                'employee_id' => $result['employee_id'],
                'username' => $result['username'],
                'setup_link' => $result['setup_link']
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function index() {
        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $employees = $this->employeeModel->getAllNonDoctorsEmployees();
        $this->json($employees);
    }

    public function show() {
        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing employee ID'], 400);

        $employee = $this->employeeModel->getById($id);

        if ($employee) {
            $this->json($employee);
        } else {
            $this->json(['error' => 'Employee not found'], 404);
        }
    }

    public function update() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can update employees'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['employee_id'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $required = ['first_name', 'last_name', 'address', 'phone', 'email', 'username'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            $employeeId = (int)$body['employee_id'];

            $personData = [
                'first_name' => $body['first_name'],
                'last_name' => $body['last_name'],
                'address' => $body['address'],
                'phone' => $body['phone'],
            ];

            $email = $body['email'] ?? null;
            $username = $body['username'] ?? null;

            if ($this->employeeModel->emailExists($body['email'], $employeeId)) {
                return $this->json(['error' => 'Email already exists'], 400);
            }
            $userModel = new UserAccount();
            if ($userModel->usernameExists($body['username'], $employeeId)) {
                return $this->json(['error' => 'Username already exists'], 400);
            }

            $this->employeeModel->update($employeeId, $personData, $email, $username);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function change() {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['employee_id'])) {
            return $this->json(['error' => 'Missing employee_id'], 400);
        }

        $employeeId = (int) $body['employee_id'];

        if ((int)$user['employee_id'] !== $employeeId) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $required = ['address', 'phone', 'email', 'username'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            $personData = [
                'address' => $body['address'],
                'phone' => $body['phone'],
            ];

            $email = $body['email'];
            $username = $body['username'];

            if ($this->employeeModel->emailExists($email, $employeeId)) {
                return $this->json(['error' => 'Email already exists'], 400);
            }
            $userModel = new \models\UserAccount();
            if ($userModel->usernameExists($username, $employeeId)) {
                return $this->json(['error' => 'Username already exists'], 400);
            }

            $this->employeeModel->update($employeeId, $personData, $email, $username);

            return $this->json([
                'success' => true,
                'user' => [
                    'username' => $username,
                    'role_id' => $user['role_id'],
                    'employee_id' => $employeeId
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function getAuthenticatedUser(): ?array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid token header']);
            exit;
        }

        $token = substr($authHeader, 7);
        $tokenModel = new AuthToken();
        $userData = $tokenModel->getUserByToken($token);

        if (!$userData) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }
        
        return $userData;
    }

    public function delete() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can delete employees'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['employee_id'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        try {
            $employeeId = (int)$body['employee_id'];

            $this->employeeModel->delete($employeeId);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}