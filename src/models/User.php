<?php

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $password;
    private ?string $avatar;
    private string $bio;
    private string $role;
    private string $status;
    private int $dailyCalories;
    private int $dailyProtein;
    private int $dailyCarbs;
    private int $dailyFats;

    public function __construct(
        string $username,
        string $email,
        string $password,
        string $role = 'user',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->avatar = null;
        $this->bio = '';
        $this->role = $role;
        $this->status = 'active';
        $this->dailyCalories = 2400;
        $this->dailyProtein = 160;
        $this->dailyCarbs = 250;
        $this->dailyFats = 70;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function getBio(): string { return $this->bio; }
    public function getRole(): string { return $this->role; }
    public function getStatus(): string { return $this->status; }
    public function getDailyCalories(): int { return $this->dailyCalories; }
    public function getDailyProtein(): int { return $this->dailyProtein; }
    public function getDailyCarbs(): int { return $this->dailyCarbs; }
    public function getDailyFats(): int { return $this->dailyFats; }

    public function setId(int $id): void { $this->id = $id; }
    public function setUsername(string $username): void { $this->username = $username; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setAvatar(?string $avatar): void { $this->avatar = $avatar; }
    public function setBio(string $bio): void { $this->bio = $bio; }
    public function setRole(string $role): void { $this->role = $role; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setDailyCalories(int $v): void { $this->dailyCalories = $v; }
    public function setDailyProtein(int $v): void { $this->dailyProtein = $v; }
    public function setDailyCarbs(int $v): void { $this->dailyCarbs = $v; }
    public function setDailyFats(int $v): void { $this->dailyFats = $v; }

    public static function fromRow(array $row): User
    {
        $user = new User(
            $row['username'],
            $row['email'],
            $row['password'],
            $row['role'],
            (int)$row['id']
        );
        $user->setAvatar($row['avatar'] ?? null);
        $user->setBio($row['bio'] ?? '');
        $user->setStatus($row['status'] ?? 'active');
        $user->setDailyCalories((int)($row['daily_calories'] ?? 2400));
        $user->setDailyProtein((int)($row['daily_protein'] ?? 160));
        $user->setDailyCarbs((int)($row['daily_carbs'] ?? 250));
        $user->setDailyFats((int)($row['daily_fats'] ?? 70));
        return $user;
    }
}
