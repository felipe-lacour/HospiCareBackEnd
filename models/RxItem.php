<?php

namespace models;

use core\Model;

class RxItem extends Model {
    protected $table = 'rx_items';

    public function getByPrescription($prescriptionId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE prescription_id = :prescription_id");
        $stmt->execute(['prescription_id' => $prescriptionId]);
        return $stmt->fetchAll();
    }

    public function addItem($prescriptionId, array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (prescription_id, drug, dose, frequency, duration_days)
            VALUES (:prescription_id, :drug, :dose, :frequency, :duration_days)
        ");
        $stmt->execute([
            'prescription_id' => $prescriptionId,
            'drug' => $data['drug'],
            'dose' => $data['dose'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'duration_days' => $data['duration_days'] ?? null
        ]);
        return $this->db->lastInsertId();
    }
}