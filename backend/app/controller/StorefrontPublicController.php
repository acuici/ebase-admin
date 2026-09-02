<?php
declare(strict_types=1);
namespace app\controller;
use app\common\controller\ApiController;
use app\common\model\StorefrontSite;
use think\facade\Db;
use think\Response;
final class StorefrontPublicController extends ApiController
{
    public function manifest(string $siteCode): Response
    {
        $site = StorefrontSite::where('site_code', $siteCode)->where('status', 'active')->find();
        if (!$site) return $this->error('STORE_NOT_FOUND', '站点不存在或未启用', 404);
        return $this->success(['site' => $site, 'listings' => Db::name('storefront_product_listings')->where('site_id', $site->id)->where('status', 'published')->select()]);
    }
    public function sitemap(string $siteCode): Response
    {
        $site = StorefrontSite::where('site_code', $siteCode)->where('status', 'active')->find();
        if (!$site) return response('', 404, ['Content-Type' => 'application/xml; charset=utf-8']);
        $urls = Db::name('storefront_product_listings')->where('site_id', $site->id)->where('status', 'published')->column('slug');
        $base = 'https://' . (Db::name('storefront_domains')->where('site_id', $site->id)->where('domain_type', 'primary')->value('domain') ?: $siteCode);
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($urls as $slug) $xml[] = '<url><loc>' . htmlspecialchars($base . '/products/' . rawurlencode($slug), ENT_XML1) . '</loc></url>';
        $xml[] = '</urlset>';
        return response(implode('', $xml), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
    public function robots(string $siteCode): Response
    {
        $site = StorefrontSite::where('site_code', $siteCode)->where('status', 'active')->find();
        if (!$site) return response('', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        $domain = Db::name('storefront_domains')->where('site_id', $site->id)->where('domain_type', 'primary')->value('domain') ?: $siteCode;
        return response("User-agent: *\nAllow: /\nSitemap: https://{$domain}/sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
