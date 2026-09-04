<?php
declare (strict_types=1);

namespace app\common\traits;

use think\Response;

/**
 * 统一 API 响应 Trait
 *
 * 所有响应都使用 { code, message, data, request_id } 结构。
 * 业务错误不返回 200；code 是稳定机器码，前端不得依赖中文 message。
 */
trait ApiResponse
{
    /**
     * 生成 request_id（每个请求唯一，贯穿日志与响应）
     */
    protected function requestId(): string
    {
        return $this->request->requestId
            ?? $this->request->header('x-request-id', '')
            ?: strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * 成功响应
     */
    protected function success(mixed $data = null, string $message = 'success', int $status = 200): Response
    {
        return json([
            'code'       => 'OK',
            'message'    => $message,
            'data'       => $data,
            'request_id' => $this->requestId(),
        ], $status);
    }

    /**
     * 分页响应
     */
    protected function paginated(
        iterable $items,
        int      $page,
        int      $pageSize,
        int      $total,
        string   $message = 'success'
    ): Response {
        $pages = (int) ceil($total / max(1, $pageSize));

        return json([
            'code'       => 'OK',
            'message'    => $message,
            'data'       => [
                'items'      => $items,
                'pagination' => [
                    'page'      => $page,
                    'page_size' => $pageSize,
                    'total'     => $total,
                    'pages'     => $pages,
                ],
            ],
            'request_id' => $this->requestId(),
        ], 200);
    }

    /**
     * 业务失败响应（HTTP 状态与语义一致）
     */
    protected function error(
        string $code = 'INTERNAL_ERROR',
        string $message = '请求失败',
        int    $status = 400,
        array  $errors = []
    ): Response {
        $body = [
            'code'       => $code,
            'message'    => $message,
            'errors'     => $errors ?: null,
            'request_id' => $this->requestId(),
        ];

        return json($body, $status);
    }
}
