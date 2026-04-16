<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../models/User.php';

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromRow($row) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromRow($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);

        return (bool)$stmt->fetch();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);

        return (bool)$stmt->fetch();
    }

    public function save(User $user): User
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password, role, status, bio)
             VALUES (:username, :email, :password, :role, :status, :bio)
             RETURNING id'
        );

        $stmt->execute([
            ':username' => $user->getUsername(),
            ':email' => $user->getEmail(),
            ':password' => $user->getPassword(),
            ':role' => $user->getRole(),
            ':status' => $user->getStatus(),
            ':bio' => $user->getBio(),
        ]);

        $user->setId((int)$stmt->fetchColumn());

        return $user;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM users ORDER BY id');
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::fromRow($row);
        }

        return $users;
    }

    public function updateProfile(int $id, string $username, string $email, string $bio): void
    {
        $stmt = $this->db->prepare('
            UPDATE users SET username = :username, email = :email, bio = :bio
            WHERE id = :id
        ');
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':bio' => $bio,
            ':id' => $id,
        ]);
    }

    public function updateGoals(int $id, int $calories, int $protein, int $carbs, int $fats): void
    {
        $stmt = $this->db->prepare('
            UPDATE users SET daily_calories = :cal, daily_protein = :pro, daily_carbs = :carbs, daily_fats = :fats
            WHERE id = :id
        ');
        $stmt->execute([
            ':cal' => $calories,
            ':pro' => $protein,
            ':carbs' => $carbs,
            ':fats' => $fats,
            ':id' => $id,
        ]);
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :pw WHERE id = :id');
        $stmt->execute([':pw' => $hashedPassword, ':id' => $id]);
    }
}
