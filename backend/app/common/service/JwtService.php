<?php
declare (strict_types=1);

namespace app\common\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use think\facade\Db;

/**
 * JWT 认证服务
 *
 * 签发/校验 Access Token 与 Refresh Token。
 * Access Token 短期有效；Refresh Token 可撤销、可轮换，并记录设备会话。
 */
class JwtService
{
    private const CLAIM_TYPE_ACCESS = 'access';
    private const CLAIM_TYPE_REFRESH = 'refresh';

    private string $secret;
    private string $algorithm;
    private int $accessTtl;
    private int $refreshTtl;

    public function __construct()
    {
        $this->secret = (string) env('JWT_SECRET', 'ebase_dev_secret');
        $this->algorithm = (string) env('JWT_ALGORITHM', 'HS256');
        $this->accessTtl = (int) env('JWT_ACCESS_TTL', 7200);
        $this->refreshTtl = (int) env('JWT_REFRESH_TTL', 604800);
    }

    /**
     * 签发访问令牌
     */
    public function issueAccessToken(int $memberId, string $sessionId): string
    {
        $now = time();
        $payload = [
            'iss' => 'ebase',
            'sub' => $memberId,
            'sid' => $sessionId,
            'typ' => self::CLAIM_TYPE_ACCESS,
            'iat' => $now,
            'exp' => $now + $this->accessTtl,
            'jti' => bin2hex(random_bytes(16)),
        ];
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * 签发刷新令牌（以哈希形式存储）
     */
    public function issueRefreshToken(int $memberId, string $sessionId, string $device = ''): string
    {
        $now = time();
        $payload = [
            'iss' => 'ebase',
            'sub' => $memberId,
            'sid' => $sessionId,
            'typ' => self::CLAIM_TYPE_REFRESH,
            'iat' => $now,
            'exp' => $now + $this->refreshTtl,
            'jti' => bin2hex(random_bytes(16)),
        ];
        $token = JWT::encode($payload, $this->secret, $this->algorithm);

        // 显存哈希，不落明文；绑定设备会话
        $hash = hash('sha256', $token);
        $key = $this->refreshKey($sessionId);
        Cache::store('redis')->set($key, json_encode([
            'member_id' => $memberId,
            'device'    => $device,
            'hash'      => $hash,
        ]), $this->refreshTtl);

        return $token;
    }

    /**
     * 解析并校验 token（不区分类型）
     *
     * @return array payload
     * @throws \Throwable 签名无效/过期
     */
    public function decode(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->secret, $this->algorithm));
    }

    /**
     * 校验访问令牌，返回 payload
     */
    public function parseAccessToken(string $token): ?array
    {
        try {
            $payload = $this->decode($token);
            if (($payload['typ'] ?? '') !== self::CLAIM_TYPE_ACCESS) {
                return null;
            }
            // 校验会话是否仍有效（可撤销：登出后失效）
            $session = $this->getSession((string) $payload['sid']);
            if (!$session || (int) $session['member_id'] !== (int) $payload['sub']) {
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 校验刷新令牌，返回 payload
     */
    public function parseRefreshToken(string $token): ?array
    {
        try {
            $payload = $this->decode($token);
            if (($payload['typ'] ?? '') !== self::CLAIM_TYPE_REFRESH) {
                return null;
            }
            $hash = hash('sha256', $token);
            $stored = Cache::store('redis')->get($this->refreshKey((string) $payload['sid']));
            if (!$stored) {
                return null;
            }
            $stored = json_decode($stored, true);
            if (!hash_equals($stored['hash'], $hash)) {
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 撤销刷新令牌（登出/轮换时）
     */
    public function revokeRefreshToken(string $sessionId): void
    {
        Cache::store('redis')->delete($this->refreshKey($sessionId));
    }

    /**
     * 撤销会话（登出/注销设备）
     */
    public function revokeSession(string $sessionId): void
    {
        Db::name('member_sessions')->where('session_id', $sessionId)->update([
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
        $this->revokeRefreshToken($sessionId);
    }

    /**
     * 校验会话是否有效
     */
    protected function getSession(string $sessionId): ?array
    {
        $row = Db::name('member_sessions')
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->find();
        return $row ?: null;
    }

    protected function refreshKey(string $sessionId): string
    {
        return 'auth:session:' . $sessionId;
    }
}
