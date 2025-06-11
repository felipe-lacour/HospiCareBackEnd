<?php
namespace models;

use core\Model;

class PasswordSetRequest extends Model {
    protected $table = 'password_set_requests';

    public function createToken($employeeId, $token) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (token, employee_id) 
            VALUES (:token, :employee_id)
        ");
        $stmt->execute(['token' => $token, 'employee_id' => $employeeId]);
    }

    public function getByToken($token) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE token = :token
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    public function markUsed($token) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET used = 1 
            WHERE token = :token
        ");
        $stmt->execute(['token' => $token]);
    }
}