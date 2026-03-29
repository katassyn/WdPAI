<?php
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
class Routing {

    public static $routes = [
        "login" => [
            "controller" => "securityController",
            "action" => "login"
        ],
        "dashboard" => [
            "controller" => "dashboardController",
            "action" => "index"
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
        // TODO sprawdzać za pomoca array_key_exists
        switch ($path) {
            case 'dashboard':
            case 'test':
            case '':
            case 'login':
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];

                $controllerObj = new $controller;
                $id = null;

                $controllerObj->$action($id);
                break;
            default:
                include 'public/404.html';
                break;
        }
    }

}
