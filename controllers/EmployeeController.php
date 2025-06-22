<?php

namespace controllers;

use core\Controller;
use models\Employee;
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

        // Expected fields: first_name, last_name, dni, birth_date, address, phone, email
        $required = ['first_name', 'last_name', 'dni', 'birth_date', 'address', 'phone', 'email'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            $result = $this->employeeModel->createWithAccount($body, 3); // 3 = receptionist (or general employee)

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
}