<?php

namespace App\Helpers;

use InvalidArgumentException;
use Throwable;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * @param mixed $file
     * @param string $path
     * @param string|null $filename
     * @param bool $isPublic
     * @return Exception|string
     * @throws Exception
     */
    public static function uploadToFirebaseStorage(
        mixed  $file,
        string $path = 'uploads',
        string $filename = null,
        bool   $isPublic = false
    ): Exception|string
    {
        try {
            $bucket = app('firebase.storage')->getBucket();
            $path = $path . DIRECTORY_SEPARATOR . ($filename ?? uniqid() . '_' . $file->getClientOriginalName());
            $bucket->upload(
                fopen($file->getPathname(), 'r'),
                array_merge(
                    ['name' => $path],
                    $isPublic ? config('firebase.acl') : []
                )
            );
            return $path;
        } catch (\Exception $exception) {
            return throw new Exception($exception->getMessage(), HttpStatus::STATUS_400);
        }
    }

    /**
     * @param string|null $filePath
     * @param int|null $minute
     * @return string|null
     * @throws Throwable
     */
    public static function signedUrlOnFirebaseStorage(?string $filePath, ?int $minute = null): ?string
    {
        if (!$filePath || filter_var($filePath, FILTER_VALIDATE_URL)) {
            return null;
        }
        if ($minute !== null && $minute < 0) {
            throw new InvalidArgumentException('Minute must be a non-negative integer');
        }
        return self::objectFileOnFirebaseStorage($filePath)->signedUrl(
            Carbon::now()->addMinutes(
                $minute ?? config('firebase.signed_url_minute')
            )
        );
    }

    /**
     * @param string $filePath
     * @return mixed
     * @throws Throwable
     */
    public static function objectFileOnFirebaseStorage(string $filePath): mixed
    {
        $bucket = app('firebase.storage')->getBucket();
        $object = $bucket->object($filePath);
        if (!$object->exists())
            return null;
        return $object;
    }

    /**
     * @param string $filePath
     * @return mixed
     * @throws Throwable
     */
    public static function deleteFileOnFirebaseStore(string $filePath): mixed
    {
        return self::objectFileOnFirebaseStorage($filePath)->delete();
    }

    /**
     * @param mixed $file
     * @param string $path
     * @param string|null $disk
     * @param array $options
     * @return string
     */
    public static function uploadFile(
        mixed  $file,
        string $path = 'uploads',
        string $disk = null,
        array  $options = []
    ): string
    {
        return Storage::disk($disk)->put($path, $file, $options);
    }

    /**
     * @param mixed $file
     * @param string $path
     * @param string|null $filename
     * @param string|null $disk
     * @param array|null $options
     * @return string
     */
    public static function uploadWithFilename(
        mixed   $file,
        string  $path,
        ?string $filename = null,
        ?string $disk = null,
        ?array  $options = []
    ): string
    {
        $filename = self::getFilename($file, $filename);
        return Storage::disk($disk)->putFileAs($path, $file, $filename, $options);
    }

    /**
     * @param string|null $imagePath
     * @param string|null $disk
     * @return string|null
     */
    public static function urlFile(?string $imagePath, ?string $disk = null): ?string
    {
        if (!$imagePath || filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return null;
        }
        return Storage::disk($disk)->url($imagePath);
    }

    /**
     * @param mixed $file
     * @param string|null $filename
     * @return string
     */
    private static function getFilename(mixed $file, ?string $filename): string
    {
        if (!$filename) {
            $filename = Str::uuid();
        }
        return !empty(pathinfo($filename, PATHINFO_EXTENSION)) ? $filename : $filename . '.' . $file->extension();
    }

    /**
     * @param string $path
     * @param string $newPath
     * @param string|null $disk
     * @return bool
     */
    public static function copyFile(string $path, string $newPath, string $disk = null): bool
    {
        return Storage::disk($disk)->copy($path, $newPath);
    }

    /**
     * @param string $path
     * @param string $newPath
     * @param string|null $disk
     * @return bool
     */
    public static function moveFile(string $path, string $newPath, string $disk = null): bool
    {
        return Storage::disk($disk)->move($path, $newPath);
    }

    /**
     * @param string $filePath
     * @param string|null $disk
     * @return string|bool
     */
    public static function deleteFile(string $filePath, string $disk = null): string|bool
    {
        try {
            return Storage::disk($disk)->delete($filePath);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $filePath
     * @param string|null $disk
     * @return string|bool
     */
    public static function existsFile(string $filePath, string $disk = null): string|bool
    {
        try {
            return Storage::disk($disk)->exists($filePath);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string|null $path
     * @return string|null
     */
    public static function getFullPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        return !self::isAbsolutePath($path) ? self::urlFile($path) : $path;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAbsolutePath(string $path): bool
    {
        return strspn($path, '/\\', 0, 1)
            || (strlen($path) > 3 && ctype_alpha($path[0])
                && substr($path, 1, 1) === ':'
                && strspn($path, '/\\', 2, 1)
            ) || null !== parse_url($path, PHP_URL_SCHEME);
    }
}
