<?php

namespace models;

use core\Model;

class Appointment extends Model {
    protected $table = 'appointments';

    public function getAll() {
        $stmt = $this->db->query("
            SELECT * FROM {$this->table}
        ");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} WHERE appointment_id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (patient_id, doctor_id, room_id, datetime, status)
            VALUES (:patient_id, :doctor_id, :room_id, :datetime, :status)
        ");
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'room_id' => $data['room_id'],
            'datetime' => $data['datetime'],
            'status' => $data['status'] ?? 'PENDIENTE'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET patient_id = :patient_id,
                doctor_id = :doctor_id,
                room_id = :room_id,
                datetime = :datetime,
                status = :status
            WHERE appointment_id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'room_id' => $data['room_id'],
            'datetime' => $data['datetime'],
            'status' => $data['status']
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE appointment_id = :id");
        return $stmt->execute(['id' => $id]);
    }
}