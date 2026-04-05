<?php
require_once "appController.php";

class AdminController extends appController
{
    public function users()
    {
        return $this->render('admin/users');
    }

    public function moderation()
    {
        return $this->render('admin/moderation');
    }
}
