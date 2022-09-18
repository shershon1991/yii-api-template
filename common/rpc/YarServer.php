<?php

namespace app\common\rpc;

use Exception;
use Yii;

/**
 * YarServer
 */
class YarServer
{

    /**
     * ip
     * @var string[]
     */
    private $ipArr = ['172.24.0.1'];

    /**
     * 密码
     * @var string
     */
    private $password = 'shershon-rpc-token';

    /**
     * 有效时间
     * @var int
     */
    private $activeTime = 1440;

    public function server($params)
    {
        try {
            $rpcToken = $params['rpc_token'] ?? '';
            $data     = $this->rpcDecode($rpcToken);
            if (!$this->auth($data)) {
                throw new Exception('认证失败');
            }
            $server = new \Yar_Server(new $data['class']());
            $server->handle();
            return ['code' => 0, 'msg' => 'success', 'data' => []];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 权限认证
     *
     * @param $param
     * @return bool
     */
    public function auth($param)
    {
        if (!$param) {
            return false;
        }
        //验证IP
        if (!in_array(Yii::$app->request->userIP, $this->ipArr)) {
            return false;
        }
        //有效时间
        if ((time() - $param['time']) > $this->activeTime) {
            return false;
        }
        //验证密码
        if ($param['password'] !== $this->password) {
            return false;
        }
        if (empty($param['class'])) {
            return false;
        }
        return true;
    }

    /**
     * 解密
     *
     * @param $str
     * @return array|mixed
     */
    public function rpcDecode($str)
    {
        if ($str) {
            return json_decode(base64_decode($str), true);
        }
        return [];
    }
}
