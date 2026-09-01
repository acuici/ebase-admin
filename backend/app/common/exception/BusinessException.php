<?php
declare (strict_types=1);

namespace app\common\exception;

use RuntimeException;

/**
 * 业务异常
 *
 * 在服务层/领域层抛出，由全局异常处理器统一转换成标准 API 错误响应。
 * code 为稳定机器码，HTTP 状态码与业务语义一致。
 */
class BusinessException extends RuntimeException
{
    protected string $errorCode;
    protected int $httpStatus;
    protected array $errors;

    public function __construct(
        string $errorCode = 'INTERNAL_ERROR',
        string $message = '业务处理失败',
        int $httpStatus = 400,
        array $errors = []
    ) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->errors = $errors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 便捷构造：请求参数不合法
     */
    public static function validationError(array $errors, string $message = '请求参数不合法'): self
    {
        return new self('VALIDATION_ERROR', $message, 422, $errors);
    }

    /**
     * 便捷构造：资源不存在
     */
    public static function notFound(string $message = '资源不存在'): self
    {
        return new self('RESOURCE_NOT_FOUND', $message, 404);
    }

    /**
     * 便捷构造：未认证
     */
    public static function unauthenticated(string $message = '请先登录'): self
    {
        return new self('UNAUTHENTICATED', $message, 401);
    }

    /**
     * 便捷构造：无权限
     */
    public static function forbidden(string $message = '无权访问'): self
    {
        return new self('FORBIDDEN', $message, 403);
    }

    /**
     * 便捷构造：库存不足
     */
    public static function insufficientInventory(string $message = '库存不足'): self
    {
        return new self('INVENTORY_INSUFFICIENT', $message, 409);
    }
}
