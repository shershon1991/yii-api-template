<?php

namespace app\common\base;

use yii\elasticsearch7\ActiveRecord;

abstract class ElasticsearchBase extends ActiveRecord
{

    /**
     * 定义db链接
     *
     * @return object|\yii\elasticsearch7\Connection|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return \Yii::$app->get('elasticsearch7');
    }

    /**
     * 分片设置
     *
     * @return array
     */
    public static function indexShards()
    {
        return [
            "settings" => [
                "number_of_shards"   => 2,  //2主分片
                "number_of_replicas" => 1 //1副本
            ]
        ];
    }

    public abstract static function mapConfig();

    public function attributes()
    {
        $mapConfig = static::mapConfig();
        return array_keys($mapConfig['properties']);
    }

    /**
     * 创建索引
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function createIndex()
    {
        $db      = static::getDb();
        $command = $db->createCommand();
        return $command->createIndex(static::index(), static::indexShards());
    }

    /**
     * 删除索引
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function deleteIndex()
    {
        $db      = static::getDb();
        $command = $db->createCommand();
        return $command->deleteIndex(static::index());
    }

    /**
     * 添加配置的映射
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public static function createMapping()
    {
        $db      = static::getDb();
        $command = $db->createCommand();
        if (!$command->indexExists(static::index())) {
            $command->createIndex(static::index(), static::indexShards());
        }
        $command->setMapping(static::index(), static::type(), static::mapConfig());
        return true;
    }

    /**
     * 更新配置的映射
     *
     * @param $properties
     * @return mixed|void
     * @throws \yii\base\InvalidConfigException
     */
    public static function updateMapping($properties)
    {
        if (empty($properties) || !is_array($properties)) {
            return;
        }
        $db      = static::getDb();
        $command = $db->createCommand();
        if (!$command->indexExists(static::index())) {
            $command->createIndex(static::index(), static::indexShards());
        }
        return $command->setMapping(static::index(), static::type(), $properties);
    }

    /**
     * 获取映射
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function getMapping()
    {
        $db      = static::getDb();
        $command = $db->createCommand();
        return $command->getMapping(static::index());
    }
}