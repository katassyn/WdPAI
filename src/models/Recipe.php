<?php

class Recipe
{
    private ?int $id;
    private int $creatorId;
    private string $title;
    private string $description;
    private ?string $image;
    private string $category;
    private string $difficulty;
    private int $prepTime;
    private int $cookTime;
    private int $servings;
    private string $moderationStatus;
    private ?string $createdAt;

    // Relations (loaded separately)
    private ?string $creatorName;
    private array $ingredients;
    private array $cookingSteps;
    private ?array $nutritionFacts;
    private array $tags;
    private bool $isFavorite;

    public function __construct(
        int $creatorId,
        string $title,
        string $description,
        string $category,
        string $difficulty,
        int $prepTime,
        int $cookTime,
        int $servings,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->creatorId = $creatorId;
        $this->title = $title;
        $this->description = $description;
        $this->image = null;
        $this->category = $category;
        $this->difficulty = $difficulty;
        $this->prepTime = $prepTime;
        $this->cookTime = $cookTime;
        $this->servings = $servings;
        $this->moderationStatus = 'pending';
        $this->createdAt = null;
        $this->creatorName = null;
        $this->ingredients = [];
        $this->cookingSteps = [];
        $this->nutritionFacts = null;
        $this->tags = [];
        $this->isFavorite = false;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getCreatorId(): int { return $this->creatorId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getImage(): ?string { return $this->image; }
    public function getCategory(): string { return $this->category; }
    public function getDifficulty(): string { return $this->difficulty; }
    public function getPrepTime(): int { return $this->prepTime; }
    public function getCookTime(): int { return $this->cookTime; }
    public function getTotalTime(): int { return $this->prepTime + $this->cookTime; }
    public function getServings(): int { return $this->servings; }
    public function getModerationStatus(): string { return $this->moderationStatus; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getCreatorName(): ?string { return $this->creatorName; }
    public function getIngredients(): array { return $this->ingredients; }
    public function getCookingSteps(): array { return $this->cookingSteps; }
    public function getNutritionFacts(): ?array { return $this->nutritionFacts; }
    public function getTags(): array { return $this->tags; }
    public function isFavorite(): bool { return $this->isFavorite; }

    // --- Setters ---
    public function setId(int $id): void { $this->id = $id; }
    public function setImage(?string $image): void { $this->image = $image; }
    public function setModerationStatus(string $status): void { $this->moderationStatus = $status; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }
    public function setCreatorName(?string $name): void { $this->creatorName = $name; }
    public function setIngredients(array $ingredients): void { $this->ingredients = $ingredients; }
    public function setCookingSteps(array $steps): void { $this->cookingSteps = $steps; }
    public function setNutritionFacts(?array $facts): void { $this->nutritionFacts = $facts; }
    public function setTags(array $tags): void { $this->tags = $tags; }
    public function setFavorite(bool $fav): void { $this->isFavorite = $fav; }

    // --- Nutrition helpers ---
    public function getCalories(): int
    {
        return $this->nutritionFacts ? (int)($this->nutritionFacts['calories'] ?? 0) : 0;
    }

    public function getProtein(): float
    {
        return $this->nutritionFacts ? (float)($this->nutritionFacts['protein'] ?? 0) : 0;
    }

    public function getCarbs(): float
    {
        return $this->nutritionFacts ? (float)($this->nutritionFacts['carbs'] ?? 0) : 0;
    }

    public function getFats(): float
    {
        return $this->nutritionFacts ? (float)($this->nutritionFacts['fats'] ?? 0) : 0;
    }

    public function getFiber(): float
    {
        return $this->nutritionFacts ? (float)($this->nutritionFacts['fiber'] ?? 0) : 0;
    }

    // --- Tag helpers ---
    public function getTagSlugs(): string
    {
        return implode(',', $this->tags);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags);
    }

    // --- Factory ---
    public static function fromRow(array $row): Recipe
    {
        $recipe = new Recipe(
            (int)$row['creator_id'],
            $row['title'],
            $row['description'] ?? '',
            $row['category'] ?? 'dinner',
            $row['difficulty'] ?? 'medium',
            (int)($row['prep_time'] ?? 0),
            (int)($row['cook_time'] ?? 0),
            (int)($row['servings'] ?? 1),
            (int)$row['id']
        );

        $recipe->setImage($row['image'] ?? null);
        $recipe->setModerationStatus($row['moderation_status'] ?? 'pending');
        $recipe->setCreatedAt($row['created_at'] ?? null);
        $recipe->setCreatorName($row['creator_name'] ?? null);

        return $recipe;
    }
}
