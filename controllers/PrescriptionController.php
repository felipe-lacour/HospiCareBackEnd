<?php

namespace controllers;

use core\Controller;
use models\Prescription;
use models\RxItem;

class PrescriptionController extends Controller {
    protected Prescription $rxModel;
    protected RxItem $itemModel;

    public function __construct() {
        $this->rxModel = new Prescription();
        $this->itemModel = new RxItem();
    }

    public function index() {
        $this->json($this->rxModel->getAll());
    }

    public function show() {
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
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) return $this->json(['error' => 'Invalid JSON'], 400);

        try {
            $rxId = $this->rxModel->create($body);
            $this->json(['success' => true, 'rx_id' => $rxId]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addItem() {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['rx_id'], $body['drug']))
            return $this->json(['error' => 'Missing rx_id or drug'], 400);

        try {
            $itemId = $this->itemModel->addItem($body['rx_id'], $body);
            $this->json(['success' => true, 'item_id' => $itemId]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->json(['error' => 'Missing rx_id'], 400);

        try {
            $this->rxModel->delete($id);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}