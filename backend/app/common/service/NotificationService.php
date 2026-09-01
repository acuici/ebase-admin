<?php
declare(strict_types=1);
namespace app\common\service;
use think\facade\Db;
final class NotificationService
{
    public function send(int $memberId,string $type,string $title,string $content,?string $path=null,array $payload=[]):void
    {
        Db::name('notifications')->insert(['member_id'=>$memberId,'notification_type'=>$type,'title'=>$title,'content'=>$content,'target_path'=>$path,'payload'=>$payload?json_encode($payload,JSON_UNESCAPED_UNICODE):null,'created_at'=>date('Y-m-d H:i:s')]);
    }
    public function broadcast(string $type,string $title,string $content,?string $path=null,array $payload=[]):void
    {
        $members=Db::name('members')->where('status',1)->column('id');
        foreach($members as $memberId)$this->send((int)$memberId,$type,$title,$content,$path,$payload);
    }
}
