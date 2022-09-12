<?php

namespace app\models;

use app\common\base\ElasticsearchBase;

class TestEs extends ElasticsearchBase
{
    public static $currentIndex;

    public static function index()
    {
        return 'php-test';
    }

    public static function type()
    {
        return 'user';
    }

    public static function mapConfig()
    {
        return [
            'properties' => [
                'id'    => [
                    'type' => 'integer',
                ],
                'name'  => [
                    'type' => 'keyword',
                ],
                'age'   => [
                    'type' => 'integer',
                ],
                'birth' => [
                    'type' => 'date',
                ],
            ]
        ];
    }

    /**
     * 删除一条数据
     *
     * @param $id
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function deleteById($id)
    {
        $db      = self::getDb();
        $command = $db->createCommand();
        return $command->delete(self::index(), self::type(), $id);
    }

    /**
     * 获取列表
     *
     * @param $where
     * @return TestEs|array|bool|mixed|\yii\elasticsearch7\ActiveRecord|null
     */
    public static function getList($where)
    {
        return self::findOne($where)->toArray();
//        return self::find()->where(['id' => 1])->asArray()->one();
    }
}
