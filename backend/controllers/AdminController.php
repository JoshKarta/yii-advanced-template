<?php

namespace backend\controllers;

class AdminController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionPermissions()
    {
        return $this->render('permissions');
    }

    public function actionScanRoutes()
    {
        return $this->render('scan-routes');
    }

}
