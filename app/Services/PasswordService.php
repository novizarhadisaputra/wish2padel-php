<?php

namespace App\Services;

use mysqli;

class PasswordService
{
    private $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New passwords do not match.'];
        }

        $currentHash = $this->getUserHash($userId);
        
        if ($currentHash) {
            if (password_verify($currentPassword, $currentHash)) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                if ($this->updateUserHash($userId, $newHash)) {
                    return ['success' => true, 'message' => 'Password changed successfully.'];
                } else {
                    return ['success' => false, 'message' => 'Database error.'];
                }
            } else {
                return ['success' => false, 'message' => 'Incorrect current password.'];
            }
        } else {
            return ['success' => false, 'message' => 'User not found.'];
        }
    }

    protected function getUserHash(int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row['password_hash'];
        }
        return null;
    }

    protected function updateUserHash(int $userId, string $newHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $userId);
        return $stmt->execute();
    }
}
