<?php
declare (strict_types=1);
namespace app\common\service\storage;
use app\common\contract\StorageServiceInterface;
use Aws\S3\S3Client;
/** S3 API adapter: MinIO, AWS S3, Aliyun OSS, Tencent COS compatible endpoints. */
class S3StorageService implements StorageServiceInterface
{
    public function __construct(private readonly S3Client $client, private readonly string $bucket) {}
    public function upload(string $path, string $content, string $mimeType): array
    {
        $result=$this->client->putObject(['Bucket'=>$this->bucket,'Key'=>$path,'Body'=>$content,'ContentType'=>$mimeType]);
        return ['path'=>$path,'etag'=>trim((string)$result['ETag'],'"')];
    }
    public function signedUrl(string $path, int $expiresIn = 900): string
    {
        $command=$this->client->getCommand('GetObject',['Bucket'=>$this->bucket,'Key'=>$path]);
        return (string)$this->client->createPresignedRequest($command,'+'.$expiresIn.' seconds')->getUri();
    }
    public function delete(string $path): void { $this->client->deleteObject(['Bucket'=>$this->bucket,'Key'=>$path]); }
    public function metadata(string $path): array
    {
        $head=$this->client->headObject(['Bucket'=>$this->bucket,'Key'=>$path]);
        return ['size'=>(int)$head['ContentLength'],'mime_type'=>(string)$head['ContentType'],'etag'=>trim((string)$head['ETag'],'"')];
    }
}
