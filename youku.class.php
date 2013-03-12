<?php
/**
 * 这个类是用来通过优酷视频id解析其真实m3u8地址，该地址可用于html5中的video标签进行播放
 * m3u8格式应该只能在mac、ios等平台下正常播放
 * 
 * @author 苏牧羊 <i@ucai.me>
 * 
 */

class youku{
	public $VideoIds;
	public $StreamType;
	public $VideoUrl;
    public $VideoParam = array();
    public $error;

    /**
     * 获取html代码资源，封装curl
     * @param $url
     * @return bool|mixed
     */
    static function getHtml($url)
    {
        if (empty($url)) {
            return false;
        }
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //启用时会将服务器服务器返回的“Location:”放在header中递归的返回给服务器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //设置 HTTP USERAGENT
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0.1) Gecko/20100101 Firefox/5.0.1 FirePHP/0.5');
        //设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, '15');
        //设置客户端是否支持 gzip压缩
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
     * 获取js存储的信息
     * @return bool|mixed
     */
    private function getJs()
    {
        $url = 'http://v.youku.com/player/getPlayList/VideoIDS/' . $this->VideoIds;
        return self::getHtml($url);
    }

    /**
     * 获取视频地址,默认获取最不清晰的视频
     * @param $VideoIds
     * @param $StreamType
     * @return string
     */
    public function getVideoUrl($VideoIds, $StreamType='flv')
    {
        $this->VideoIds = $VideoIds;
        $this->StreamType =$StreamType;
        if(empty($this->VideoParam))
            $this->parseVideoParam();
        if(!in_array($this->StreamType,$this->VideoParam['typelist']))
            $this->StreamType = 'flv';
        $this->VideoUrl = 'http://v.youku.com/player/getM3U8/vid/' . $this->VideoParam['vid'] . '/type/' . $this->StreamType . '/sid/' . $this->VideoParam['sid'] . '/K/' . $this->VideoParam['key'] . '/video.m3u8';
        return $this->VideoUrl;
    }

    /**
     * 获取视频清晰度
     * @param $VideoIds
     * @return mixed
     */
    public function getStreamTypes($VideoIds)
    {
        $this->VideoIds = $VideoIds;
        if (empty($this->VideoParam))
            $this->parseVideoParam();
        return $this->VideoParam['typelist'];
    }

    /**
     * 清空当前类中的数据
     */
    public function clear(){
        $this->VideoIds = '';
        $this->StreamType = '';
        $this->VideoUrl='';
        $this->VideoParam=array();
    }
    /**
     * 分析视频参数
     */
    private function parseVideoParam()
    {
            $js = json_decode($this->getJs(), true);
            $this->VideoParam['vid'] = $js['data'][0]['videoid'];
            $this->VideoParam['typelist'] = $js['data'][0]['streamtypes'];
            $this->VideoParam['key']= self::getKey($js['data'][0]['key1'], $js['data'][0]['key2']);
            $this->VideoParam['sid']=self::sid();
    }

    /**
     * 生成sid
     * @return string
     */
    static function sid()
    {
        $sid = time() . (rand(0, 9000) + 10000);
        return $sid;
    }

    /**
     * 获得加密key的算法
     * @param $key1
     * @param $key2
     * @return string
     */
    static function getKey($key1, $key2)
    {
        $a = hexdec($key1);
        $b = $a ^ 0xA55AA5A5;
        $b = dechex($b);
        return $key2 . $b;
    }
}