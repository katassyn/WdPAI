<?php
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/RecipeController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/SettingsController.php';
require_once 'src/controllers/ApiController.php';

class Routing {

    public static $routes = [
        "login" => [
            "controller" => "securityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "securityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "securityController",
            "action" => "logout"
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
        "settings/profile" => [
            "controller" => "SettingsController",
            "action" => "saveProfile"
        ],
        "settings/goals" => [
            "controller" => "SettingsController",
            "action" => "saveGoals"
        ],
        "settings/password" => [
            "controller" => "SettingsController",
            "action" => "savePassword"
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
        "api/favorite" => [
            "controller" => "ApiController",
            "action" => "favorite"
        ],
        "api/search" => [
            "controller" => "ApiController",
            "action" => "search"
        ],
        "api/tracking" => [
            "controller" => "ApiController",
            "action" => "tracking"
        ],
        "api/tracking/today" => [
            "controller" => "ApiController",
            "action" => "getTracking"
        ],
        "api/log-meal" => [
            "controller" => "ApiController",
            "action" => "logMeal"
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
