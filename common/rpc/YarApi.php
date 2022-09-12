<?php

namespace app\common\rpc;

/**
 * Yar rpc client
 */
class YarApi
{
    /**
     * 密码
     * @var string
     */
    private $password = 'Add25f37';

    public function api(array $condition)
    {
        $defult    = [
            'url'   => 'http://backend.test:8084/rpc/index', //服务器URL
            'class' => '', //class名称
        ];
        $condition = array_merge($defult, $condition);

        $data             = [];
        $data['time']     = time();
        $data['password'] = $this->password;
        $data['class']    = $condition['class'];

        return new \Yar_Client("{$condition['url']}?rpctoken={$this->rpcEncode($data)}");
    }

    /**
     * 加密
     *
     * @param array $data
     * @return string
     */
    private function rpcEncode(array $data)
    {
        return base64_encode(json_encode($data));
    }
}
