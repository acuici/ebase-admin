<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\Request;

/** Append-only audit records for member authentication/security actions. */
final class MemberAuthAuditService
{
    public function record(
        ?int $memberId,
        ?string $email,
        string $eventType,
        Request $request,
        array $metadata = [],
    ): void {
        Db::name('member_auth_logs')->insert([
            'member_id' => $memberId,
            'email' => $email,
            'event_type' => $eventType,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->header('User-Agent', ''), 0, 500),
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
