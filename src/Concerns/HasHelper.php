<?php

namespace Saidqb\LaravelSupport\Concerns;

trait HasHelper
{
    static function issetVal($data, $key = '', $default = '')
    {
        if ($key === '') {
            $isset_data = isset($data) ? $data : $default;
            return static::emptyVal($data);
        }
        if (is_array($data)) {

            $isset_data =  isset($data[$key]) ? $data[$key] : $default;
            return static::emptyVal($isset_data, $default);
        } else {

            $isset_data =  isset($data->{$key}) ? $data->{$key} : $default;
            return static::emptyVal($isset_data, $default);
        }
    }

    static function emptyVal($data, $default = '')
    {
        if ($data === 0 || $data === '0') {
            return $data;
        }

        return !empty($data) ? $data : $default;
    }


    static function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }


    static function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }


    static function isJson($string)
    {
        return is_string($string) && is_array(json_decode($string, TRUE)) && (json_last_error() == JSON_ERROR_NONE) ? TRUE : FALSE;
    }
}
