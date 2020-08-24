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

use think\App;
use think\contract\LogHandlerInterface;
use think\facade\Env;
use think\facade\Request;

class WebTracking implements LogHandlerInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        //公网域名
        'host' => 'cn-hangzhou.log.aliyuncs.com',
        //内网域名
        'hostinside' => 'cn-hangzhou-intranet.log.aliyuncs.com',
        //项目
        'project' => '',
        //${project}下面开通Web Tracking功能的某一个Logstore的名称
        'logstore' => '',
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
     * @param array $log 日志信息
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
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
                    $msg = print_r($msg, true);
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function logReport(array $message): bool
    {
        $host = Env::get('app_debug') ? $this->config['host'] : $this->config['hostinside'];
        $url = "http://{$this->config['project']}.{$host}/logstores/{$this->config['logstore']}/track?APIVersion=0.6.0";
        foreach ($message as $msg) {
            $noticeUrl = $url . '&' . http_build_query($msg);
            $this->sendRequest($noticeUrl);
        }
        return true;
    }

    /**
     * 请求
     * @param string $noticeUrl
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest(string $noticeUrl)
    {
        $this->sendGet($noticeUrl);
    }

    /**
     * 发送请求
     * @param string $noticeUrl 请求地址
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendGet(string $noticeUrl)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $client->request('GET', $noticeUrl);
        } catch (\Throwable $e) {
            //再尝试一次
            $client->request('GET', $noticeUrl);
        }
    }
}
