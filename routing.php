<?php
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/RecipeController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/SettingsController.php';

class Routing {

    public static $routes = [
        "login" => [
            "controller" => "securityController",
            "action" => "login"
        ],
        "forgot-password" => [
            "controller" => "securityController",
            "action" => "forgotPassword"
        ],
        "dashboard" => [
            "controller" => "dashboardController",
            "action" => "index"
        ],
        "recipes" => [
            "controller" => "RecipeController",
            "action" => "recipes"
        ],
        "creator" => [
            "controller" => "RecipeController",
            "action" => "creator"
        ],
        "cooking" => [
            "controller" => "RecipeController",
            "action" => "cooking"
        ],
        "recipe" => [
            "controller" => "RecipeController",
            "action" => "detail"
        ],
        "settings" => [
            "controller" => "SettingsController",
            "action" => "index"
        ],
        "admin/users" => [
            "controller" => "AdminController",
            "action" => "users"
        ],
        "admin/moderation" => [
            "controller" => "AdminController",
            "action" => "moderation"
        ],
        "test" => [
            "controller" => "dashboardController",
            "action" => "test"
        ],
        "" => [
            "controller" => "securityController",
            "action" => "login"
        ],
    ];

    public static function run(string $path)
    {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];

            $controllerObj = new $controller;
            $controllerObj->$action();
        } else {
            include 'public/404.html';
        }
    }
}
