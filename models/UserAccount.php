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

    public function createWithTokenLink(int $employeeId, string $username, int $roleId): array {
        // Create the account with NULL password
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (username, role_id, employee_id)
            VALUES (:username, :role_id, :employee_id)
        ");
        $stmt->execute([
            'username' => $username,
            'role_id' => $roleId,
            'employee_id' => $employeeId
        ]);

        // Generate setup token
        $token = bin2hex(random_bytes(32));

        // Store password setup request
        $psr = new \models\PasswordSetRequest();
        $psr->createToken($employeeId, $token);

        return [
            'username' => $username,
            'setup_link' => "http://localhost:5500/auth/set-password?token=$token"
        ];
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool {
        $query = "SELECT 1 FROM user_accounts WHERE username = :username";
        if ($excludeId) {
            $query .= " AND employee_id != :id";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':username', $username);
        if ($excludeId) $stmt->bindValue(':id', $excludeId);
        $stmt->execute();
        return (bool) $stmt->fetch();
    }
}