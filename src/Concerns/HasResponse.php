<?php

namespace Saidqb\LaravelSupport\Concerns;

use Saidqb\LaravelSupport\ResponseCode;

trait HasResponse
{

    static $responseJsonDecode = array();
    static $responseFilterConfig = array();

    static function responseDecodeFor($arr = [])
    {
        static::$responseJsonDecode = $arr;
    }

    static function responseConfig($arr = [])
    {
        $default = [
            'hide' => [],
            'decode' => [],
            'decode_child' => [],
            'decode_array' => [],
            'add_field' => [],
            'hook' => [],
        ];

        $rArr = array_merge($default, $arr);
        static::$responseFilterConfig = $rArr;
    }

    static function responseConfigAdd($config, $value = [])
    {
        static::$responseFilterConfig[$config] = $value;
    }

    static function responseAddField($field, $value = '')
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                static::$responseFilterConfig['add_field'][$k] = $v;
            }
            return true;
        }
        static::$responseFilterConfig['add_field'][$field] = $value;
        return true;
    }

    static function filterResponseField($arr)
    {
        $nv = [];
        if (is_string($arr)) {
            return $nv;
        }

        if (!empty($arr)) {
            foreach ($arr as $kv => $v) {

                if (!empty(static::$responseFilterConfig['hide'])) {
                    if (in_array($kv, static::$responseFilterConfig['hide'])) continue;
                }

                if (!empty(static::$responseFilterConfig['decode'])) {

                    if (in_array($kv, static::$responseFilterConfig['decode'])) {
                        if (!empty($v) && static::isJson($v)) {
                            $nv[$kv] = json_decode($v);

                            if (!empty(static::$responseFilterConfig['decode_child'])) {
                                foreach (static::$responseFilterConfig['decode_child'] as $kdc => $vdc) {
                                    if (strpos($vdc, '.') !== false) {
                                        $childv = explode('.', $vdc);
                                        if ($kv == $childv[0]) {
                                            if (isset($nv[$kv]->{$childv[1]})) {
                                                $nv[$kv]->{$childv[1]}  = (empty($nv[$kv]->{$childv[1]})) ? (object)null : $nv[$kv]->{$childv[1]};
                                            }
                                        }
                                    }
                                }
                            }
                        } else if (is_array($v)) {
                            $nv[$kv] = (empty($v)) ? (object)null : $v;
                        } else {
                            $nv[$kv] = (object) null;
                        }
                        continue;
                    }
                }

                if (!empty(static::$responseFilterConfig['decode_array'])) {
                    if (in_array($kv, static::$responseFilterConfig['decode_array'])) {
                        if (!empty($v) && static::isJson($v)) {
                            $nv[$kv] = json_decode($v);
                        } else if (is_array($v)) {
                            $nv[$kv] = $v;
                        } else {
                            $nv[$kv] = array();
                        }
                        continue;
                    }
                }

                if (in_array($kv, static::$responseJsonDecode)) {
                    $nv[$kv] = json_decode($v);
                    continue;
                }

                if (is_null($v) || $v === NULL) {
                    $v = '';
                }

                $nv[$kv] = $v;
            }

            if (!empty(static::$responseFilterConfig['add_field'])) {
                foreach (static::$responseFilterConfig['add_field'] as $k => $v) {
                    $nv[$k] = $v;
                }
            }

            if (!empty(static::$responseFilterConfig['hook'])) {
                foreach (static::$responseFilterConfig['hook'] as $k => $v) {
                    if (is_callable(static::$responseFilterConfig['hook'][$k])) {
                        $nv = call_user_func(static::$responseFilterConfig['hook'][$k], $nv);
                    }
                }
            }
        }

        return $nv;
    }

    /**
     * Response data
     *
     * @param array $data
     * @param int $status
     * @param string $message
     * @param int $error_code
     * @return \Illuminate\Http\JsonResponse
     */
    static function response($data = [], $status = ResponseCode::HTTP_OK, $message = ResponseCode::HTTP_OK_MESSAGE, $error_code = 0)
    {
        // rebuild the response data, if the data is not an array
        if (is_string($data) || is_numeric($data)){
            $error_code = $message == ResponseCode::HTTP_OK_MESSAGE ? $error_code : $message;
            $message = $status == ResponseCode::HTTP_OK ? $error_code : $status;
            $status = $data;
        } else if (is_object($data)) {
            $data = (array)$data;
        }

        $resData = [
            'status' => $status,
            'success' => $status == ResponseCode::HTTP_OK ? true : false, // 'true' or 'false
            'error_code' => $error_code,
            'message' => $message,
            'data' => [],
        ];


        if (isset($data['items'])) {
            $items = [];

            if (!empty($data['items'])) {
                $items = $data['items'];
            }

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $k => $v) {
                    $items[$k] = static::filterResponseField($v);
                }
            }

            $resData['data']['items'] = $items;

            if (!isset($data['pagination'])) {
                $resData['data']['pagination'] = (object) null;
            } else {
                if ($data['pagination'] === false) {
                    unset($resData['data']['pagination']);
                }

                $resData['data']['pagination'] = $data['pagination'];
            }

            return response()->json($resData, $status);
        }

        if (isset($data['item'])) {

            $data['item'] = static::filterResponseField($data['item']);
            $resData['data'] = $data;
            return response()->json($resData, $status);
        }

        $item = (object) null;

        if (!empty($data)) {
            if (is_array($data)) {
                $item = static::filterResponseField($data);
            }
        }

        $resData['data']['item'] = $item;
        return response()->json($resData, $status);
    }
}
