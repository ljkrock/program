<?php
include_once 'Curl.php';
$conn = @new mysqli("127.0.0.1","root","root","proxy");
if($conn->connect_errno){ //返回链接错误号
// 返回链接错误信息
    die("数据库链接失败：".$conn->connect_error);
}
$conn->set_charset("utf8") or die("设置字符集失败：".$conn->error);
$sql = 'select * from available;';
$res = $conn->query($sql);
$res = $res->fetch_all();
foreach ($res as $row){
    $ch = new Curl('https://www.ip.cn/');
    echo $row[1];
    $ch->set_proxy($row[1]);
    $ch->set_time_out(3);
    try{
        $html = $ch->exec();
    }
    catch (Exception $e){
        var_dump($e);
    }
    if (!$html){
        $sql = 'update available set weight=weight-1 where ip="'.$row[1].'";';
        $res = $conn->query($sql);
    }
    if (strpos($html,'class="well"')){
        echo '打开成功'.PHP_EOL;
        $sql = 'update available set weight=weight+1 where ip="'.$row[1].'";';
        $res = $conn->query($sql);
    }
}
$conn->close();
