<?php

namespace common\services\storage;

use Yii;
use yii\web\UploadedFile;
use common\services\storage\interface\FileStorageServiceInterface;

class FileStorageService implements FileStorageServiceInterface
{
    private string $basePath;
    public function __construct()
    {
        $this->basePath = Yii::getAlias('@frontend/web');
    }


    public function upload(UploadedFile $file, string $directory): string
    {
        $uploadPath = $this->getAbsolutePath($directory);

        $this->ensureDirectoryExists($uploadPath);
        
        $uniqueName = $this->getUniqueName($file);
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;
        if (!$file->saveAs($fullPath)) {
            throw new \RuntimeException('Cannot save file: ' . $file->name);
        }
        return $directory . DIRECTORY_SEPARATOR . $uniqueName;
    }

    public function delete(string $relativePath): bool
    {
        $filePath = $this->getAbsolutePath($relativePath);
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found: ' . $filePath);
        }
        if(unlink($filePath)){
            return true;
        }
        throw new \RuntimeException('Cannot delete file: ' . $filePath);
    }

    // public function get(string $relativePath): string
    // {
    //     $filePath = $this->getAbsolutePath($relativePath);
    //     if (!file_exists($filePath)) {
    //         return '';
    //     }
    //     return $filePath;
    // }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function getAbsolutePath(string $directory): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $directory;
    }

    private function getUniqueName(UploadedFile $file): string
    {
        return uniqid('file_', true) . '.' . $file->extension;
    }
}