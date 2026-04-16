<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/UserRepository.php';

class SettingsController extends appController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function index()
    {
        $this->requireAuth();

        $user = $this->userRepository->findById($_SESSION['user_id']);

        return $this->render('settings', [
            'user' => $user,
            'success' => $_GET['saved'] ?? null,
            'error' => null,
            'tab' => $_GET['tab'] ?? 'profile',
        ]);
    }

    /**
     * POST /settings/profile — update username, email, bio
     */
    public function saveProfile()
    {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->findById($userId);

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        // Validation
        if (empty($username) || empty($email)) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'Username and email are required.',
                'tab' => 'profile',
                'success' => null,
            ]);
        }

        // Check uniqueness (exclude self)
        if ($email !== $user->getEmail() && $this->userRepository->emailExists($email)) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'This email is already taken.',
                'tab' => 'profile',
                'success' => null,
            ]);
        }

        if ($username !== $user->getUsername() && $this->userRepository->usernameExists($username)) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'This username is already taken.',
                'tab' => 'profile',
                'success' => null,
            ]);
        }

        $this->userRepository->updateProfile($userId, $username, $email, $bio);

        // Update session name
        $_SESSION['user_name'] = $username;

        $this->redirect('/settings?tab=profile&saved=profile');
    }

    /**
     * POST /settings/goals — update daily macro goals
     */
    public function saveGoals()
    {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];

        $calories = (int)($_POST['daily_calories'] ?? 2400);
        $protein = (int)($_POST['daily_protein'] ?? 160);
        $carbs = (int)($_POST['daily_carbs'] ?? 250);
        $fats = (int)($_POST['daily_fats'] ?? 70);

        $this->userRepository->updateGoals($userId, $calories, $protein, $carbs, $fats);

        $this->redirect('/settings?tab=goals&saved=goals');
    }

    /**
     * POST /settings/password — change password
     */
    public function savePassword()
    {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->findById($userId);

        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user->getPassword())) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'Current password is incorrect.',
                'tab' => 'account',
                'success' => null,
            ]);
        }

        if (strlen($newPass) < 8) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'New password must be at least 8 characters.',
                'tab' => 'account',
                'success' => null,
            ]);
        }

        if ($newPass !== $confirm) {
            return $this->render('settings', [
                'user' => $user,
                'error' => 'Passwords do not match.',
                'tab' => 'account',
                'success' => null,
            ]);
        }

        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $this->userRepository->updatePassword($userId, $hashed);

        $this->redirect('/settings?tab=account&saved=password');
    }
}
