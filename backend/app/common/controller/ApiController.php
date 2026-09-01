<?php
declare (strict_types=1);

namespace app\common\controller;

use app\BaseController;
use app\common\traits\ApiResponse;
use think\App;
use think\Request;

/**
 * API 控制器基类
 *
 * 统一附加跨域头，注入 request_id，并规范控制器中可用的公共方法。
 */
abstract class ApiController extends BaseController
{
    use ApiResponse;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 控制器内统一获取 JWT 当前成员
     */
    protected function currentMember(): ?\app\common\model\Member
    {
        return request()->member ?? null;
    }

    /**
     * 断言当前请求来自已认证成员
     */
    protected function requireMember(): \app\common\model\Member
    {
        $member = $this->currentMember();
        if (!$member) {
            throw new \think\exception\HttpResponseException(
                $this->error('UNAUTHENTICATED', '请先登录', 401)
            );
        }
        return $member;
    }
}
