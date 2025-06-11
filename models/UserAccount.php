<?php

namespace models;

use core\Model;

class UserAccount extends Model {
    protected $table = 'user_accounts';

    public function setPassword(int $employeeId, string $hash): void {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET pwd_hash = :hash
            WHERE employee_id = :id
        ");
        $stmt->execute([
            'hash' => $hash,
            'id' => $employeeId
        ]);
    }

    public function findByUsername(string $username): array|false {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE username = :username
        ");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function verifyLogin(string $username, string $password): array|false {
        $user = $this->findByUsername($username);
        if (!$user || !password_verify($password, $user['pwd_hash'])) {
            return false;
        }

        $this->updateLastLogin($username);
        return $user;
    }

    public function updateLastLogin(string $username): void {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET last_login = CURRENT_TIMESTAMP
            WHERE username = :username
        ");
        $stmt->execute(['username' => $username]);
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (username, role_id, employee_id, pwd_hash)
            VALUES (:username, :role_id, :employee_id, :pwd_hash)
        ");
        return $stmt->execute([
            'username'    => $data['username'],
            'role_id'     => $data['role_id'],
            'employee_id' => $data['employee_id'],
            'pwd_hash'    => $data['pwd_hash']
        ]);
    }
}