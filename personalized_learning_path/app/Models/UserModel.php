<?php
declare(strict_types=1);

class UserModel {
    #tìm user theo email
    public function findByEmail(string $email): ?array {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    #tìm user theo id
    public function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    #tạo user mới và trả về id vừa tạo
    public function create(string $fullName, string $email, string $passwordHash): int {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, full_name) VALUES (:email, :hash, :name)'
            );
            $stmt->execute(['email' => $email, 'hash' => $passwordHash, 'name' => $fullName]);
            $userId = (int) $pdo->lastInsertId();

            $pdo->prepare('INSERT INTO user_settings (user_id) VALUES (:id)')->execute(['id' => $userId]);

            $pdo->commit();
            return $userId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}