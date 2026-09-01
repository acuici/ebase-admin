<?php
declare(strict_types=1);

namespace app\common\service\storage;

use app\common\exception\BusinessException;
use think\file\UploadedFile;

/**
 * Safe local development storage.
 * Files live in runtime/uploads rather than public; download goes through access checks.
 */
final class LocalAssetStorageService
{
    private const MAX_BYTES = 20 * 1024 * 1024;
    private const ALLOWED = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
        'video/mp4' => ['mp4'],
        'application/pdf' => ['pdf'],
    ];

    public function store(UploadedFile $file): array
    {
        $size = (int) $file->getSize();
        if ($size < 1 || $size > self::MAX_BYTES) {
            throw BusinessException::validationError(['file' => ['文件大小必须在 1B 到 20MB 之间']]);
        }

        $tmpPath = $file->getPathname();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        $extension = strtolower((string) $file->getOriginalExtension());
        if (!isset(self::ALLOWED[$mime]) || !in_array($extension, self::ALLOWED[$mime], true)) {
            throw BusinessException::validationError(['file' => ['文件类型或扩展名不被允许']]);
        }

        $hash = hash_file('sha256', $tmpPath);
        $path = date('Y/m/d') . '/' . $hash . '.' . $extension;
        $base = runtime_path('uploads');
        $target = $base . DIRECTORY_SEPARATOR . $path;
        $directory = dirname($target);

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new BusinessException('INTERNAL_ERROR', '无法创建上传目录', 500);
        }

        if (!is_file($target) && !move_uploaded_file($tmpPath, $target)) {
            throw new BusinessException('INTERNAL_ERROR', '文件保存失败', 500);
        }

        return [
            'storage_driver' => 'local',
            'storage_path' => $path,
            'original_name' => mb_substr((string) $file->getOriginalName(), 0, 255),
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => $size,
            'sha256' => $hash,
        ];
    }

    public function absolutePath(string $path): string
    {
        $base = realpath(runtime_path('uploads'));
        $candidate = realpath(runtime_path('uploads') . DIRECTORY_SEPARATOR . $path);
        if (!$base || !$candidate || !str_starts_with($candidate, $base . DIRECTORY_SEPARATOR)) {
            throw BusinessException::notFound('素材文件不存在');
        }
        return $candidate;
    }

    public function delete(string $path): void
    {
        $absolutePath = $this->absolutePath($path);
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new BusinessException('INTERNAL_ERROR', '素材文件删除失败', 500);
        }
    }
}
