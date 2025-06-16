<?php

namespace controllers;

use core\Controller;
use models\Doctor;
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

        if ($user['role_id'] == 2 && $doctor && $doctor['doctor_id'] != $user['employee_id']) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        if ($doctor) {
            $this->json($doctor);
        } else {
            $this->json(['error' => 'Doctor not found'], 404);
        }
    }

    public function store() {
        $user = $this->getAuthenticatedUser();
        if (!$user || $user['role_id'] != 1) {
            return $this->json(['error' => 'Only admins can create doctors'], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        try {
            $personData = [
                'dni' => $body['dni'],
                'first_name' => $body['first_name'],
                'last_name' => $body['last_name'],
                'birth_date' => $body['birth_date'],
                'address' => $body['address'],
                'phone' => $body['phone']
            ];

            $employeeData = [
                'email' => $body['email'],
                'hire_date' => date('Y-m-d H:i:s'),
            ];

            $doctorData = [
                'license_no' => $body['license_no'],
                'specialty' => $body['specialty']
            ];

            $userData = [
                'username' => $body['username'],
                'role_id' => $body['role_id'] ?? 2
            ];

            $result = $this->doctorModel->createDoctor($personData, $employeeData, $doctorData, $userData);

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

    private function getAuthenticatedUser(): ?array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token) return null;

        $authModel = new AuthToken();
        return $authModel->getUserByToken($token);
    }
}
