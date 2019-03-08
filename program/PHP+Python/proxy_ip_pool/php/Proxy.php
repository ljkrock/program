<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/5
 * Time: 12:48
 */
header("Content-type:text/html;charset=gbk");
include_once 'Curl.php';
class Proxy
{
    private $url = 'http://www.xiladaili.com/gaoni/';//西拉免费代理IP
    private $check_url = 'https://www.ip.cn/';
    private $ip_list = [];

    /**
     * 获取每一页的ip数据
     * @param $url
     * @return mixed
     */
    public function getHtml($url){
        //curl类
        $ch = new Curl($url);
        //访问并返回网页代码
        $html = $ch->exec();
        //获取数据列表
        $tableHtmlPreg = '/<tbody>([\s\S]*?)<\/tbody>/';//列表正则
        $res = preg_match($tableHtmlPreg,$html,$arr);
        if ($arr){
            $tableHtml = $arr[1];
        }
        //匹配这个列表里所有ip，并返回
        $ipPreg = '/<tr>[\s\S]*?<td>(?P<ip>[\s\S]*?)<\/td>[\s\S]*?<\/tr>/';
        preg_match_all($ipPreg,$tableHtml,$trs,PREG_SET_ORDER);
        array_map(function ($v){
            return [
                'ip'=>$v['ip'],
            ];
        },$trs);
        return $trs;
    }
    public function collect() {
        //连接数据库
        $conn = @new mysqli("127.0.0.1","root","root","proxy");
        if($conn->connect_errno){ //返回链接错误号
            // 返回链接错误信息
            die("数据库链接失败：".$conn->connect_error);
        }
        $conn->set_charset("utf8") or die("设置字符集失败：".$conn->error);
        //循环提取每一页数据，并写入数据表 `proxy`
        $insertSql = 'insert into proxy ("ip","port","type") values ';
        for ($i=1;$i<3000;$i++){
            $trs = $this->getHtml($this->url.$i);
            foreach ($trs as $key=>$tr){//循环每一页的ip列表
                //查询是否存在
                $sql = 'select * from proxy where ip="'.$tr['ip'].'"';
                $res = $conn->query($sql);
                if ($res->fetch_assoc()){
                    continue;
                }
                //插入数据
                $insertSql = 'insert into proxy (`ip`,`port`,`type`) values ("'.$tr['ip'].'",0,"no");';
                echo '第'.$i.'页 第'.$key.'条： '.$insertSql.PHP_EOL;
                $conn->query($insertSql);
            }

        }
        $conn->close();
    }
}
$proxy =  new Proxy();
$proxy->collect();
