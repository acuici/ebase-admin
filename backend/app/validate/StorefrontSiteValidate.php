<?php
declare (strict_types=1);
namespace app\validate;
use think\Validate;
class StorefrontSiteValidate extends Validate
{
 protected $rule=['site_code'=>'require|max:64|alphaDash','name'=>'require|max:120','default_locale'=>'require|max:16','currency'=>'require|alpha|length:3','timezone'=>'require|max:64','status'=>'in:draft,active,maintenance,disabled','service_email'=>'email'];
 protected $message=['site_code.require'=>'站点代码不能为空','site_code.alphaDash'=>'站点代码格式不合法','name.require'=>'站点名称不能为空','currency.length'=>'币种必须是 3 位代码','status.in'=>'站点状态不合法','service_email.email'=>'客服邮箱格式不正确'];
}
