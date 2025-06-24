<?php

namespace controllers;

use core\Controller;
use models\Doctor;
use models\Employee;
use models\Person;
use models\UserAccount;
use models\AuthToken;

class DoctorController extends Controller {
    protected Doctor $doctorModel;

    public function __construct() {
        $this->doctorModel = new Doctor();
    }

    public function index() {
        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        // All roles can view the list of doctors
        $doctors = $this->doctorModel->getAllDoctors();
        $this->json($doctors);
    }

    public function show() {
        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing doctor ID'], 400);

        $doctor = $this->doctorModel->getDoctorById($id);

        if ($doctor) {
            $this->json($doctor);
        } else {
            $this->json(['error' => 'Doctor not found'], 404);
        }
    }

    public function store() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can add doctors'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        // Expected fields
        $required = ['first_name', 'last_name', 'dni', 'birth_date', 'address', 'phone', 'email', 'license_no', 'specialty'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            $personData = [
                'dni' => $body['dni'],
                'first_name' => $body['first_name'],
                'last_name' => $body['last_name'],
                'birth_date' => $body['birth_date'],
                'address' => $body['address'],
                'phone' => $body['phone'],
                'email' => $body['email'],
            ];

            $doctorData = [
                'license_no' => $body['license_no'],
                'specialty' => $body['specialty']
            ];

            // Validar unicidad de datos
            $employeeModel = new Employee();
            if ($employeeModel->emailExists($body['email'])) {
                return $this->json(['error' => 'Email already exists'], 400);
            }
            $personModel = new Person();
            if ($personModel->dniExists($body['dni'])) {
                return $this->json(['error' => 'DNI already exists'], 400);
            }
            if ($this->doctorModel->licenseExists($body['license_no'])) {
                return $this->json(['error' => 'License number already exists'], 400);
            }

            $result = $this->doctorModel->createDoctor($personData, $doctorData);

            $this->json([
                'success' => true,
                'doctor_id' => $result['employee_id'],
                'username' => $result['username'],
                'setup_link' => $result['setup_link']
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
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

    public function update() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can update doctors'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['doctor_id'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $required = ['first_name', 'last_name', 'address', 'phone', 'email', 'license_no', 'specialty', 'username'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json(['error' => "Missing field: $field"], 400);
            }
        }

        try {
            $doctorId = (int)$body['doctor_id'];

            $personData = [
                'first_name' => $body['first_name'],
                'last_name' => $body['last_name'],
                'address' => $body['address'],
                'phone' => $body['phone']
            ];

            $doctorData = [
                'license_no' => $body['license_no'],
                'specialty' => $body['specialty']
            ];

            $email = $body['email'] ?? null;
            $username = $body['username'] ?? null;

            $employeeModel = new Employee();
            if ($employeeModel->emailExists($body['email'], $doctorId)) {
                return $this->json(['error' => 'Email already exists'], 400);
            }
            $userModel = new UserAccount();
            if ($userModel->usernameExists($body['username'], $doctorId)) {
                return $this->json(['error' => 'Username already exists'], 400);
            }
            if ($this->doctorModel->licenseExists($body['license_no'], $doctorId)) {
                return $this->json(['error' => 'License number already exists'], 400);
            }

            $this->doctorModel->updateDoctor($doctorId, $personData, $doctorData, $email, $username);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function employment() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can change doctors status'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $doctorId = $input['doctor_id'] ?? null;
        $employed = $input['employed'] ?? null;

        if (!$doctorId || !isset($employed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
            return;
        }

        $doctorModel = new Doctor();
        $success = $doctorModel->updateEmployment($doctorId, $employed);
        echo json_encode(['success' => $success]);
    }
}
