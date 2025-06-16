<?php

namespace controllers;

use core\Controller;
use models\Appointment;
use models\AuthToken;
use models\UserAccount;

class AppointmentController extends Controller {
    protected Appointment $model;

    public function __construct() {
        $this->model = new Appointment();
    }

private function getAuthenticatedUser(): ?array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);

    if (!$token) return null;

    $authModel = new AuthToken();
    $userData = $authModel->getUserByToken($token);

    if (!$userData || (!isset($userData['username']) && !isset($userData['user_id']))) return null;

    $username = $userData['username'] ?? $userData['user_id']; // fallback if needed
    $userModel = new \models\UserAccount();
    $user = $userModel->findByUsername($username);

    return $user ?: null;
}

    public function index() {
        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        if ((int)$user['role_id'] === 2) {
            $this->json($this->model->getByDoctorId($user['employee_id']));
        } else {
            $this->json($this->model->getAll());
        }
    }

    public function show() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing ID'], 400);

        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = $this->model->getById($id);

        if ((int)$user['role_id'] === 2 && $data['doctor_id'] != $user['employee_id']) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $this->json($data ?: ['error' => 'Not found'], $data ? 200 : 404);
    }

    public function store() {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        if ((int)$user['role_id'] === 2 && $body['doctor_id'] != $user['employee_id']) {
            return $this->json(['error' => 'Doctors can only create their own appointments'], 403);
        }

        try {
            $id = $this->model->create($body);
            $this->json(['success' => true, 'appointment_id' => $id]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing ID'], 400);

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $existing = $this->model->getById($id);
        if (!$existing) return $this->json(['error' => 'Not found'], 404);

        if ((int)$user['role_id'] === 2 && $existing['doctor_id'] != $user['employee_id']) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        try {
            $this->model->update($id, $body);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing ID'], 400);

        $user = $this->getAuthenticatedUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $existing = $this->model->getById($id);
        if (!$existing) return $this->json(['error' => 'Not found'], 404);

        if ((int)$user['role_id'] === 2 && $existing['doctor_id'] != $user['employee_id']) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        try {
            $this->model->delete($id);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
