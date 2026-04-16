<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/RecipeRepository.php';

class RecipeController extends appController
{
    private RecipeRepository $recipeRepository;

    public function __construct()
    {
        $this->recipeRepository = new RecipeRepository();
    }

    /**
     * GET /recipes — Recipe library (list)
     * Supports ?category=breakfast and ?tag=high-protein filters
     */
    public function recipes()
    {
        $this->requireAuth();

        $category = $_GET['category'] ?? null;
        $tag = $_GET['tag'] ?? null;

        $recipes = $this->recipeRepository->findAllApproved($category, $tag);
        $tags = $this->recipeRepository->getAllTags();

        // Check favorites for logged-in user
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            foreach ($recipes as $recipe) {
                $recipe->setFavorite(
                    $this->recipeRepository->isFavorite($recipe->getId(), $userId)
                );
            }
        }

        return $this->render('recipes', [
            'recipes' => $recipes,
            'tags' => $tags,
            'activeCategory' => $category,
            'activeTag' => $tag,
        ]);
    }

    /**
     * GET /recipe?id=1 — Single recipe detail
     */
    public function detail()
    {
        $this->requireAuth();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/recipes');
            return;
        }

        $recipe = $this->recipeRepository->findById($id);

        if (!$recipe || $recipe->getModerationStatus() !== 'approved') {
            $this->redirect('/recipes');
            return;
        }

        // Check if favorited
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $recipe->setFavorite(
                $this->recipeRepository->isFavorite($recipe->getId(), $userId)
            );
        }

        return $this->render('recipe', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * GET /creator — Recipe creation form
     * POST /creator — Save new recipe
     */
    public function creator()
    {
        $this->requireAuth();

        if ($this->isPost()) {
            return $this->handleCreateRecipe();
        }

        $tags = $this->recipeRepository->getAllTags();
        return $this->render('creator', [
            'tags' => $tags,
        ]);
    }

    /**
     * GET /cooking?id=1 — Step-by-step cooking mode
     */
    public function cooking()
    {
        $this->requireAuth();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirect('/recipes');
            return;
        }

        $recipe = $this->recipeRepository->findById($id);

        if (!$recipe) {
            $this->redirect('/recipes');
            return;
        }

        return $this->render('cooking', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * Handle POST /creator — process recipe creation form
     */
    private function handleCreateRecipe()
    {
        $userId = $_SESSION['user_id'];

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'dinner';
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $prepTime = (int)($_POST['prep_time'] ?? 0);
        $cookTime = (int)($_POST['cook_time'] ?? 0);
        $servings = (int)($_POST['servings'] ?? 1);

        // Validation
        if (empty($title)) {
            $tags = $this->recipeRepository->getAllTags();
            return $this->render('creator', [
                'error' => 'Recipe title is required.',
                'tags' => $tags,
            ]);
        }

        // Create recipe
        $recipe = new Recipe(
            $userId,
            $title,
            $description,
            $category,
            $difficulty,
            $prepTime,
            $cookTime,
            $servings
        );
        $recipe->setModerationStatus('approved'); // auto-approve for now

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/img/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('recipe_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $recipe->setImage('/public/img/uploads/' . $filename);
        }

        $recipe = $this->recipeRepository->save($recipe);
        $recipeId = $recipe->getId();

        // Save ingredients
        $ingredientNames = $_POST['ingredient_name'] ?? [];
        $ingredientAmounts = $_POST['ingredient_amount'] ?? [];
        $ingredientUnits = $_POST['ingredient_unit'] ?? [];

        $ingredients = [];
        for ($i = 0; $i < count($ingredientNames); $i++) {
            if (!empty(trim($ingredientNames[$i]))) {
                $ingredients[] = [
                    'name' => trim($ingredientNames[$i]),
                    'amount' => trim($ingredientAmounts[$i] ?? ''),
                    'unit' => trim($ingredientUnits[$i] ?? ''),
                ];
            }
        }
        if (!empty($ingredients)) {
            $this->recipeRepository->saveIngredients($recipeId, $ingredients);
        }

        // Save cooking steps
        $stepTitles = $_POST['step_title'] ?? [];
        $stepInstructions = $_POST['step_instructions'] ?? [];
        $stepDurations = $_POST['step_duration'] ?? [];

        $steps = [];
        for ($i = 0; $i < count($stepInstructions); $i++) {
            if (!empty(trim($stepInstructions[$i]))) {
                $steps[] = [
                    'title' => trim($stepTitles[$i] ?? ''),
                    'instructions' => trim($stepInstructions[$i]),
                    'duration' => (int)($stepDurations[$i] ?? 0),
                ];
            }
        }
        if (!empty($steps)) {
            $this->recipeRepository->saveSteps($recipeId, $steps);
        }

        // Save nutrition
        $calories = (int)($_POST['calories'] ?? 0);
        if ($calories > 0) {
            $this->recipeRepository->saveNutrition($recipeId, [
                'calories' => $calories,
                'protein' => (float)($_POST['protein'] ?? 0),
                'carbs' => (float)($_POST['carbs'] ?? 0),
                'fats' => (float)($_POST['fats'] ?? 0),
                'fiber' => (float)($_POST['fiber'] ?? 0),
                'sugar' => (float)($_POST['sugar'] ?? 0),
            ]);
        }

        // Save tags
        $tagIds = $_POST['tags'] ?? [];
        if (!empty($tagIds)) {
            $this->recipeRepository->saveTags($recipeId, array_map('intval', $tagIds));
        }

        $this->redirect('/recipe?id=' . $recipeId);
    }
}
