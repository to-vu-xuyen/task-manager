<?php

namespace common\helpers;

use yii\web\UploadedFile;

class FileStorageHelper
{
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    public static function getUniqueName(UploadedFile $file): string
    {
        return uniqid('file_', true) . '.' . $file->extension;
    }
}