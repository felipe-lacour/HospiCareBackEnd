<?php

namespace controllers;

use core\Controller;
use models\Employee;
use middlewares\AuthMiddleware;

class EmployeeController extends Controller {
    protected Employee $employeeModel;

    public function __construct() {
        $this->employeeModel = new Employee();
    }

    public function store() {

        $user = AuthMiddleware::getUserFromToken();
        if ($user['role_id'] !== 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
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
}