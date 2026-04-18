<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/RecipeRepository.php';

class AdminController extends appController
{
    private UserRepository $userRepository;
    private RecipeRepository $recipeRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->recipeRepository = new RecipeRepository();
    }

    public function users()
    {
        $this->requireAdmin();

        $activeUsers = $this->userRepository->findByStatus('active');
        $pendingUsers = $this->userRepository->findByStatus('pending');
        $suspendedUsers = $this->userRepository->findByStatus('suspended');

        return $this->render('admin/users', [
            'activeUsers' => $activeUsers,
            'pendingUsers' => $pendingUsers,
            'suspendedUsers' => $suspendedUsers,
            'activeCount' => count($activeUsers),
            'pendingCount' => count($pendingUsers),
            'suspendedCount' => count($suspendedUsers),
            'currentUserId' => (int)$_SESSION['user_id'],
        ]);
    }

    public function moderation()
    {
        $this->requireAdmin();

        $pending = $this->recipeRepository->findByModerationStatus('pending');
        $approved = $this->recipeRepository->findByModerationStatus('approved');
        $rejected = $this->recipeRepository->findByModerationStatus('rejected');
        $flagged = $this->recipeRepository->findByModerationStatus('flagged');

        return $this->render('admin/moderation', [
            'pendingRecipes' => $pending,
            'approvedRecipes' => $approved,
            'rejectedRecipes' => $rejected,
            'flaggedRecipes' => $flagged,
            'pendingCount' => count($pending),
            'approvedCount' => count($approved),
            'rejectedCount' => count($rejected),
            'flaggedCount' => count($flagged),
        ]);
    }
}
