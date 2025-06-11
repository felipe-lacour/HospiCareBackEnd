<?php

namespace models;

use core\Model;

class Employee extends Model {
    protected string $table = 'employees';

    public function create(array $data): int {
        // Optional safety check
        if (!isset($data['person_id'], $data['email'], $data['hire_date'])) {
            throw new \Exception("Missing required fields for employee creation.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (person_id, email, hire_date)
            VALUES (:person_id, :email, :hire_date)
        ");

        $stmt->execute([
            'person_id' => $data['person_id'],
            'email' => $data['email'],
            'hire_date' => $data['hire_date'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE employee_id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}