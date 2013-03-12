<?php
/**
 * 这个类是用来通过优酷视频id解析其真实m3u8地址，该地址可用于html5中的video标签进行播放
 * m3u8格式应该只能在mac、ios等平台下正常播放
 *
 * @author 苏牧羊 <i@ucai.me>
 */

class youku
{
    protected static $_cache = array();

    protected static $_error;

    /**
     * 获取视频地址,默认获取最不清晰的视频
     *
     * @param $videoIds
     * @param string $streamType
     * @return bool|string
     */
    static function getVideoUrl($videoIds, $streamType = 'flv')
    {
        $info = self::_getVideoInfo($videoIds);
        if (!$info) {
            return false;
        }
        if (!in_array($streamType, $info['typelist'])) {
            $streamType = 'flv';
        }
        $params = "{$info['vid']}/type/{$streamType}/sid/{$info['sid']}/K/{$info['key']}/video.m3u8";
        return 'http://v.youku.com/player/getM3U8/vid/' . $params;
    }

    /**
     * 获取视频清晰度
     *
     * @param $videoIds
     * @return mixed|bool
     */
    static function getStreamTypes($videoIds)
    {
        $info = self::_getVideoInfo($videoIds);
        return $info && isset($info['typelist']) ? $info['typelist'] : false;
    }

    /**
     * 清空缓存
     */
    static function clear()
    {
        self::$_cache = array();
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    static function error()
    {
        return self::$_error;
    }

    /**
     * 获取视频信息
     *
     * @param $videoIds
     * @return bool
     */
    protected static function _getVideoInfo($videoIds)
    {
        if (empty(self::$_cache[$videoIds])) {
            self::$_cache[$videoIds] = self::_parseVideoParam($videoIds);
            if (empty(self::$_cache[$videoIds])) {
                self::$_error = '获取视频信息失败';
                return false;
            }
        }
        return self::$_cache[$videoIds];
    }

    /**
     * 分析视频参数
     *
     * @param $videoIds
     * @return array
     */
    protected static function _parseVideoParam($videoIds)
    {
        $url = 'http://v.youku.com/player/getPlayList/VideoIDS/' . $videoIds;
        $source = self::_getHtml($url);
        $js = json_decode($source, true);
        return array(
            'vid' => $js['data'][0]['videoid'],
            'typelist' => $js['data'][0]['streamtypes'],
            'key' => self::_getKey($js['data'][0]['key1'], $js['data'][0]['key2']),
            'sid' => self::_sid(),
        );
    }

    /**
     * 获取html代码资源，封装curl
     *
     * @param $url
     * @return bool|mixed
     */
    protected static function _getHtml($url)
    {
        if (empty($url)) {
            return false;
        }

        // 初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 启用时会将服务器服务器返回的“Location:”放在header中递归的返回给服务器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // 设置 HTTP USERAGENT
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0.1) Gecko/20100101 Firefox/5.0.1 FirePHP/0.5');
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, '15');
        // 设置客户端是否支持 gzip压缩
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($output === false) {
            return false;
        }
        return $output;
    }

    /**
     * 生成sid
     *
     * @return string
     */
    protected static function _sid()
    {
        $sid = time() . (rand(0, 9000) + 10000);
        return $sid;
    }

    /**
     * 获得加密key的算法
     *
     * @param $key1
     * @param $key2
     * @return string
     */
    protected static function _getKey($key1, $key2)
    {
        $a = hexdec($key1);
        $b = $a ^ 0xA55AA5A5;
        $b = dechex($b);
        return $key2 . $b;
    }
}