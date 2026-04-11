<?php
require_once "appController.php";

class SettingsController extends appController
{
    public function index()
    {
        return $this->render('settings');
    }
}
