<?php

namespace app\controllers;

use app\models\TestEs;
use yii\web\Controller;

class EsController extends Controller
{
    // 创建索引
    public function actionCreateIndex()
    {
        var_dump(TestEs::createIndex());
    }

    // 删除索引
    public function actionDeleteIndex()
    {
        var_dump(TestEs::deleteIndex());
    }

    // 添加配置的映射
    public function actionCreateMap()
    {
        var_dump(TestEs::createMapping());
    }

    // 更新配置的映射
    public function actionUpdateMap()
    {
        var_dump(TestEs::updateMapping());
    }

    // 插入数据
    public function actionInsertData()
    {
        $model = new TestEs();

        // 单条插入
        $data1 = ['id' => 1, 'name' => '小谭', 'age' => 31, 'birth' => '1991-04-14'];
        $res1  = $model->saveOne($data1);
        var_dump($res1);

        // 批量插入
        $data2 = [
            ['id' => 2, 'name' => '小张', 'age' => 20, 'birth' => '1992-05-23'],
            ['id' => 3, 'name' => '小李', 'age' => 25, 'birth' => '1993-04-20'],
        ];
        $res2  = $model->bulk($data2);
        var_dump($res2);
    }

    // 删除数据
    public function actionDelData()
    {
        var_dump(TestEs::deleteById(1));
    }

    // 查询数据
    public function actionGetData()
    {
        $where = ['=', 'id', 1];
        var_dump(TestEs::getList($where));
    }

}
