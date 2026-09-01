<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;use think\facade\Db;use think\Request;use think\Response;
final class NotificationController extends ApiController
{
 public function index(Request $request):Response{$member=$this->requireMember();$items=Db::name('notifications')->where('member_id',$member->id)->order('id','desc')->limit(50)->select();$unread=Db::name('notifications')->where('member_id',$member->id)->whereNull('read_at')->count();return $this->success(['items'=>$items,'unread'=>$unread]);}
 public function read(int $id):Response{$member=$this->requireMember();Db::name('notifications')->where('id',$id)->where('member_id',$member->id)->update(['read_at'=>date('Y-m-d H:i:s')]);return $this->success(null,'通知已读');}
 public function readAll():Response{$member=$this->requireMember();Db::name('notifications')->where('member_id',$member->id)->whereNull('read_at')->update(['read_at'=>date('Y-m-d H:i:s')]);return $this->success(null,'通知已全部读');}
}
