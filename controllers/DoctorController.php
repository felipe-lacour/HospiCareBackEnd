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
                'phone' => $body['phone'],
                'email' => $body['email'],
            ];

            $doctorData = [
                'license_no' => $body['license_no'],
                'specialty' => $body['specialty']
            ];

            $result = $this->doctorModel->createDoctor($personData, $doctorData);

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

        try {
            $doctorId = (int)$body['doctor_id'];

            $personData = [
                'first_name' => $body['first_name'],
                'last_name' => $body['last_name'],
                'address' => $body['address'],
                'phone' => $body['phone'],
                'email' => $body['email']
            ];

            $doctorData = [
                'license_no' => $body['license_no'],
                'specialty' => $body['specialty']
            ];

            $username = $body['username'] ?? null;

            $this->doctorModel->updateDoctor($doctorId, $personData, $doctorData, $username);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
