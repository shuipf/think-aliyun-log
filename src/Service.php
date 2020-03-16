<?php
// +----------------------------------------------------------------------
// | Service 注册服务
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace aliyun\log;

class Service extends \think\Service
{

    /**
     * 注册
     * @return void
     */
    public function register()
    {
        $provider = [
            'think\exception\Handle' => ExceptionHandle::class,
        ];
        $this->app->bind($provider);
    }

}
