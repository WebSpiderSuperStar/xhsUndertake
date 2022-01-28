<?php
/**
 * 小红书微信小程序抓取用户主页帖子（普适版方案），后续可接入代理池部署在服务器上
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
function Get_xsign($userid, $page)
{
    $dir = __DIR__;
    if (empty($page)) {
        exec("node \"{$dir}/test2.js\" {$userid}", $output);
    } else {
        exec("node \"{$dir}/test2.js\" {$userid} {$page}", $output);
    }
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

//根据分组抽取用户id
function Get_userid($db, $type)
{
//     $sql = "select user_id FROM xiaohongshu_pgy_kol where update_flag = 0 and type like '{$type}%' order by fansCount desc limit 10;";
//     $sql = "select f.user_id from xiaohongshu_user_follow f join xiaohongshu_note_usr2 u on f.user_id = u.user_id where u.note_count > 0 and u.flag <99 group by user_id order by u.note_count desc limit 1;";
//     $sql = "select user_id from xiaohongshu_note_usr2 where flag = 98;";
    $sql = "select user_id from xiaohongshu_pgy_kol where update_flag = 1;";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    // $update_arr = array();
    // foreach ($result as $value) {
    // $userid = $value['user_id'];
    // $update_arr[] = "'" . $userid . "'";
    // }
    // if (! empty($update_arr)) {
    // $update_str = implode(',', $update_arr);
    // $sql = "update xiaohongshu_pgy_kol set update_flag = {$flag} where user_id in ({$update_str});";
    // $sth = $db->query($sql);
    // }
    return $result;
}

//更新用户抓取flag
function updateUserFlag($db, $userid, $flag)
{
    $sql = "update xiaohongshu_pgy_kol set update_flag = {$flag} where user_id = '{$userid}';";
//     $sql = "update xiaohongshu_note_usr2 set flag = {$flag} where user_id = '{$userid}';";
    var_dump($sql);
    $sth = $db->query($sql);
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
        $count++;
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

//获取用户主页内容
function getUserCotent($userid, $page, $Authorization)
{
    $url = "https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/user/{$userid}/notes?page={$page}&page_size=15";
    $x_sign = Get_xsign($userid, $page);
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
        "referer: https://appservice.qq.com/1108221428/2.18.1/page-frame.html"
    );
    $content = curl_get($url, $header);
    if (!empty($content)) {
        $data_json = json_decode($content, true);
        return $data_json;
    } else {
        return false;
    }
}

// $type_array = array(
//     '美妆',
//     '时尚',
//     '母婴',
//     '美食',
//     '出行',
//     '影视',
//     '摄影',
//     '搞笑',
//     '娱乐',
//     '家居家装',
//     '生活记录',
//     '萌娃',
//     '知识',
//     '宠物',
//     '运动健身',
//     '情感',
//     '教育',
//     '兴趣爱好',
//     '文化',
//     '游戏',
//     '健康',
//     '减肥',
//     '资讯',
//     '科技数码',
//     '星座命理',
//     '汽车',
//     '素材',
//     '体育赛事',
//     '婚嫁',
//     '文化娱乐',
//     '商业财经',
//     '社科',
//     '潮流'
// );
$type_array = array(
    '美妆'
);
//根据分组类型抽取用户id，后续可优化
foreach ($type_array as $type) {
    var_dump(date("Y-m-d H:i:s"));
    $notecount = 0;
    $result = Get_userid($db, $type);
    var_dump($type);
    var_dump($result);
    foreach ($result as $value) {
        $temp = -1;
        $count = 0;
        $notecount++;
        $authorizationid = intval($notecount / 10) % 2;
        var_dump('1:' . time());
        $Authorization_value = Get_Authorization($db, $authorizationid);
        $Authorization = $Authorization_value['authorization'];
        $Authorizationid = $Authorization_value['id'];
        var_dump($notecount);
        var_dump($authorizationid);
        var_dump($Authorization);
        $userid = $value['user_id'];
        var_dump($userid);
        $page = 0;
        //先获取用户帖子总页数，再遍历，目前每页6条帖子
        $url = "https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/user/{$userid}";
        $endid = '';
        $x_sign = Get_xsign($userid, $page);
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
        var_dump($userid);
        sleep(1);
        $data_json = json_decode($content, true);
        $total_count = $data_json['data']['notes'];
        $total_page = ceil($total_count / 6);
//         if($total_page > 3){
//             $total_page = 3;
//         }
        $success_flag = $data_json['success'];
        $msg = $data_json['msg'];
        var_dump($msg);
        if ($total_count === 0) {
            updateUserFlag($db, $userid, 99);
        } elseif ($success_flag == false) {
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
        } elseif (empty($total_count)) {
            var_dump($content);
            exit();
        } else {
            var_dump($total_page);
            var_dump('3:' . time());
            var_dump(date("Y-m-d H:i:s"));
            $page = 1;
            $data_json = getUserCotent($userid, $page, $Authorization);
            var_dump($data_json);
            while ($page <= $total_page) {
                var_dump($count);
                if ($temp == $page) {
                    var_dump($content);
                    checkAuthorizationFlag($db, $Authorizationid);
                    $data_json = getUserCotent($userid, $page, $Authorization);
                    if (empty($data_json)) {
                        break;
                    }
                } else {
                    $temp = $page;
                }
                $sleeptime_add = rand(0, 9);
                $sleeptime = 3 + $sleeptime_add / 10;
                sleep($sleeptime);
                $note_insert_arr = array();
                $note_arr = $data_json['data'];
                $success_flag = $data_json['success'];
                $msg = $data_json['msg'];
                var_dump($note_arr);
                if (!empty($note_arr)) {
                    foreach ($note_arr as $note_data) {
                        $count = $count + 1;
                        $noteid = $note_data['id'];
                        $title = $note_data['title'];
                        $create_time = $note_data['time'];
                        $liked_count = $note_data['likes'];
                        $note_type = $note_data['type'];
                        $user_id = $note_data['user']['id'];
                        $user_name = $note_data['user']['nickname'];
                        $note_insert_arr[] = "('{$noteid}', '{$note_type}', '{$title}', '{$create_time}', {$liked_count}, '{$user_id}', '{$user_name}')";
                    }
                    $note_insert_str = implode(',', $note_insert_arr);
                    $sql_insert = "insert ignore into xiaohongshu_comment_note_2(id, type, title, create_time, liked_count, user_id, user_name) values {$note_insert_str} on duplicate key update liked_count = values(liked_count);";
                    var_dump($sql_insert);
                    $sth_insert = $db->query($sql_insert);
                    $page++;
                    $data_json = getUserCotent($userid, $page, $Authorization);
                    if (empty($content)) {
                        continue 2;
                    }
                } elseif ($success_flag == false) {
                    if ($msg == 'Internal error processing note_comment_list_v2') {
                        var_dump($noteid);
                        continue 2;
                    } elseif ($msg == 'Internal error processing get_user_base_info_by_id_list') {
                        var_dump($noteid);
                        continue 2;
                    } elseif ($msg == 'rpc timeout') {
                        var_dump($noteid);
                        continue 2;
                    } elseif ($msg == '登录已过期') {
                        var_dump($noteid);
                        continue 2;
                    }
                } elseif ($data_json['data']['commentsTotal'] == 0) {
                    break;
                } else {
                    var_dump($content);
                    var_dump(date("Y-m-d H:i:s"));
                }
            }
            var_dump('4:' . time());
            updateUserFlag($db, $userid, 99);
            var_dump('5:' . time());
        }
    }
}