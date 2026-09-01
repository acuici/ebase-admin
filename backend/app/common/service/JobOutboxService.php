<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * MySQL-backed reliable job outbox.
 *
 * A unique business job_key prevents duplicate scheduling. Workers claim jobs
 * under row locks, retry with exponential backoff, and retain terminal errors.
 */
final class JobOutboxService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    public function schedule(
        string $jobKey,
        string $jobType,
        array $payload,
        \DateTimeInterface $availableAt,
        int $maxAttempts = 5,
    ): void {
        $now = date('Y-m-d H:i:s');

        Db::name('job_outbox')->insert([
            'job_key' => $jobKey,
            'job_type' => $jobType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => $availableAt->format('Y-m-d H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ], true);
    }

    /**
     * Atomically claim one due job. Returns null when the queue is empty.
     */
    public function claimDueJob(): ?array
    {
        return Db::transaction(function (): ?array {
            $now = date('Y-m-d H:i:s');
            $job = Db::name('job_outbox')
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
                ->where('available_at', '<=', $now)
                ->order('id', 'asc')
                ->lock(true)
                ->find();

            if (!$job) {
                return null;
            }

            Db::name('job_outbox')
                ->where('id', $job['id'])
                ->update([
                    'status' => self::STATUS_PROCESSING,
                    'attempts' => (int) $job['attempts'] + 1,
                    'locked_at' => $now,
                    'updated_at' => $now,
                ]);

            $job['attempts'] = (int) $job['attempts'] + 1;
            $job['payload'] = json_decode((string) $job['payload'], true, 512, JSON_THROW_ON_ERROR);

            return $job;
        });
    }

    public function complete(int $jobId): void
    {
        Db::name('job_outbox')
            ->where('id', $jobId)
            ->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function fail(array $job, \Throwable $exception): void
    {
        $attempts = (int) $job['attempts'];
        $maxAttempts = (int) $job['max_attempts'];
        $terminal = $attempts >= $maxAttempts;
        $delay = min(3600, 30 * (2 ** max(0, $attempts - 1)));

        Db::name('job_outbox')
            ->where('id', $job['id'])
            ->update([
                'status' => $terminal ? self::STATUS_DEAD : self::STATUS_FAILED,
                'available_at' => date('Y-m-d H:i:s', time() + $delay),
                'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
