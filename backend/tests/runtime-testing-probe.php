<?php
declare(strict_types=1);

use app\common\model\Member;
use app\common\service\JwtService;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;

$root = '/Users/panacea/Desktop/ebase/backend';
putenv('APP_ENV=testing');
$testingEnv = parse_ini_file($root . '/.env.testing', false, INI_SCANNER_RAW) ?: [];
foreach ($testingEnv as $key => $value) {
    putenv($key . '=' . $value);
}
require $root . '/vendor/autoload.php';
$app = new think\App($root);
$app->setEnvName('testing');
$app->initialize();

$testingDatabase = require $root . '/config/testing/database.php';
think\facade\Config::set(['database' => $testingDatabase]);

function out(string $stage, string $status, array $extra = []): void
{
    echo json_encode(array_merge(['stage' => $stage, 'status' => $status], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
function fail(string $stage, Throwable $e): void
{
    out($stage, 'FAIL', ['exception' => $e::class, 'file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()]);
}

out('bootstrap', 'OK', ['environment' => 'testing', 'entry' => 'testing CLI probe; testing database config applied after App::initialize']);
try {
    $config = Config::get('database.connections.mysql');
    out('config.mysql', 'OK', ['host' => $config['hostname'] ?? null, 'port' => $config['hostport'] ?? null, 'database' => $config['database'] ?? null, 'user' => $config['username'] ?? null]);
} catch (Throwable $e) { fail('config.mysql', $e); }
try {
    $config = Config::get('cache.stores.redis');
    out('config.redis', 'OK', ['host' => $config['host'] ?? null, 'port' => $config['port'] ?? null, 'select' => $config['select'] ?? null, 'password_present' => ($config['password'] ?? '') !== '']);
} catch (Throwable $e) { fail('config.redis', $e); }
try {
    out('config.jwt', 'OK', ['algorithm' => env('JWT_ALGORITHM', 'HS256'), 'access_ttl' => (int) env('JWT_ACCESS_TTL', 7200), 'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800), 'secret_present' => (string) env('JWT_SECRET', '') !== '']);
} catch (Throwable $e) { fail('config.jwt', $e); }
try {
    $row = Db::query('SELECT DATABASE() AS database_name, @@hostname AS db_hostname, @@port AS db_port')[0];
    out('mysql.query', 'OK', $row);
} catch (Throwable $e) { fail('mysql.query', $e); }
try {
    $redis = Cache::store('redis');
    $key = 'ebase:testing:probe:config:' . bin2hex(random_bytes(6));
    $redis->set($key, 'ok', 30);
    $value = $redis->get($key);
    $redis->delete($key);
    out('redis.probe', $value === 'ok' ? 'OK' : 'FAIL', ['readback' => $value, 'ttl_seconds' => 30]);
} catch (Throwable $e) { fail('redis.probe', $e); }
try {
    $jwt = new JwtService();
    $token = $jwt->issueAccessToken(1, 'probe-session');
    $payload = $jwt->decode($token);
    out('jwt.probe', 'OK', ['claims' => array_intersect_key($payload, array_flip(['iss', 'sub', 'sid', 'typ', 'iat', 'exp']))]);
} catch (Throwable $e) { fail('jwt.probe', $e); }

$member = null;
try {
    $member = Member::order('id', 'asc')->find();
    if (!$member) throw new RuntimeException('no member found');
    out('login.member_query', 'OK', ['member_id' => (int) $member->id, 'email_present' => ((string) $member->email) !== '']);
} catch (Throwable $e) { fail('login.member_query', $e); }
if ($member) {
    try { $ok = $member->verifyPassword('__testing_invalid_password__'); out('login.password_verify', $ok ? 'UNEXPECTED' : 'OK', ['match' => $ok]); } catch (Throwable $e) { fail('login.password_verify', $e); }
    try { $roles = $member->roles()->select(); out('login.roles', 'OK', ['count' => count($roles)]); } catch (Throwable $e) { fail('login.roles', $e); }
    try {
        $redis = Cache::store('redis');
        $key = 'ebase:testing:auth:session:probe-' . bin2hex(random_bytes(4));
        $redis->set($key, json_encode(['member_id' => (int) $member->id]), 30);
        $record = $redis->get($key);
        $redis->delete($key);
        out('login.redis_session', $record ? 'OK' : 'FAIL', ['readback_present' => $record !== false && $record !== null]);
    } catch (Throwable $e) { fail('login.redis_session', $e); }
    try {
        $jwt = new JwtService();
        $token = $jwt->issueAccessToken((int) $member->id, 'probe-login-session');
        $payload = $jwt->decode($token);
        out('login.jwt_issue', 'OK', ['subject' => $payload['sub'] ?? null, 'type' => $payload['typ'] ?? null]);
    } catch (Throwable $e) { fail('login.jwt_issue', $e); }
}
