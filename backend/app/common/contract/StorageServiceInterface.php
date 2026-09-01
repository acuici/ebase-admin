<?php
declare (strict_types=1);
namespace app\common\contract;
interface StorageServiceInterface
{
    public function upload(string $path, string $content, string $mimeType): array;
    public function signedUrl(string $path, int $expiresIn = 900): string;
    public function delete(string $path): void;
    public function metadata(string $path): array;
}
