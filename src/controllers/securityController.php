<?php
require_once "appController.php";
class securityController extends appController{
    public function login(){
        return $this->render('login');
    }

    public function register(){
        return $this->render('register');
    }

    public function logout(){
        return $this->render('logout');
    }

    public function forgotPassword(){
        return $this->render('forgot-password');
    }
}