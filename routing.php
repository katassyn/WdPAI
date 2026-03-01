<?php

class Routing {
    public static function run($path) {
        switch ($path) {
            case 'dashboard':
                include 'public/dashboard.html';
                break;
            case 'login':
                include 'public/login.html';
                break;
            default:
                include 'public/404.html';
                break;
        }
    }
}
