<?php

namespace models;

use core\Model;

class RxItem extends Model {
    protected $table = 'rx_items';

    public function getByPrescription($rxId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE rx_id = :rx");
        $stmt->execute(['rx' => $rxId]);
        return $stmt->fetchAll();
    }

    public function addItem($rxId, array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (rx_id, drug, dose, frequency, duration_days)
            VALUES (:rx_id, :drug, :dose, :frequency, :duration_days)
        ");
        $stmt->execute([
            'rx_id' => $rxId,
            'drug' => $data['drug'],
            'dose' => $data['dose'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'duration_days' => $data['duration_days'] ?? null
        ]);
        return $this->db->lastInsertId();
    }
}