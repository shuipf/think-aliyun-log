<?php
// +----------------------------------------------------------------------
// | WebTracking https://help.aliyun.com/document_detail/31752.html
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace aliyun\log;

use Swlib\SaberGM;
use think\App;
use think\contract\LogHandlerInterface;
use Throwable;
use think\facade\Env;
use think\facade\Request;

class WebTracking implements LogHandlerInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'project' => 'run',
        //公网域名
        'host' => 'cn-hangzhou.log.aliyuncs.com',
        //内网域名
        'hostinside' => 'cn-hangzhou-intranet.log.aliyuncs.com',
        //${project}下面开通Web Tracking功能的某一个Logstore的名称
        'logstore' => 'think6',
    ];

    /**
     * 实例化并传入参数
     * WebTracking constructor.
     * @param App $app
     * @param array $config
     */
    public function __construct(App $app, $config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log): bool
    {
        $message = [];
        //日志信息封装
        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $dateYmd = date('Y-m-d', $time);
        $dateH = date('H', $time);
        $baseData = [
            'url' => Request::url(true),
            'host' => Request::host(),
            'ip' => Request::ip(),
            'method' => Request::method(),
            'referer' => Request::getReferer(),
            'useragent' => Request::server('HTTP_USER_AGENT'),
            'appname' => Request::app(),
            'controller' => Request::controller(),
            'action' => Request::action(),
        ];
        foreach ($log as $type => $val) {
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }
                $msg = [
                    //日志时间
                    'time' => $time,
                    'date' => $date,
                    'dateymd' => $dateYmd,
                    'dateh' => $dateH,
                    //日志类型
                    'type' => $type,
                    //日志内容
                    'msg' => $msg,
                ];
                $message[] = array_merge($msg, $baseData);
            }
        }
        if ($message) {
            return $this->logReport($message);
        }
        return true;
    }

    /**
     * 日志上报
     * @param array $message
     * @return bool
     */
    protected function logReport(array $message): bool
    {
        $host = Env::get('app_debug') ? $this->config['host'] : $this->config['hostinside'];
        $url = "http://{$this->config['project']}.{$host}/logstores/{$this->config['logstore']}/track?APIVersion=0.6.0";
        foreach ($message as $msg) {
            $noticeUrl = $url . '&' . http_build_query($msg);
            go(
                function () use ($noticeUrl) {
                    try {
                        SaberGM::get($noticeUrl);
                    } catch (Throwable $e) {

                    }
                }
            );
        }
        return true;
    }

}
