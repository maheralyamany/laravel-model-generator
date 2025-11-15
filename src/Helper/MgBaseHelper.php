<?php

declare(strict_types=1);

namespace ModelGenerator\Helper;

use Doctrine\DBAL\Schema\Table;
use Exception;
use Illuminate\Support\Str;
use Throwable;

class MgBaseHelper
{
  public static function parseBoolean(mixed $value, $default = false)
  {
    if (is_null($value))
      return value($default);
    return match ($value) {
      'false', '0', 'FALSE', 0, false => false,
      'true', '1', 'TRUE', 1, true => true,
      default => value($default),
    };
  }
  public static function isEmpty(mixed $value): bool
  {
    return !isset($value) || $value === null || empty($value);
  }
  public static function isNotEmpty(mixed $value): bool
  {
    return !self::isEmpty($value);
  }
  public static function isEquals(string $first, string $second): bool
  {
    return  self::lower($first) === self::lower($second);
  }
  public static function isNotEquals(string $first, string $second): bool
  {
    return  !self::isEquals($first,$second);
  }
  public static function lower($value)
  {
    return mb_strtolower($value, 'UTF-8');
  }
  public static function isJsonValue($string): bool
  {
    try {
      if (!is_string($string)) {
        return false;
      }
      $string = trim($string);
      return substr($string, 0, 1) === '{' || substr($string, 0, 1) === '[';
    } catch (Exception $exception) {
      //throw $th;
    }
    return false;
  }
  public static  function getValueByKey(array $array, string $key, $default = null)
  {
    $keys = explode('.', $key);
    $value = $array;
    foreach ($keys as $k) {
      // فك JSON مرة واحدة فقط إذا كانت string
      if (is_string($value) && self::isJsonValue($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $value = $decoded;
        }
      }

      // الوصول إلى المصفوفة أو JSON بعد فكها
      if (is_array($value)) {
        if (array_key_exists($k, $value)) {
          $value = $value[$k];
        } elseif (is_numeric($k) && array_key_exists((int) $k, $value)) {
          $value = $value[(int) $k];
        } else {
          return value($default);
        }
      } else {
        return value($default);
      }
    }

    return self::autoCast($value, $default);
  }
  public static function autoCast($value, $default = null)
  {
    try {
      if (self::isEmpty($value)) {
        return $default == null ? $value : value($default);
      }

      if (is_string($value)) {
        $trimmed = trim($value);
        // رقم صحيح أو عشري
        if (is_numeric($trimmed)) {
          return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        // boolean
        $lower = strtolower($trimmed);
        if (in_array($lower, [
          'true',
          'false',
          '1',
          '0',
        ], true)) {
          return in_array($lower, [
            'true',
            '1',
          ], true);
        }

        // JSON
        if (self::isJsonValue($trimmed)) {
          $decoded = json_decode($trimmed, true);
          if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
          } else {
            return value($default ?? []);
          }
        }
      }
    } catch (Throwable $throwable) {
      //throw $th;
    }

    return $value;
  }
  public static function jsonToArray($string, $default = [])
  {
    if (self::isEmpty($string)) {
      return value($default);
    }

    if (is_array($string)) {
      return $string;
    }

    if (!is_string($string)) {
      return value($default);
    }

    $trimmed = trim($string);
    if (self::isJsonValue($trimmed)) {
      $decoded = json_decode($trimmed, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }

    return value($default);
  }
}
