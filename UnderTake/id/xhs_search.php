<?php
/**
 * 小红书微信小程序抓取搜索结果（普适版方案），后续可接入代理池部署在服务器上
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
    exec("node \"{$dir}/xsign.js\" \"{$url}\"", $output);
    $x_sign = $output[0];
    return $x_sign;
}

//从xhs_wx_authorization表中抽取authorization
function Get_Authorization($db, $authorizationid)
{
    $sql = "select id,authorization from xhs_wx_authorization where owner = 'yx' and effective_flag = 0;";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    $Authorization_array = array();
    foreach ($result as $value) {
        $Authorization_array[] = $value;
    }
    return $Authorization_array[$authorizationid];
}

//更新xhs_wx_authorization有效性flag
function updateAuthorizationFlag($db, $Authorizationid, $flag)
{
    $sql = "update xhs_wx_authorization set effective_flag = {$flag} where id = {$Authorizationid};";
    var_dump($sql);
    $sth = $db->query($sql);
}

//间隔30s查看xhs_wx_authorization有效性变更
function checkAuthorizationFlag($db, $id)
{
    $count = 0;
    $flag = 1;
    $sql = "update xhs_wx_authorization set effective_flag = 1 where id = {$id};";
    $sth = $db->query($sql);
    while (($flag == 1) && ($count < 20)) {
        $count ++;
        $sql = "select effective_flag from xhs_wx_authorization where id = {$id};";
        $sth = $db->query($sql);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $flag = $result[0]['effective_flag'];
        sleep(30);
    }
    return $flag;
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_COOKIE, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $detail = curl_exec($ch);
    return $detail;
    // $detail = mb_convert_encoding("$detail", "UTF-8", "GBK");
}

//获取搜索关键词
function Get_keyword($db, $tag)
{
    $sql = "SELECT id,keyword FROM opinion.xiaohongshu_search_tag where tag = '祛疤膏' and flag = 0 order by id;";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

//更新搜索状态
function UpdateTagFlag($db, $id)
{
    $sql = "update xiaohongshu_search_tag set flag = 1 where id = {$id};";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

var_dump(date("Y-m-d H:i:s"));
//通过搜索品牌获取搜索关键词，这个逻辑后续需要优化
$tag = '祛疤膏';
$result = Get_keyword($db, $tag);
$date = date("Ymd");
foreach ($result as $value) {
    $count = 0;
    $authorizationid = $page % 2;
    var_dump('1:' . time());
    $Authorization_value = Get_Authorization($db, $authorizationid);
    $Authorization = $Authorization_value['authorization'];
    $Authorizationid = $Authorization_value['id'];
    var_dump($authorizationid);
    var_dump($Authorization);
    $id = $value['id'];
    $keyword = $value['keyword'];
    $keyword = urlencode($keyword);
    var_dump($keyword);
	//数据处理，目前小程序搜索只能拿到5页数据
    for ($page = 1; $page <= 5; $page ++) {
        $url = "https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/search/notes?keyword={$keyword}&sortBy=create_time_desc&page={$page}&pageSize=20&prependNoteIds=&needGifCover=true";
        $url_xsign = "/fe_api/burdock/weixin/v2/search/notes?keyword={$keyword}&sortBy=create_time_desc&page={$page}&pageSize=20&prependNoteIds=&needGifCover=trueWSUDD";
        $x_sign = Get_xsign($url_xsign);
        var_dump($x_sign);
        $header = array(
            "authority: www.xiaohongshu.com",
            "Authorization: wxmp.c694df92-178c-4196-a498-0a8bd616c4e8",
            "pragma: no-cache",
            "cache-control: no-cache",
            "accept: application/json, text/plain, */*",
            "x-sign: {$x_sign}",
            "sec-fetch-site: same-origin",
            "sec-fetch-mode: cors",
            "sec-fetch-dest: empty",
            "accept-language: zh-CN,zh;q=0.9",
            "Connection: Keep-Alive",
            "content-type: application/json",
            "Host: www.xiaohongshu.com",
            "referer: https://servicewechat.com/wxb296433268a1c654/24/page-frame.html"
        );
        $content = curl_get($url, $header);
        var_dump('2:' . time());
        var_dump($content);
        sleep(1);
        $data_json = json_decode($content, true);
        $success_flag = $data_json['success'];
        $msg = $data_json['msg'];
        var_dump($msg);
        $sql_note = '';
        $sql_tag = '';
        if ($success_flag == false) {
            if ($msg == 'note censored') {
                updateUserFlag($db, $userid, 102);
            } elseif ($msg == 'note not enabled') {
                updateUserFlag($db, $userid, 103);
            } elseif ($msg == 'Internal error processing note_comment_list_v2') {
                var_dump($userid);
                continue;
            } elseif ($msg == 'Internal error processing get_user_base_info_by_id_list') {
                var_dump($userid);
                continue;
            } elseif ($msg == '登录已过期') {
                var_dump($userid);
                sleep(60);
                continue;
            } elseif ($msg == 'Spam') {
                checkAuthorizationFlag($db, $Authorizationid);
                continue;
            } elseif ($msg == '用户被封号') {
                updateUserFlag($db, $userid, 104);
                continue;
            } else {
                var_dump($content);
                exit();
            }
        } else {
            $notelist = isset($data_json['data']['notes']) ? $data_json['data']['notes'] : array();
            $note_insertarr = array();
            $tag_insertarr = array();
            if (! empty($notelist)) {
                foreach ($notelist as $note) {
                    $noteid = $note['id'];
                    $like_count = $note['likes'];
                    $create_time = $note['time'];
                    $title = $note['title'];
                    $type = $note['type'];
                    $userid = $note['user']['id'];
                    $nickname = $note['user']['nickname'];
                    $note_insertarr[] = "('{$noteid}', '{$type}', '{$title}', '{$create_time}', '{$nickname}', '{$userid}', '{$like_count}')";
                    $tag_insertarr[] = "('{$tag}', '{$value['keyword']}', {$date}, '{$noteid}')";
                }
                if (! empty($note_insertarr)) {
                    $note_insertstr = implode(',', $note_insertarr);
                    $sql_note = "insert ignore into xiaohongshu_comment_note_2(id, type, title, create_time, user_name, user_id, liked_count) values {$note_insertstr} on duplicate key update create_time = values(create_time);";
                    $sth = $db->query($sql_note);
                    $tag_insertstr = implode(',', $tag_insertarr);
                    $sql_tag = "insert ignore into xiaohongshu_search_result(tag, keyword, date, id) values {$tag_insertstr};";
                    $sth = $db->query($sql_tag);
                }
            } else {
                break;
            }
            var_dump($sql_note);
            var_dump($sql_tag);
        }
    }
    UpdateTagFlag($db, $id);
}