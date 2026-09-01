<?php
declare (strict_types=1);
namespace app\common\contract;
interface NotificationServiceInterface
{
    public function sendSms(string $phone, string $template, array $params = []): string;
    public function sendEmail(string $email, string $subject, string $html): string;
    public function sendInApp(int $memberId, string $title, string $content): string;
}
