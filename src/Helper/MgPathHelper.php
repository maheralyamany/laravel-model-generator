<?php

declare(strict_types=1);

namespace ModelGenerator\Helper;

use Illuminate\Support\Facades\File;
use ModelGenerator\CodeGenerator\Exception\GeneratorException;

class MgPathHelper
{
  /**
   * Determine if the given path is a file.
   *
   * @param  string  $file
   */
  public static function isFile($file): bool
  {
    return is_file($file);
  }

  public static function isDirectory(string $directory): bool
  {
    return is_dir($directory);
  }

  public static function exists(string $path): bool
  {
    return file_exists($path);
  }

  public static function getFileExtension(string $path): string
  {
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if ($extension && !str_starts_with($extension, ".")) {
      $extension = "." . $extension;
    }

    return $extension;
  }

  public static function getFilenameWithoutExtension(string $path): string
  {
    $extension = self::getFileExtension($path);
    $name = pathinfo($path, PATHINFO_FILENAME);
    //$fileName = Str::replaceEnd(".", "", $fileName);
    return \str_replace($extension, "", $name);
  }

  public static function getFileDirectory(string $filename): string
  {
    $directory = dirname($filename);
    // $directory = pathinfo($filename, PATHINFO_DIRNAME);
    return $directory;
  }

  public static function getPathDirectory(string $path): string
  {

    $directory = $path;
    if (is_file($path) || pathinfo($path, PATHINFO_EXTENSION)) {
      $directory = dirname($path);
    }

    return $directory;
  }

  public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true, bool $force = false): bool
  {
    $path = self::normalizePathOrUrl($path);
    if (!static::isDirectory($path)) {
      return File::makeDirectory($path, $mode, $recursive, $force);
    }

    return true;
  }




  public static function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true, bool $force = false): bool
  {
    $path = self::normalizePathOrUrl($path);
    $directory = self::getPathDirectory($path);
    if (!self::isDirectory($directory)) {
      if (!static::makeDirectory($directory, $mode, $recursive, $force)) {
        throw new GeneratorException(sprintf('Could not create directory %s', $directory));
      }
    }
    return true;
  }
  public static function ensureModelsDirectoryExists($directory): bool
  {
    $directory = self::normalizePathOrUrl($directory);
    if (self::ensureDirectoryExists($directory, 0777, true)) {
      if (!is_writeable($directory)) {
        throw new GeneratorException(sprintf('%s is not writeable', $directory));
      }
      return true;
    }
    return false;
  }
  public static  function emptyDirectory($path): void
  {

    $path = self::normalizePathOrUrl($path);
    if (static::isDirectory($path)) {
      $directories =  File::directories($path);

      // dd([$path, $directories]);
      foreach ($directories as $directory) {
        File::deleteDirectory($directory);
      }
      //$files =  File::allFiles($path);
      $files = glob($path . '/*');
      foreach ($files as $file) {
        if (self::exists($file))
          File::delete($file);
      }
    }
    //file_put_contents($outputFilepath, $content);
  }
 
  public static function normalizePath(string $path): string
  {
    // إزالة المسافات والاقتباسات الزائدة
    $path = trim($path, " \t\n\r\0\x0B\"'");
    // استبدال الباك سلاش إلى سلاش أمامي
    $path = str_replace('\\', '/', $path);
    // إزالة السلاشات المتكررة بدون regex
    while (strpos($path, '//') !== false) {
      $path = str_replace('//', '/', $path);
    }
    return $path;
  }
  public static function normalizePathOrUrl(string $path): string
  {
    //return (new WhitespacePathNormalizer())->normalizePath($path);
    // إزالة المسافات والاقتباسات الزائدة
    $path = trim($path, " \t\n\r\0\x0B\"'");
    // استبدال الباك سلاش إلى سلاش أمامي
    $path = str_replace('\\', '/', $path);
    // إزالة السلاشات المتكررة بدون regex
    while (strpos($path, '//') !== false && !str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
      $path = str_replace('//', '/', $path);
    }
    return $path;
  }
}
