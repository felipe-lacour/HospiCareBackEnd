<?php

namespace models;

use core\Model;
use models\Person;
use models\Employee;
use models\UserAccount;
use models\PasswordSetRequest;

class Doctor extends Model {
    protected $table = 'doctors';

    public function createDoctor(array $personData, array $doctorData): array {
        // 1. Create employee, person, and user account together
        $employeeModel = new Employee();
        $employeeResult = $employeeModel->createWithAccount($personData, 2); // 2 is the role ID for doctors

        $employeeId = $employeeResult['employee_id'];
        $username = $employeeResult['username'];
        $link = $employeeResult['setup_link'];

        // 2. Create doctor
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (doctor_id, license_no, specialty)
            VALUES (:doctor_id, :license_no, :specialty)
        ");
        $stmt->execute([
            'doctor_id' => $employeeId,
            'license_no' => $doctorData['license_no'],
            'specialty' => $doctorData['specialty']
        ]);

        return [
            'employee_id' => $employeeId,
            'username' => $username,
            'setup_link' => $link
        ];
    }

    public function getAllDoctors(): array {
        $stmt = $this->db->prepare("
            SELECT d.*, e.email, p.first_name, p.last_name, p.dni, p.birth_date, p.address, p.phone
            FROM doctors d
            JOIN employees e ON d.doctor_id = e.employee_id
            JOIN persons p ON e.person_id = p.person_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function sendPasswordSetupEmail(string $email, string $username, string $token): string {
        return "http://localhost:8000/auth/set-password?token=$token";
    }

    public function getDoctorById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT 
                d.*, 
                e.email, 
                p.first_name, 
                p.last_name, 
                p.dni, 
                p.birth_date, 
                p.address, 
                p.phone,
                u.username
            FROM doctors d
            JOIN employees e ON d.doctor_id = e.employee_id
            JOIN persons p ON e.person_id = p.person_id
            LEFT JOIN user_accounts u ON u.employee_id = e.employee_id
            WHERE d.doctor_id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateDoctor(int $doctorId, array $personData, array $doctorData, ?string $username = null): void {
        $stmt = $this->db->prepare("
            SELECT e.person_id
            FROM employees e
            JOIN user_accounts u ON u.employee_id = e.employee_id
            WHERE e.employee_id = :id
        ");
        $stmt->execute(['id' => $doctorId]);
        $row = $stmt->fetch();

        if (!$row) throw new \Exception("Doctor not found.");

        $personId = $row['person_id'];

        // Actualizar persona
        $updates = [];
        foreach ($personData as $key => $value) {
            $updates[] = "$key = :$key";
        }
        $stmt = $this->db->prepare("
            UPDATE persons SET " . implode(', ', $updates) . " WHERE person_id = :person_id
        ");
        $stmt->execute(array_merge($personData, ['person_id' => $personId]));

        // Actualizar email en employees
        $stmt = $this->db->prepare("
            UPDATE employees SET email = :email WHERE employee_id = :id
        ");
        $stmt->execute([
            'email' => $personData['email'],
            'id' => $doctorId
        ]);

        // Actualizar doctor
        $stmt = $this->db->prepare("
            UPDATE doctors SET license_no = :license_no, specialty = :specialty WHERE doctor_id = :id
        ");
        $stmt->execute(array_merge($doctorData, ['id' => $doctorId]));

        // Actualizar username si es necesario
        if ($username) {
            $stmt = $this->db->prepare("
                UPDATE user_accounts SET username = :username WHERE employee_id = :id
            ");
            $stmt->execute(['username' => $username, 'id' => $doctorId]);
        }
    }
}