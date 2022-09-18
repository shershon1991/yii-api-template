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
    private $password = 'shershon-rpc-token';

    public function api(array $condition)
    {
        $defult    = [
            'url'   => 'http://47.108.49.196:8081/index.php?r=yar/rpc-server', //服务器URL
            'class' => '', //class名称
        ];
        $condition = array_merge($defult, $condition);

        $data             = [];
        $data['time']     = time();
        $data['password'] = $this->password;
        $data['class']    = $condition['class'];

        return new \Yar_Client("{$condition['url']}&rpc_token={$this->rpcEncode($data)}");
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
