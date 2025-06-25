<?php

namespace models;

use core\Model;

class Appointment extends Model {
    protected $table = 'appointments';

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY datetime DESC");
        return $stmt->fetchAll();
    }

    public function getByDoctorId(int $doctorId): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE doctor_id = :did ORDER BY datetime DESC");
        $stmt->execute(['did' => $doctorId]);
        return $stmt->fetchAll();
    }

    public function getById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE appointment_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

public function create(array $data): int {
    $sql = "
        INSERT INTO {$this->table}
          (patient_id, doctor_id, room_id, datetime, status)
        VALUES
          (:patient_id, :doctor_id, :room_id, :datetime, :status)
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'patient_id' => $data['patient_id'],
        'doctor_id'  => $data['doctor_id'],
        'room_id'    => $data['room_id'] ?? null,
        'datetime'   => $data['datetime'],
        'status'     => $data['status']   ?? 'PENDIENTE',
    ]);
    return (int)$this->db->lastInsertId();
}

public function update(int $id, array $data): bool {
    $sql = "
        UPDATE {$this->table}
        SET
          patient_id = :patient_id,
          doctor_id  = :doctor_id,
          room_id    = :room_id,
          datetime   = :datetime,
          status     = :status
        WHERE appointment_id = :id
    ";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        'id'         => $id,
        'patient_id' => $data['patient_id'],
        'doctor_id'  => $data['doctor_id'],
        'room_id'    => $data['room_id'] ?? null,
        'datetime'   => $data['datetime'],
        'status'     => $data['status'],
    ]);
}
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE appointment_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

}
