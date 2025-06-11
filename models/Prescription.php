<?php

namespace models;

use core\Model;

class Prescription extends Model {
    protected $table = 'prescriptions';

    public function getAll() {
        return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
    }

    public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM prescriptions WHERE prescription_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (patient_id, doctor_id, date, status)
            VALUES (:patient_id, :doctor_id, :date, :status)
        ");
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'date' => $data['date'] ?? date('Y-m-d'),
            'status' => $data['status'] ?? 'BORRADOR'
        ]);
        return $this->db->lastInsertId();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE rx_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}