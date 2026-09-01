<?php
declare(strict_types=1);

namespace app\common\service\logistics;

use app\common\contract\LogisticsServiceInterface;
use app\common\exception\BusinessException;
use GuzzleHttp\Client;

/** 快递100 subscription adapter; credentials are read only from environment. */
final class Kuaidi100Service implements LogisticsServiceInterface
{
    public function __construct(private readonly Client $client = new Client()) {}

    public function createShipment(string $orderNo, array $recipient, array $items): array
    {
        throw new BusinessException('UPSTREAM_UNAVAILABLE', '快递100电子面单需配置月结账号后启用', 503);
    }

    public function cancelShipment(string $shipmentNo): void
    {
        throw new BusinessException('UPSTREAM_UNAVAILABLE', '快递100取消面单尚未配置', 503);
    }

    public function track(string $trackingNo, ?string $carrier = null): array
    {
        if (!$this->isConfigured()) throw new BusinessException('UPSTREAM_UNAVAILABLE', '快递100查询尚未配置', 503);
        $response = $this->client->post((string)env('KUAIDI100_QUERY_URL', 'https://poll.kuaidi100.com/poll/query.do'), ['form_params' => ['customer' => env('KUAIDI100_CUSTOMER'), 'sign' => md5($trackingNo . env('KUAIDI100_KEY')), 'param' => json_encode(['com' => $carrier, 'num' => $trackingNo])]]);
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function subscribeTracking(string $trackingNo, string $callbackUrl): void
    {
        throw new BusinessException('UPSTREAM_UNAVAILABLE', '快递100订阅需配置回调密钥后启用', 503);
    }

    private function isConfigured(): bool { return (string)env('KUAIDI100_CUSTOMER','') !== '' && (string)env('KUAIDI100_KEY','') !== ''; }
}
