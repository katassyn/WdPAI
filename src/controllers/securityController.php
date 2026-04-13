<?php
require_once "appController.php";
require_once __DIR__ . '/../repository/UserRepository.php';

class securityController extends appController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        if ($this->isGet()) {
            return $this->render('login');
        }

        // POST — login
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['error' => 'Please fill in all fields.']);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->render('login', ['error' => 'Invalid email or password.']);
        }

        if ($user->getStatus() === 'suspended') {
            return $this->render('login', ['error' => 'Your account has been suspended.']);
        }

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['user_name'] = $user->getUsername();

        $this->redirect('/dashboard');
    }

    public function register()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        if ($this->isGet()) {
            return $this->render('login');
        }

        // POST — register
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return $this->render('login', ['error' => 'Please fill in all fields.', 'tab' => 'signup']);
        }

        if (strlen($password) < 8) {
            return $this->render('login', ['error' => 'Password must be at least 8 characters.', 'tab' => 'signup']);
        }

        if ($this->userRepository->emailExists($email)) {
            return $this->render('login', ['error' => 'Email already in use.', 'tab' => 'signup']);
        }

        if ($this->userRepository->usernameExists($username)) {
            return $this->render('login', ['error' => 'Username already taken.', 'tab' => 'signup']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User($username, $email, $hashedPassword);
        $user = $this->userRepository->save($user);

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['user_name'] = $user->getUsername();

        $this->redirect('/dashboard');
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('/login');
    }

    public function forgotPassword()
    {
        return $this->render('forgot-password');
    }
}
