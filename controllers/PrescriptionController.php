<?php

namespace controllers;

use core\Controller;
use models\Prescription;
use models\RxItem;
use models\AuthToken;
use models\UserAccount;

class PrescriptionController extends Controller {
    protected Prescription $rxModel;
    protected RxItem $itemModel;
    protected ?array $user = null;

    public function __construct() {
        $this->rxModel = new Prescription();
        $this->itemModel = new RxItem();

        $token = $this->getBearerToken();
        if ($token) {
            $authModel = new AuthToken();
            $userData = $authModel->getUserByToken($token);

            if ($userData) {
                $userModel = new UserAccount();
                $this->user = $userModel->findByUsername($userData['user_id']);
            }
        }
    }

    private function requireAuth(): bool {
        if (!$this->user) {
            $this->json(['error' => 'Unauthorized'], 401);
            return false;
        }
        return true;
    }

    private function requireDoctor(): bool {
        if (!$this->requireAuth()) return false;
        if ((int)$this->user['role_id'] !== 2) {
            $this->json(['error' => 'Forbidden: only doctors can perform this action'], 403);
            return false;
        }
        return true;
    }

    public function index() {
        if (!$this->requireAuth()) return;
        $this->json($this->rxModel->getAll());
    }

    public function show() {
        if (!$this->requireAuth()) return;

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing rx_id'], 400);

        $rx = $this->rxModel->getById($id);
        $items = $this->itemModel->getByPrescription($id);

        $this->json([
            'prescription' => $rx,
            'items' => $items
        ]);
    }

    public function store() {
        if (!$this->requireDoctor()) return;

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        try {
            $body['doctor_id'] = $this->user['employee_id']; 
            $rxId = $this->rxModel->create($body);
            $this->json(['success' => true, 'prescription_id' => $rxId]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addItem() {
        if (!$this->requireDoctor()) return;

        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['prescription_id'], $body['drug'])) {
            return $this->json(['error' => 'Missing prescription_id or drug'], 400);
        }

        try {
            $itemId = $this->itemModel->addItem($body['prescription_id'], $body);
            $this->json(['success' => true, 'item_id' => $itemId]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        if (!$this->requireAuth()) return;

        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing prescription_id'], 400);

        try {
            $this->rxModel->delete($id);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getBearerToken(): ?string {
        $headers = apache_request_headers();
        if (!empty($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}