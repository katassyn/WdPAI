<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/RecipeRepository.php';

class dashboardController extends appController
{
    private UserRepository $userRepository;
    private RecipeRepository $recipeRepository;
    private PDO $db;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->recipeRepository = new RecipeRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index()
    {
        $this->requireAuth();

        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->findById($userId);

        // Today's tracking
        $today = date('Y-m-d');
        $stmt = $this->db->prepare('SELECT * FROM daily_tracking WHERE user_id = :uid AND date = :date');
        $stmt->execute([':uid' => $userId, ':date' => $today]);
        $tracking = $stmt->fetch(PDO::FETCH_ASSOC);

        $eaten = [
            'calories' => (int)($tracking['calories'] ?? 0),
            'protein' => (float)($tracking['protein'] ?? 0),
            'carbs' => (float)($tracking['carbs'] ?? 0),
            'fats' => (float)($tracking['fats'] ?? 0),
        ];

        $goals = [
            'calories' => $user->getDailyCalories(),
            'protein' => $user->getDailyProtein(),
            'carbs' => $user->getDailyCarbs(),
            'fats' => $user->getDailyFats(),
        ];

        // Favorite recipes
        $stmt = $this->db->prepare('
            SELECT r.*, u.username AS creator_name
            FROM favorites f
            JOIN recipes r ON f.recipe_id = r.id
            JOIN users u ON r.creator_id = u.id
            WHERE f.user_id = :uid AND r.moderation_status = \'approved\'
            ORDER BY f.created_at DESC
            LIMIT 6
        ');
        $stmt->execute([':uid' => $userId]);
        $favorites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recipe = Recipe::fromRow($row);
            $recipe->setNutritionFacts($this->getNutritionForRecipe($recipe->getId()));
            $recipe->setTags($this->getTagsForRecipe($recipe->getId()));
            $recipe->setFavorite(true);
            $favorites[] = $recipe;
        }

        // Recent activity
        $stmt = $this->db->prepare('
            SELECT * FROM activity_log
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 5
        ');
        $stmt->execute([':uid' => $userId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('dashboard', [
            'title' => 'Dashboard - MacroCook',
            'user' => $user,
            'eaten' => $eaten,
            'goals' => $goals,
            'favorites' => $favorites,
            'activities' => $activities,
        ]);
    }

    public function test()
    {
        echo "test";
    }

    private function getNutritionForRecipe(int $recipeId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM nutrition_facts WHERE recipe_id = :id');
        $stmt->execute([':id' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getTagsForRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare('
            SELECT t.name FROM tags t
            JOIN recipe_tags rt ON t.id = rt.tag_id
            WHERE rt.recipe_id = :id ORDER BY t.name
        ');
        $stmt->execute([':id' => $recipeId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
