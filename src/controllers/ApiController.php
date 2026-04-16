<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/RecipeRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class ApiController extends appController
{
    private RecipeRepository $recipeRepository;
    private PDO $db;

    public function __construct()
    {
        $this->recipeRepository = new RecipeRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * POST /api/favorite — toggle favorite for a recipe
     * Body: { recipe_id: int }
     * Returns: { success: true, favorited: bool }
     */
    public function favorite()
    {
        $this->requireAuth();
        $this->requirePost();

        $data = $this->getJsonInput();
        $recipeId = (int)($data['recipe_id'] ?? 0);
        $userId = $_SESSION['user_id'];

        if ($recipeId <= 0) {
            return $this->json(['success' => false, 'error' => 'Invalid recipe ID'], 400);
        }

        // Check if already favorited
        $isFav = $this->recipeRepository->isFavorite($recipeId, $userId);

        if ($isFav) {
            // Remove favorite
            $stmt = $this->db->prepare('DELETE FROM favorites WHERE user_id = :uid AND recipe_id = :rid');
            $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
            return $this->json(['success' => true, 'favorited' => false]);
        } else {
            // Add favorite
            $stmt = $this->db->prepare('INSERT INTO favorites (user_id, recipe_id) VALUES (:uid, :rid)');
            $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
            return $this->json(['success' => true, 'favorited' => true]);
        }
    }

    /**
     * GET /api/search?q=salmon
     * Returns: { results: [ { id, title, category, image, calories, total_time } ] }
     */
    public function search()
    {
        $this->requireAuth();

        $query = trim($_GET['q'] ?? '');

        if (strlen($query) < 2) {
            return $this->json(['results' => []]);
        }

        $stmt = $this->db->prepare("
            SELECT r.id, r.title, r.category, r.image, r.prep_time, r.cook_time,
                   nf.calories, u.username AS creator_name
            FROM recipes r
            LEFT JOIN nutrition_facts nf ON r.id = nf.recipe_id
            JOIN users u ON r.creator_id = u.id
            WHERE r.moderation_status = 'approved'
              AND (
                  LOWER(r.title) LIKE :q
                  OR LOWER(r.description) LIKE :q
                  OR EXISTS (
                      SELECT 1 FROM ingredients i
                      WHERE i.recipe_id = r.id AND LOWER(i.name) LIKE :q
                  )
              )
            ORDER BY r.created_at DESC
            LIMIT 10
        ");

        $like = '%' . strtolower($query) . '%';
        $stmt->execute([':q' => $like]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'category' => $row['category'],
                'image' => $row['image'],
                'calories' => (int)($row['calories'] ?? 0),
                'total_time' => (int)$row['prep_time'] + (int)$row['cook_time'],
                'creator' => $row['creator_name'],
            ];
        }

        return $this->json(['results' => $results]);
    }

    /**
     * POST /api/tracking — save daily macro tracking
     * Body: { calories, protein, carbs, fats }
     * Returns: { success: true, tracking: { ... } }
     */
    public function tracking()
    {
        $this->requireAuth();
        $this->requirePost();

        $data = $this->getJsonInput();
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');

        $calories = (int)($data['calories'] ?? 0);
        $protein = (float)($data['protein'] ?? 0);
        $carbs = (float)($data['carbs'] ?? 0);
        $fats = (float)($data['fats'] ?? 0);

        // Upsert: insert or update daily tracking
        $stmt = $this->db->prepare('
            INSERT INTO daily_tracking (user_id, date, calories, protein, carbs, fats)
            VALUES (:uid, :date, :cal, :pro, :carbs, :fats)
            ON CONFLICT (user_id, date) DO UPDATE SET
                calories = EXCLUDED.calories,
                protein = EXCLUDED.protein,
                carbs = EXCLUDED.carbs,
                fats = EXCLUDED.fats
            RETURNING *
        ');

        $stmt->execute([
            ':uid' => $userId,
            ':date' => $today,
            ':cal' => $calories,
            ':pro' => $protein,
            ':carbs' => $carbs,
            ':fats' => $fats,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->json(['success' => true, 'tracking' => $row]);
    }

    /**
     * GET /api/tracking — get today's tracking data
     * Returns: { tracking: { ... } | null }
     */
    public function getTracking()
    {
        $this->requireAuth();

        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');

        $stmt = $this->db->prepare('
            SELECT * FROM daily_tracking
            WHERE user_id = :uid AND date = :date
        ');
        $stmt->execute([':uid' => $userId, ':date' => $today]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->json(['tracking' => $row ?: null]);
    }

    /**
     * POST /api/log-meal — log a recipe as eaten (add to tracking + activity)
     * Body: { recipe_id: int }
     */
    public function logMeal()
    {
        $this->requireAuth();
        $this->requirePost();

        $data = $this->getJsonInput();
        $recipeId = (int)($data['recipe_id'] ?? 0);
        $userId = $_SESSION['user_id'];

        if ($recipeId <= 0) {
            return $this->json(['success' => false, 'error' => 'Invalid recipe ID'], 400);
        }

        // Get recipe nutrition
        $recipe = $this->recipeRepository->findById($recipeId);
        if (!$recipe) {
            return $this->json(['success' => false, 'error' => 'Recipe not found'], 404);
        }

        $today = date('Y-m-d');

        // Get current tracking or create new
        $stmt = $this->db->prepare('SELECT * FROM daily_tracking WHERE user_id = :uid AND date = :date');
        $stmt->execute([':uid' => $userId, ':date' => $today]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        $newCal = ($current['calories'] ?? 0) + $recipe->getCalories();
        $newPro = ($current['protein'] ?? 0) + $recipe->getProtein();
        $newCarbs = ($current['carbs'] ?? 0) + $recipe->getCarbs();
        $newFats = ($current['fats'] ?? 0) + $recipe->getFats();

        // Upsert tracking
        $stmt = $this->db->prepare('
            INSERT INTO daily_tracking (user_id, date, calories, protein, carbs, fats)
            VALUES (:uid, :date, :cal, :pro, :carbs, :fats)
            ON CONFLICT (user_id, date) DO UPDATE SET
                calories = EXCLUDED.calories,
                protein = EXCLUDED.protein,
                carbs = EXCLUDED.carbs,
                fats = EXCLUDED.fats
        ');
        $stmt->execute([
            ':uid' => $userId,
            ':date' => $today,
            ':cal' => $newCal,
            ':pro' => $newPro,
            ':carbs' => $newCarbs,
            ':fats' => $newFats,
        ]);

        // Log activity
        $stmt = $this->db->prepare('
            INSERT INTO activity_log (user_id, type, description)
            VALUES (:uid, :type, :desc)
        ');
        $stmt->execute([
            ':uid' => $userId,
            ':type' => 'cooked',
            ':desc' => 'You cooked ' . $recipe->getTitle(),
        ]);

        return $this->json([
            'success' => true,
            'tracking' => [
                'calories' => $newCal,
                'protein' => $newPro,
                'carbs' => $newCarbs,
                'fats' => $newFats,
            ],
            'recipe' => $recipe->getTitle(),
        ]);
    }

    // =========== Helper methods ===========

    private function requirePost(): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
            exit;
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    private function json(array $data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
