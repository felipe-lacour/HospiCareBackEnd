<?php

namespace controllers;

use core\Controller;
use models\Appointment;

class AppointmentController extends Controller {
    protected Appointment $model;

    public function __construct() {
        $this->model = new Appointment();
    }

    public function index() {
        $this->json($this->model->getAll());
    }

    public function show() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing ID'], 400);

        $data = $this->model->getById($id);
        $this->json($data ?: ['error' => 'Not found'], $data ? 200 : 404);
    }

    public function store() {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

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

        try {
            $this->model->delete($id);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}