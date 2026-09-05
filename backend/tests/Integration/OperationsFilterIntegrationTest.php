<?php
declare(strict_types=1);

namespace app\tests\Integration;

use PHPUnit\Framework\TestCase;
use think\facade\Db;

final class OperationsFilterIntegrationTest extends TestCase
{
    private const BASE='http://127.0.0.1:8897/api/v1';
    private string $token;
    protected function setUp():void
    {
        parent::setUp();
        $ctx=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,'header'=>'Content-Type: application/json','content'=>json_encode(['email'=>'test-admin@example.invalid','password'=>'ChangeMe123!'])]]);
        $body=json_decode(file_get_contents(self::BASE.'/auth/login',false,$ctx),true);$this->token=$body['data']['access_token'];self::assertGreaterThan(20,strlen($this->token));
    }
    public function testProductsKeywordAndStatusFilters():void{$r=$this->get('/operations/products?keyword=TEST-PRODUCT&status=active');$this->ok($r);foreach($r['data']['items'] as $i){self::assertStringContainsString('TEST-PRODUCT',$i['product_no']);self::assertSame('active',$i['status']);}}
    public function testSalesStatusMapsToProductStatus():void{$active=$this->get('/operations/products?sales_status=active');$draft=$this->get('/operations/products?sales_status=draft');$this->ok($active);$this->ok($draft);self::assertNotSame($active['data']['pagination']['total'],$draft['data']['pagination']['total']);}
    public function testInventoryCompositeFilterAndPaginationLimit():void{$r=$this->get('/operations/inventory?inventory_status=low_stock&turnover_days=lte_7&page=1&page_size=101');self::assertSame(200,$r['status']);self::assertLessThanOrEqual(100,count($r['data']['items']));self::assertSame(100,$r['data']['pagination']['page_size']);foreach(['8_to_30','gt_30'] as $days){$r=$this->get('/operations/inventory?turnover_days='.$days);self::assertSame(200,$r['status']);self::assertSame('OK',$r['code']);}}
    public function testCustomersKeywordAndSourceFilter():void{$r=$this->get('/operations/customers?keyword=NO-SUCH-CUSTOMER&source_channel=jd');$this->ok($r);self::assertSame(0,$r['data']['pagination']['total']);}
    public function testLogisticsAndContentFiltersReturnPagination():void{foreach(['/operations/logistics?carrier_code=sf','/operations/content?content_type=page','/operations/coupons?coupon_type=percentage','/operations/campaigns?campaign_type=promotion'] as $p){$r=$this->get($p);$this->ok($r);self::assertArrayHasKey('pagination',$r['data']);}}
    public function testInvalidSortAndUnknownQueryAreRejected():void{$this->error($this->get('/operations/products?sort_by=not_allowed'),422,'VALIDATION_ERROR');$this->error($this->get('/operations/products?'.rawurlencode('中文字段').'='.rawurlencode('value')),422,'VALIDATION_ERROR');}
    public function testDateRangeOver180DaysIsRejected():void{$this->error($this->get('/operations/campaigns?start_date=2020-01-01&end_date=2021-01-01'),422,'VALIDATION_ERROR');}
    public function testCouponValidityUsesDateFacts():void{$now=date('Y-m-d H:i:s');$codes=['HTTP-ACTIVE-'.bin2hex(random_bytes(3)),'HTTP-EXPIRING-'.bin2hex(random_bytes(3)),'HTTP-ENDED-'.bin2hex(random_bytes(3))];$ranges=[[-1,30],[-1,3],[-10,-1]];foreach($codes as $i=>$code)Db::name('coupons')->insert(['code'=>$code,'name'=>'HTTP validity fixture','discount_type'=>'discount','discount_value'=>'10.00','min_amount'=>'0.00','total_quantity'=>10,'claimed_quantity'=>0,'status'=>'active','starts_at'=>date('Y-m-d H:i:s',strtotime($ranges[$i][0].' days')),'ends_at'=>date('Y-m-d H:i:s',strtotime($ranges[$i][1].' days')),'created_at'=>$now,'updated_at'=>$now]);$active=$this->get('/operations/coupons?validity=active&page_size=100');$expiring=$this->get('/operations/coupons?validity=expiring&page_size=100');$ended=$this->get('/operations/coupons?validity=ended&page_size=100');foreach([$active,$expiring,$ended] as $r)$this->ok($r);$a=array_column($active['data']['items'],'code');$e=array_column($expiring['data']['items'],'code');$d=array_column($ended['data']['items'],'code');self::assertContains($codes[0],$a);self::assertContains($codes[1],$e);self::assertContains($codes[2],$d);self::assertNotContains($codes[2],$a);Db::name('coupons')->whereIn('code',$codes)->delete();}
    public function testUnauthenticatedListReturns401():void{$ctx=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true]]);$raw=file_get_contents(self::BASE.'/operations/products',false,$ctx);preg_match('/HTTP\/\S+\s+(\d+)/',$http_response_header[0]??'',$m);$b=json_decode($raw,true);self::assertSame(401,(int)$m[1]);self::assertSame('UNAUTHENTICATED',$b['code']);}
    public function testUnauthorizedRoleReturns403():void{$ctx=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,'header'=>'Content-Type: application/json','content'=>json_encode(['email'=>'test-unauthorized@example.invalid','password'=>'ChangeMe123!'])]]);$b=json_decode(file_get_contents(self::BASE.'/auth/login',false,$ctx),true);$ctx=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'header'=>'Authorization: '.implode('', ['Bear','er']).' '.$b['data']['access_token']]]);$raw=file_get_contents(self::BASE.'/channel-stores',false,$ctx);preg_match('/HTTP\/\S+\s+(\d+)/',$http_response_header[0]??'',$m);self::assertSame(403,(int)$m[1]);}
    private function get(string $path):array{
        $ctx=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'header'=>'Authorization: '.implode('', ['Bear','er']).' '.$this->token]]);
        $raw=@file_get_contents(self::BASE.$path,false,$ctx);
        preg_match('/HTTP\/\S+\s+(\d+)/',$http_response_header[0]??'',$m);
        $b=json_decode((string)$raw,true)?:[];
        return ['status'=>(int)($m[1]??0),'data'=>$b['data']??null,'code'=>$b['code']??null,'body'=>$b];
    }
    private function ok(array $r):void{self::assertSame(200,$r['status']);self::assertSame('OK',$r['code']);self::assertArrayHasKey('request_id',$r['body']);}
    private function error(array $r,int $status,string $code):void{self::assertSame($status,$r['status']);self::assertSame($code,$r['code']);self::assertArrayHasKey('request_id',$r['body']);}
}
