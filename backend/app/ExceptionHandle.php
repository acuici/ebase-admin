<?php
declare (strict_types=1);

namespace app;

use app\common\exception\BusinessException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 *
 * 所有 API 异常统一转换为标准响应结构：
 * { code, message, errors, request_id }
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        BusinessException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     */
    public function report(Throwable $exception): void
    {
        // 业务异常与可预期异常不需要记录错误日志
        if ($exception instanceof BusinessException) {
            return;
        }
        parent::report($exception);
    }

    /**
     * 将异常渲染为 HTTP 响应
     */
    public function render($request, Throwable $e): Response
    {
        // 业务异常：直接转换为标准错误响应
        if ($e instanceof BusinessException) {
            return $this->errorResponse(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getHttpStatus(),
                $e->getErrors()
            );
        }

        // 参数校验异常
        if ($e instanceof ValidateException) {
            $validationErrors = $e->getError();
            return $this->errorResponse(
                'VALIDATION_ERROR',
                $e->getMessage(),
                422,
                is_array($validationErrors) ? $validationErrors : ['_error' => [$validationErrors]],
            );
        }

        // 未认证
        if ($e instanceof HttpException && $e->getStatusCode() === 401) {
            return $this->errorResponse('UNAUTHENTICATED', '请先登录', 401);
        }

        // 404
        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return $this->errorResponse('RESOURCE_NOT_FOUND', '接口不存在', 404);
        }

        // 资源不存在
        if ($e instanceof ModelNotFoundException || $e instanceof DataNotFoundException) {
            return $this->errorResponse('RESOURCE_NOT_FOUND', '资源不存在', 404);
        }

        // 调试模式显示原始错误，否则输出内部错误
        if (env('APP_DEBUG')) {
            return $this->errorResponse(
                'INTERNAL_ERROR',
                $e->getMessage(),
                500
            );
        }

        return $this->errorResponse('INTERNAL_ERROR', '服务器内部错误', 500);
    }

    /**
     * 构造标准错误响应
     */
    protected function errorResponse(
        string $code,
        string $message,
        int $status,
        array $errors = []
    ): Response {
        $body = [
            'code'       => $code,
            'message'    => $message,
            'errors'     => $errors ?: null,
            'request_id' => strtoupper(bin2hex(random_bytes(8))),
        ];
        return json($body, $status);
    }
}
