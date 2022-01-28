<?php
/**
 * 小红书抓取话题下帖子方案（普适版方案），后续可接入代理池部署在服务器上
 */
//opinion真服数据库连接
$dsn = 'mysql:dbname=opinion;host=10.21.200.48;port=3306;';
$user = 'opinion';
$password = 'vDGM0lspmy=';
try {
    $db = new PDO($dsn, $user, $password, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8mb4",
        PDO::ATTR_PERSISTENT => true
    ));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    sleep(10);
}

//x-sign获取方法
function Get_xsign($url)
{
    $dir = __DIR__;
    var_dump($url);
    if (!empty($url)) {
        $url = $url . 'WSUDD';
        var_dump($url);
        exec("node \"{$dir}/xsign.js\" \"{$url}\"", $output);
    } else {
        return false;
    }
    $x_sign = $output[0];
    return $x_sign;
}

//curl方法
function curl_get($url, $header)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    // curl_setopt($ch, CURLOPT_HEADER, 1);
    // curl_setopt($ch, CURLOPT_, 1);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, 'version=3.4.1&spuId=kingPower_mall@367');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_COOKIE, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $detail = curl_exec($ch);
    return $detail;
    // $detail = mb_convert_encoding("$detail", "UTF-8", "GBK");
}

var_dump(date("Y-m-d H:i:s"));
$noteid_arr = array();
$url_front = "https://www.xiaohongshu.com";
//当前看这个接口只能抓到99页,目前只有个简单的处理逻辑，后续需要优化
for ($page = 1; $page < 100; $page++){
    $url_back = "/fe_api/burdock/v2/page/{$topic}/notes?page={$page}&page_size=6&sort=hot";
    $url = $url_front . $url_back;
    $x_sign = Get_xsign($url_back);
    print ($x_sign);
    $header = array(
        "authority: www.xiaohongshu.com",
        "pragma: no-cache",
        "cache-control: no-cache",
        'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="90", "Google Chrome";v="90"',
        "accept: application/json, text/plain, */*",
        "x-sign: {$x_sign}",
        'sec-ch-ua-mobile: ?0',
        "sec-fetch-site: same-origin",
        "sec-fetch-mode: cors",
        "sec-fetch-dest: empty",
        "accept-language: zh-CN,zh;q=0.9",
        "Connection: Keep-Alive",
        "content-type: application/json",
        "Host: www.xiaohongshu.com",
        "referer: https://www.xiaohongshu.com/page/topics/v2/{$topic}"
    );
    $content = curl_get($url, $header);
    $data_json = json_decode($content, true);
	//数据处理
    if(!empty($data_json)){
        $insert_arr = array();
        $insert_str = '';
        $notelist = $data_json['data']['noteList'];
        if(!empty($notelist)){
            foreach ($notelist as $note){
                $note_id = $note['id'];
                $noteid_arr[] = $note_id;
                $type = $note['type'];
                $title = $note['title'];
                $create_time = $note['time'];
                $liked_count = $note['likes'];
                $user_id = $note['user']['id'];
                $user_name = $note['user']['nickname'];
                $insert_arr[] = "({$db->quote($note_id)}, {$db->quote($type)}, {$db->quote($title)}, {$db->quote($create_time)}, {$db->quote($user_name)}, {$db->quote($user_id)}, {$db->quote($liked_count)}, 110, 110)";
            }
            if(!empty($insert_arr)){
                var_dump($insert_arr);
                if(sizeof($insert_arr) > 1){
                    $insert_str = implode(',', $insert_arr);
                } else {
                    $insert_str = $insert_arr[0];
                }
                $sql = "insert ignore into xiaohongshu_comment_note_2(id, type, title, create_time, user_name, user_id, liked_count, content_flag, flag) values {$insert_str} on duplicate key update flag = values(flag);";
                $sql = preg_replace_callback(
                    '/./u',
                    function (array $match) {
                        return strlen($match[0]) >= 4 ? '' : $match[0];
                    },
                    $sql);
                var_dump($sql);
                $sth = $db->query($sql);
            }
        } else {
            var_dump($url);
            var_dump($content);
            var_dump($noteid_arr);
            exit();
        }
    } else {
        var_dump($url);
        var_dump($content);
        var_dump($noteid_arr);
        exit();
    }
    sleep(3);
}
var_dump($noteid_arr);