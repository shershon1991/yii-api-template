<?php

namespace app\controllers;

use yii\web\Controller;
use app\common\rpc\YarApi;

class YarController extends Controller
{
    public function actionRpcCall()
    {
        $condition = ['class' => '\backend\models\Per'];
        $yar       = new YarApi();
        $client    = $yar->api($condition);
        $response  = [
            'code' => 200,
            'msg'  => 'success',
            'data' => $client->SelAll()
        ];
        return $this->asJson($response);
    }
}