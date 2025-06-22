<?php

namespace models;

use core\Model;
use models\Person;
use models\UserAccount;
use models\PasswordSetRequest;

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
            SELECT e.*, p.first_name, p.last_name, p.dni, p.birth_date, p.address, p.phone, u.username
            FROM employees e
            JOIN persons p ON e.person_id = p.person_id
            LEFT JOIN user_accounts u ON u.employee_id = e.employee_id
            WHERE e.employee_id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createWithAccount(array $personData, int $roleId): array {
        // 1. Create person
        $personModel = new Person();
        $personId = $personModel->create($personData);

        // 2. Create employee
        $email = $personData['email'] ?? null;
        if (!$email) {
            throw new \Exception("Email is required to create employee.");
        }

        $hireDate = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (person_id, email, hire_date)
            VALUES (:person_id, :email, :hire_date)
        ");
        $stmt->execute([
            'person_id' => $personId,
            'email' => $email,
            'hire_date' => $hireDate,
        ]);
        $employeeId = (int) $this->db->lastInsertId();

        // 3. Create user account (username = email)
        $username = $email;
        $userModel = new UserAccount();
        if ($userModel->findByUsername($username)) {
            throw new \Exception("Username already exists.");
        }

        $placeholderPassword = password_hash(bin2hex(random_bytes(6)), PASSWORD_BCRYPT);
        $userModel->create([
            'username' => $username,
            'employee_id' => $employeeId,
            'role_id' => $roleId,
            'pwd_hash' => $placeholderPassword
        ]);

        // 4. Create password setup token
        $token = bin2hex(random_bytes(32));
        $psrModel = new PasswordSetRequest();
        $psrModel->createToken($employeeId, $token);

        $setupLink = "http://localhost:8000/auth/set-password?token=$token";

        return [
            'employee_id' => $employeeId,
            'person_id' => $personId,
            'username' => $username,
            'setup_link' => $setupLink
        ];
    }

    public function getAllNonDoctorsEmployees(): array {
        $stmt = $this->db->prepare("
            SELECT e.*, p.first_name, p.last_name, p.dni, p.birth_date, p.address, p.phone
            FROM employees e
            JOIN persons p ON e.person_id = p.person_id
            WHERE e.employee_id NOT IN (SELECT doctor_id FROM doctors)
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}