<?php
// +----------------------------------------------------------------------
// | ExceptionHandle 应用异常处理类
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace aliyun\log;

use think\exception\Handle;
use think\facade\Request;
use Throwable;

class ExceptionHandle extends Handle
{

    /**
     * Report or log an exception.
     * @param Throwable $exception
     */
    public function report(Throwable $exception): void
    {
        if (!$this->isIgnoreReport($exception)) {
            $data = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code' => $this->getCode($exception),
            ];
            $post = Request::post();
            $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            if (!empty($post)) {
                $log .= PHP_EOL . print_r($post, true);
            }
            if ($this->app->config->get('log.record_trace')) {
                $log .= PHP_EOL . $exception->getTraceAsString();
            }
            $this->app->log->record($log, 'error');
        }
    }
}