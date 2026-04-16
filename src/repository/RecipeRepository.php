<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../models/Recipe.php';

class RecipeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all approved recipes with nutrition, tags, and creator name.
     * Optionally filter by category or tag.
     */
    public function findAllApproved(?string $category = null, ?string $tag = null): array
    {
        $sql = '
            SELECT r.*, u.username AS creator_name
            FROM recipes r
            JOIN users u ON r.creator_id = u.id
            WHERE r.moderation_status = :status
        ';
        $params = [':status' => 'approved'];

        if ($category) {
            $sql .= ' AND r.category = :category';
            $params[':category'] = $category;
        }

        if ($tag) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM recipe_tags rt
                JOIN tags t ON rt.tag_id = t.id
                WHERE rt.recipe_id = r.id AND t.name = :tag
            )';
            $params[':tag'] = $tag;
        }

        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $recipes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recipe = Recipe::fromRow($row);
            $recipe->setTags($this->getTagsForRecipe($recipe->getId()));
            $recipe->setNutritionFacts($this->getNutritionForRecipe($recipe->getId()));
            $recipes[] = $recipe;
        }

        return $recipes;
    }

    /**
     * Get a single recipe by ID with all related data.
     */
    public function findById(int $id): ?Recipe
    {
        $stmt = $this->db->prepare('
            SELECT r.*, u.username AS creator_name
            FROM recipes r
            JOIN users u ON r.creator_id = u.id
            WHERE r.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $recipe = Recipe::fromRow($row);
        $recipe->setTags($this->getTagsForRecipe($id));
        $recipe->setNutritionFacts($this->getNutritionForRecipe($id));
        $recipe->setIngredients($this->getIngredientsForRecipe($id));
        $recipe->setCookingSteps($this->getStepsForRecipe($id));

        return $recipe;
    }

    /**
     * Check if a recipe is favorited by a user.
     */
    public function isFavorite(int $recipeId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM favorites WHERE recipe_id = :rid AND user_id = :uid');
        $stmt->execute([':rid' => $recipeId, ':uid' => $userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Save a new recipe. Returns the Recipe with ID set.
     */
    public function save(Recipe $recipe): Recipe
    {
        $stmt = $this->db->prepare('
            INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
            VALUES (:creator_id, :title, :description, :image, :category, :difficulty, :prep_time, :cook_time, :servings, :status)
            RETURNING id
        ');

        $stmt->execute([
            ':creator_id' => $recipe->getCreatorId(),
            ':title' => $recipe->getTitle(),
            ':description' => $recipe->getDescription(),
            ':image' => $recipe->getImage(),
            ':category' => $recipe->getCategory(),
            ':difficulty' => $recipe->getDifficulty(),
            ':prep_time' => $recipe->getPrepTime(),
            ':cook_time' => $recipe->getCookTime(),
            ':servings' => $recipe->getServings(),
            ':status' => $recipe->getModerationStatus(),
        ]);

        $recipe->setId((int)$stmt->fetchColumn());
        return $recipe;
    }

    /**
     * Save ingredients for a recipe.
     */
    public function saveIngredients(int $recipeId, array $ingredients): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order)
            VALUES (:recipe_id, :name, :amount, :unit, :sort_order)
        ');

        foreach ($ingredients as $i => $ing) {
            $stmt->execute([
                ':recipe_id' => $recipeId,
                ':name' => $ing['name'],
                ':amount' => $ing['amount'] ?? '',
                ':unit' => $ing['unit'] ?? '',
                ':sort_order' => $i + 1,
            ]);
        }
    }

    /**
     * Save cooking steps for a recipe.
     */
    public function saveSteps(int $recipeId, array $steps): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes)
            VALUES (:recipe_id, :step_number, :title, :instructions, :duration)
        ');

        foreach ($steps as $i => $step) {
            $stmt->execute([
                ':recipe_id' => $recipeId,
                ':step_number' => $i + 1,
                ':title' => $step['title'] ?? ('Step ' . ($i + 1)),
                ':instructions' => $step['instructions'],
                ':duration' => (int)($step['duration'] ?? 0),
            ]);
        }
    }

    /**
     * Save nutrition facts for a recipe.
     */
    public function saveNutrition(int $recipeId, array $nutrition): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
            VALUES (:recipe_id, :calories, :protein, :carbs, :fats, :fiber, :sugar)
        ');

        $stmt->execute([
            ':recipe_id' => $recipeId,
            ':calories' => (int)($nutrition['calories'] ?? 0),
            ':protein' => (float)($nutrition['protein'] ?? 0),
            ':carbs' => (float)($nutrition['carbs'] ?? 0),
            ':fats' => (float)($nutrition['fats'] ?? 0),
            ':fiber' => (float)($nutrition['fiber'] ?? 0),
            ':sugar' => (float)($nutrition['sugar'] ?? 0),
        ]);
    }

    /**
     * Save tags for a recipe.
     */
    public function saveTags(int $recipeId, array $tagIds): void
    {
        $stmt = $this->db->prepare('INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (:rid, :tid)');

        foreach ($tagIds as $tagId) {
            $stmt->execute([':rid' => $recipeId, ':tid' => $tagId]);
        }
    }

    /**
     * Get all available tags.
     */
    public function getAllTags(): array
    {
        $stmt = $this->db->query('SELECT * FROM tags ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============ Private helpers ============

    private function getTagsForRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare('
            SELECT t.name FROM tags t
            JOIN recipe_tags rt ON t.id = rt.tag_id
            WHERE rt.recipe_id = :id
            ORDER BY t.name
        ');
        $stmt->execute([':id' => $recipeId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getNutritionForRecipe(int $recipeId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM nutrition_facts WHERE recipe_id = :id');
        $stmt->execute([':id' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function getIngredientsForRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM ingredients WHERE recipe_id = :id ORDER BY sort_order
        ');
        $stmt->execute([':id' => $recipeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStepsForRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM cooking_steps WHERE recipe_id = :id ORDER BY step_number
        ');
        $stmt->execute([':id' => $recipeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
