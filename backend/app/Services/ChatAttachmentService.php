<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatAttachmentService
{
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;

    public function storeMany(array $files): array
    {
        return collect($files)
            ->filter()
            ->map(fn ($file) => $this->storeOne($file))
            ->values()
            ->all();
    }

    private function storeOne($file): array
    {
        if (! $file || ! $file->isValid()) {
            throw ValidationException::withMessages([
                'files' => ['上传附件无效，请重新选择文件。'],
            ]);
        }

        if (($file->getSize() ?? 0) > self::MAX_FILE_BYTES) {
            throw ValidationException::withMessages([
                'files' => ['单个附件大小不能超过 10 MB。'],
            ]);
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $size = (int) ($file->getSize() ?? 0);
        $folder = public_path('uploads/chat');

        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $storedName = Str::uuid()->toString() . '.' . $extension;
        $file->move($folder, $storedName);

        $publicPath = '/uploads/chat/' . $storedName;
        $fullPath = $folder . DIRECTORY_SEPARATOR . $storedName;
        $kind = $this->detectKind($mimeType, $extension);
        $extractedText = $this->extractText($fullPath, $mimeType, $extension, $kind);

        return [
            'name' => $originalName,
            'path' => $publicPath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'kind' => $kind,
            'text_content' => $extractedText,
        ];
    }

    private function detectKind(string $mimeType, string $extension): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if ($this->isTextLike($mimeType, $extension)) {
            return 'text';
        }

        return 'file';
    }

    private function isTextLike(string $mimeType, string $extension): bool
    {
        if (str_starts_with($mimeType, 'text/')) {
            return true;
        }

        return in_array($extension, [
            'txt', 'md', 'markdown', 'json', 'csv', 'log', 'xml', 'html', 'htm',
            'js', 'ts', 'tsx', 'jsx', 'vue', 'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp',
            'sql', 'yml', 'yaml', 'ini', 'sh', 'bat', 'ps1'
        ], true);
    }

    private function extractText(string $fullPath, string $mimeType, string $extension, string $kind): ?string
    {
        if ($kind !== 'text') {
            return null;
        }

        $raw = @file_get_contents($fullPath);

        if ($raw === false || $raw === '') {
            return null;
        }

        $encoding = mb_detect_encoding($raw, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $raw = @mb_convert_encoding($raw, 'UTF-8', $encoding);
        }

        $cleaned = preg_replace([
            "/\r\n?/",
            "/\t/",
        ], [
            "\n",
            ' ',
        ], (string) $raw);

        return Str::limit(trim((string) $cleaned), 12000, "\n...[已截断]");
    }

    public function toDataUrl(?string $publicPath, ?string $mimeType): ?string
    {
        if (! $publicPath || ! $mimeType || ! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        $fullPath = public_path(ltrim($publicPath, '/'));

        if (! File::exists($fullPath)) {
            return null;
        }

        $raw = @file_get_contents($fullPath);

        if ($raw === false) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($raw));
    }
}