<?php
require_once "appController.php";

class RecipeController extends appController
{
    public function recipes()
    {
        return $this->render('recipes');
    }

    public function cooking()
    {
        return $this->render('cooking');
    }

    public function creator()
    {
        return $this->render('creator');
    }

    public function detail()
    {
        return $this->render('recipe');
    }
}
