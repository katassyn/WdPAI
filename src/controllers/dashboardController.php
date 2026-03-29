<?php
require_once "appController.php";
class dashboardController extends appController{
    public function index(){
        $title = "WDPAI - DASHBOARD";
        return $this->render('dashboard',["title" => $title]);
    }

    public function test(){
        echo "test";
    }
}