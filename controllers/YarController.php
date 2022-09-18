<?php

namespace app\controllers;

use app\common\rpc\YarServer;
use Yii;
use yii\web\Controller;
use app\common\rpc\YarApi;

class YarController extends Controller
{
    /**
     * 关闭csrf
     * @var bool
     */
    public $enableCsrfValidation = false;

    public function actionRpcCall()
    {
        $condition = ['class' => '\app\models\User'];
        $yar       = new YarApi();
        $client    = $yar->api($condition);
        $response  = [
            'code' => 200,
            'msg'  => 'success',
            'data' => $client->selAll()
        ];
        return $this->asJson($response);
    }

    public function actionRpcServer()
    {
        $params    = Yii::$app->request->get();
        $yarServer = new YarServer();
        $response  = $yarServer->server($params);
        return $this->asJson($response);
    }
}