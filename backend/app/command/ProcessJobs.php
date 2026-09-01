<?php
declare(strict_types=1);

namespace app\command;

use app\common\service\JobOutboxService;
use app\common\service\OrderService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/** Processes one or more due reliable jobs. Run repeatedly from a supervisor. */
final class ProcessJobs extends Command
{
    protected function configure(): void
    {
        $this->setName('jobs:process')
            ->setDescription('处理可靠任务队列中的待执行任务');
    }

    protected function execute(Input $input, Output $output): int
    {
        $outbox = new JobOutboxService();
        $processed = 0;

        while ($job = $outbox->claimDueJob()) {
            try {
                $this->dispatch($job);
                $outbox->complete((int) $job['id']);
                $processed++;
                $output->writeln("完成任务 #{$job['id']} {$job['job_type']}");
            } catch (\Throwable $exception) {
                $outbox->fail($job, $exception);
                $output->error("任务 #{$job['id']} 失败：{$exception->getMessage()}");
            }
        }

        $output->writeln("本次共处理 {$processed} 个任务");
        return 0;
    }

    private function trackLogistics(array $job): void
    {
        $package = \think\facade\Db::name('shipment_packages')->where('id', $job['payload']['package_id'])->find();
        if (!$package) return;
        $events = (new \app\common\service\logistics\Kuaidi100Service())->track($package['tracking_no'], $package['carrier_code']);
        foreach (($events['data'] ?? []) as $event) {
            (new \app\common\service\FulfillmentService())->addTrackingEvent((int)$package['id'], [
                'external_event_id' => hash('sha256', json_encode($event)),
                'event_status' => $event['status'] ?? 'in_transit',
                'description' => $event['context'] ?? '物流状态更新',
                'occurred_at' => $event['ftime'] ?? date('Y-m-d H:i:s'),
                'raw_payload' => $event,
            ]);
        }
    }

    private function dispatch(array $job): void
    {
        match ($job['job_type']) {
            'order.cancel_timeout' => (new OrderService())->cancelExpired((int) $job['payload']['order_id']),
            'logistics.track' => $this->trackLogistics($job),
            default => throw new \RuntimeException("未知任务类型：{$job['job_type']}"),
        };
    }
}
