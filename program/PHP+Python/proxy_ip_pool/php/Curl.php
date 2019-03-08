<?php

class Curl
{
    private $ch;
    private $url = "http://www.baidu.com";
    private $flag_if_have_run;   //标记exec是否已经运行
    private $set_time_out = 200;  //设置curl超时时间
    private $cookie_file = "cookie.txt";  //cookie_file路径
    private $show_header = 0;    //是否输出返回头信息
    private $set_useragent = ""; //模拟用户使用的浏览器，默认为模拟

    //构造函数
    public function __construct($url = ""){
        $this->ch = curl_init();
        $this->url = $url ? $url : $this->url;
        //$this->set_useragent = $_SERVER['HTTP_USER_AGENT']; // 模拟用户使用的浏览器
        $this->set_useragent ="Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/56.0.2924.87 Safari/537.36";
        // $this->set_useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.143 Safari/537.36";
        //$this->cookie_file=dirname(__FILE__)."/cookie_".md5(basename(__FILE__)).".txt";    //初始化cookie文件路径
        //$this->cookie_file= SAE_TMP_PATH.TmpFS;
    }

    //设置cookie文件
    public function set_cookie_file($file = "cookie.txt"){
        $this->cookie_file = $file;
    }
    //设置超时
    public function set_time_out($timeout=20){
        if(intval($timeout) != 0)
            $this->set_time_out = $timeout;
        return $this;
    }
    //设置来源页面
    public function set_referer($referer = ""){
        if (!empty($referer))
            curl_setopt($this->ch, CURLOPT_REFERER , $referer);
        return $this;
    }
    //设置cookie存放模式 1客户端、2服务器文件
    public function set_cookie_mode($mode = ""){
        $this->cookie_mode = $mode;
        return $this;
    }
    //载入cookie
    public function load_cookie(){
        curl_setopt($this->ch, CURLOPT_COOKIEFILE , $this->cookie_file);
        return $this;
    }
    public function save_cookie(){
        if ($this->cookie_file){
            curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookie_file);//把返回来的cookie信息保存在$cookie_jar文件中
        }
        return $this;
    }


    //post参数 (array) $post
    public function post ($post = ""){
        if($post && is_array($post)){
            curl_setopt($this->ch, CURLOPT_POST , 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS , http_build_query($post) );
        }
        return $this;
    }
    //设置代理 ,例如'68.119.83.81:27977'
    public function set_proxy($proxy = ""){
        if($proxy){
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($this->ch, CURLOPT_PROXY,$proxy);
        }
        return $this;
    }
    //设置伪造ip
    public function set_ip($ip=""){
        if(!empty($ip))
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("X-FORWARDED-FOR:$ip", "CLIENT-IP:$ip"));
        return $ip;
    }
    //传输格式设置
    public function setHeader($header){
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        return $this;
    }
    //设置是否显示返回头信息
    public function show_header($show=0){
        $this->show_header = 0;
        if($show)
            $this->show_header = 1;
        return $this;
    }

    //设置请求头信息
    public function set_useragent($str=""){
        if($str)
            $this->set_useragent = $str;
        return $this;
    }

    //执行
    public function exec ($url = ""){
        if(!$url) $url = $this->url;
        curl_setopt($this->ch, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER , 1 );    //获取的信息以文件流的形式返回
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->set_useragent); // 模拟用户使用的浏览器
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->set_time_out);  //超时设置
        curl_setopt($this->ch, CURLOPT_HEADER, $this->show_header); // 显示返回的Header区域内容
        curl_setopt($this->ch, CURLOPT_NOBODY, 0);//不返回response body内容

        $res = curl_exec($this->ch);

        $this->flag_if_have_run = true;
        if (curl_errno($this->ch)) {
            echo 'Errno '.curl_error($this->ch).PHP_EOL;
            return false;
        }
        if($this->show_header == 1){ //数组形式返回头信息和body信息
            list($header, $body) = explode("\r\n\r\n", $res);
            $arr['header'] = $header;
            $arr['body'] = $body;
            if($arr) return $arr;
        }
        curl_close($this->ch);
        return $res;
    }


    //返回  curl_getinfo信息
    public function get_info(){
        if($this->flag_if_have_run == true )
            return curl_getinfo($this->ch);
        else
            throw new Exception("<h1>需先运行( 执行exec )，再获取信息</h1>");
    }
}
