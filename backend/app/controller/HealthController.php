<?php
declare(strict_types=1);

namespace app\controller;

use app\common\controller\ApiController;
use think\facade\Db;
use think\Response;

final class HealthController extends ApiController
{
    public function db(): Response
    {
        if (getenv('APP_ENV') !== 'testing') {
            throw \app\common\exception\BusinessException::notFound('资源不存在');
        }

        $row = Db::query('SELECT DATABASE() AS database_name, @@hostname AS db_hostname, @@port AS db_port')[0];

        return $this->success([
            'environment' => 'testing',
            'database' => $row['database_name'],
            'host' => $row['db_hostname'],
            'port' => (int) $row['db_port'],
        ]);
    }
}
