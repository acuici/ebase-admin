<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class StorefrontContentValidate extends Validate
{
 protected $rule=['content_type'=>'require|in:theme,navigation,page,policy,campaign,seo_redirect','content_key'=>'require|max:120|alphaDash','title'=>'require|max:255','status'=>'in:draft,published,archived','payload'=>'require|array'];
 protected $message=['content_type.in'=>'内容类型不合法','content_key.require'=>'内容标识不能为空','content_key.alphaDash'=>'内容标识格式不合法','title.require'=>'标题不能为空','payload.require'=>'内容数据不能为空','payload.array'=>'内容数据格式不合法'];
}
