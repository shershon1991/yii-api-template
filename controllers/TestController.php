<?php

namespace app\controllers;

use yii\web\Controller;
use app\common\rpc\YarApi;

class TestController extends Controller
{
    public function actionIndex()
    {
        $condition = ['class' => '\backend\models\Per'];
        $yar       = new YarApi();
        $client    = $yar->api($condition);
        var_dump($client->some_method("test"));
    }
}