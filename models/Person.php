<?php

namespace models;

use core\Model;

class Person extends Model {
    protected $table = 'persons';

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE person_id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (dni, first_name, last_name, birth_date, address, phone)
            VALUES (:dni, :first_name, :last_name, :birth_date, :address, :phone)
        ");

        $stmt->execute([
            'dni' => $data['dni'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'phone' => $data['phone']
        ]);

        return $this->db->lastInsertId();
    }

    public function dniExists(string $dni, ?int $excludeId = null): bool {
        $query = "
            SELECT 1 FROM persons p
            JOIN employees e ON p.person_id = e.person_id
            WHERE p.dni = :dni";
        if ($excludeId) {
            $query .= " AND e.employee_id != :id";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':dni', $dni);
        if ($excludeId) $stmt->bindValue(':id', $excludeId);
        $stmt->execute();
        return (bool) $stmt->fetch();
    }
}