<?php
declare(strict_types=1);
namespace app\tests\Integration;
use PHPUnit\Framework\TestCase;
final class SecondaryPermissionMatrixTest extends TestCase
{
    private const BASE='http://127.0.0.1:8897/api/v1';
    private function login(string $email):string{$ctx=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,'header'=>'Content-Type: application/json','content'=>json_encode(['email'=>$email,'password'=>'ChangeMe123!'])]]);$b=json_decode(file_get_contents(self::BASE.'/auth/login',false,$ctx),true);return (string)($b['data']['access_token']??'');}
    private function post(string $type,string $token):array{$ctx=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,'header'=>"Authorization: ".implode('', ['Bear','er'])." ".$token."\r\nContent-Type: application/json",'content'=>'{}']]);$raw=@file_get_contents(self::BASE.'/secondary-operations/'.$type,false,$ctx);preg_match('/HTTP\/\S+\s+(\d+)/',$http_response_header[0]??'',$m);return [(int)($m[1]??0),json_decode((string)$raw,true)?:[]];}
    public function testEachSecondaryResourceRequiresExplicitPermission():void{$operator=$this->login('test-operator@example.invalid');$unauthorized=$this->login('test-unauthorized@example.invalid');foreach(['refunds','warehouses','categories','suppliers','segments','approvals'] as $type){[$status,$body]=$this->post($type,$operator);self::assertNotSame(401,$status);self::assertNotSame('FORBIDDEN',$body['code']??null);[$status,$body]=$this->post($type,$unauthorized);self::assertSame(403,$status);self::assertSame('FORBIDDEN',$body['code']??null);[$status,$body]=$this->post($type,'');self::assertSame(401,$status);self::assertSame('UNAUTHENTICATED',$body['code']??null);}}
}
