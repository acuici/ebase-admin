<?php
declare (strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\exception\BusinessException;
use app\common\model\StorefrontSite;
use app\common\service\StorefrontSiteListService;
use app\validate\StorefrontSiteValidate;
use think\Request; use think\Response;
class StorefrontSiteController extends ApiController
{
 private StorefrontSiteListService $lists;
 public function __construct(\think\App $app){parent::__construct($app);$this->lists=new StorefrontSiteListService();}
 public function index(Request $request): Response { return $this->success($this->lists->list($request->get())); }
 public function read(int $id): Response { $site=StorefrontSite::find($id); if(!$site)throw BusinessException::notFound('站点不存在'); return $this->success($site); }
 public function create(Request $request): Response { $this->validate($request->post(),StorefrontSiteValidate::class); $site=StorefrontSite::create($request->only(['site_code','name','brand_name','service_email','default_locale','currency','timezone','status','default_seo_title','default_seo_description'])); return $this->success($site,'独立站创建成功',201); }
 public function update(Request $request,int $id): Response { $site=StorefrontSite::find($id); if(!$site)throw BusinessException::notFound('站点不存在'); $data=$request->only(['name','brand_name','service_email','default_locale','currency','timezone','status','default_seo_title','default_seo_description']); $this->validate(array_merge($site->toArray(),$data),StorefrontSiteValidate::class); $site->save($data); return $this->success($site,'站点设置已保存'); }
}
