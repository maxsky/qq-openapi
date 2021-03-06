<?php

/**
 * 生成签名类
 *
 * @version 3.0.3
 * @author open.qq.com
 * @copyright © 2012, Tencent Corporation. All rights reserved.
 * @ History:
 *           3.0.3 | nemozhang | 2012-08-28 16:40:20 | support cpay callback sig verifictaion.
 *           3.0.2 | sparkeli | 2012-03-06 17:58:20 | add statistic fuction which can report API's access time and
 *           number to background server
 *           3.0.1 | nemozhang | 2012-02-14 17:58:20 | resolve a bug: at line 108, change  'post' to  $method
 *           3.0.0 | nemozhang | 2011-12-12 11:11:11 | initialization
 */

namespace Tencent\QQ\Open\lib;

class SnsSigCheck {

    /**
     * 生成签名
     *
     * @param string $method 请求方法，get/post
     * @param string $url_path
     * @param array  $params 表单参数
     * @param string $secret 密钥
     *
     * @return string
     */
    static public function makeSig($method, $url_path, $params, $secret) {
        $mk = self::makeSource($method, $url_path, $params);
        $signed = hash_hmac("sha1", $mk, strtr($secret, '-_', '+/'), true);

        return base64_encode($signed);
    }

    private static function makeSource($method, $url_path, $params) {
        $str = strtoupper($method) . '&' . rawurlencode($url_path) . '&';

        ksort($params);
        $query_string = [];
        foreach ($params as $key => $val) {
            array_push($query_string, $key . '=' . $val);
        }
        $query_string = implode('&', $query_string);

        return $str . str_replace('~', '%7E', rawurlencode($query_string));
    }

    /**
     * 验证回调发货 URL 的签名（注意和普通的 OpenAPI 签名算法不同，详见 @refer 说明）
     *
     * @param string $method 请求方法，get/post
     * @param string $url_path
     * @param array  $params 腾讯调用发货回调 URL 携带的请求参数
     * @param string $secret 密钥
     * @param string $sig 腾讯调用发货回调 URL 时传递的签名
     *
     * @refer http://wiki.open.qq.com/wiki/%E5%9B%9E%E8%B0%83%E5%8F%91%E8%B4%A7URL%E7%9A%84%E5%8D%8F%E8%AE%AE%E8%AF%B4%E6%98%8E_V3
     *
     * @return bool
     */
    public static function verifySig($method, $url_path, $params, $secret, $sig) {
        // 先使用专用的编码规则对 value 编码
        foreach ($params as $k => $v) {
            $params[$k] = self::encodeValue($v);
        }

        // 计算签名
        $signed = self::makeSig($method, $url_path, $params, $secret);

        return $signed === $sig;
    }

    /**
     * 回调发货URL专用的编码算法。编码规则为：除了 0~9 a~z A~Z !*()之外其他字符按其ASCII码的十六进制加%进行表示，例如"-"编码为"%2D"
     *
     * @param $value
     *
     * @refer http://wiki.open.qq.com/wiki/%E5%9B%9E%E8%B0%83%E5%8F%91%E8%B4%A7URL%E7%9A%84%E5%8D%8F%E8%AE%AE%E8%AF%B4%E6%98%8E_V3
     *
     * @return string
     */
    private static function encodeValue($value) {
        $result = '';

        $len = strlen($value);

        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            if (preg_match("/[a-zA-Z0-9!\(\)*]{1,1}/", $c)) {
                $result .= $c;
            } else {
                $result .= ("%" . sprintf("%02X", ord($c)));
            }
        }

        return $result;
    }
}
