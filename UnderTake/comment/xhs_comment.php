<?php
/**
 * 小红书微信小程序抓取帖子评论方案（无代理普适版方案），后续可接入代理池部署在服务器上
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

/**
 * x-sign获取方法
 * @param $noteid
 * @param $endid
 * @return mixed
 */
function Get_xsign($noteid, $endid)
{
    $dir = __DIR__;
    if (empty($endid)) {
        exec("node \"{$dir}/test.js\" {$noteid}", $output);
    } else {
        exec("node \"{$dir}/test.js\" {$noteid} {$endid}", $output);
    }
    $x_sign = $output[0];
    return $x_sign;
}

/**
 * 从xhs_wx_authorization表中抽取authorization
 * @param $db
 * @param $authorizationid
 * @return mixed
 */
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

/**
 * 获取任务帖子id
 * @param $db
 * @param $flag
 * @return mixed
 */
function Get_noteid($db, $flag)
{
    $sql = "SELECT n.note_id
FROM opinion.xiaohongshu_comment_note_2 n
         join opinion.xiaohongshu_search_result t on t.note_id = n.note_id
where n.note_id <> ''
  and t.date > '20211222'
  and t.tag = ''
  and n.comment_flag <> 1;";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    $update_arr = array();
    foreach ($result as $value) {
        $noteid = $value['note_id'];
        $update_arr[] = "'" . $noteid . "'";
    }
    if (!empty($update_arr)) {
        $update_str = implode(',', $update_arr);
        $sql = "update xiaohongshu_comment_note_2 set comment_flag = {$flag} where note_id in ({$update_str});";
        $sth = $db->query($sql);
    }
    return $result;
}

/**
 * 更新帖子评论抓取任务flag
 * @param $db
 * @param $noteid
 * @param $flag
 */
function updateNoteFlag($db, $noteid, $flag)
{
    $sql = "update xiaohongshu_comment_note_2 set comment_flag = {$flag} where note_id = '{$noteid}';";
    var_dump($sql);
    $sth = $db->query($sql);
}

/**
 * 更新xhs_wx_authorization有效性flag
 * @param $db
 * @param $Authorizationid
 * @param $flag
 */
function updateAuthorizationFlag($db, $Authorizationid, $flag)
{
    $sql = "update xhs_wx_authorization set effective_flag = {$flag} where id = {$Authorizationid};";
    var_dump($sql);
    $sth = $db->query($sql);
}

/**
 * 间隔30s查看xhs_wx_authorization有效性变更
 * @param $db
 * @param $id
 * @return int|mixed
 */
function checkAuthorizationFlag($db, $id)
{
    $count = 0;
    $flag = 1;
    $sql = "update xhs_wx_authorization set effective_flag = 1 where id = {$id};";
    $sth = $db->query($sql);
    while (($flag == 1) && ($count < 20)) {
        $count++;
        $sql = "select effective_flag from xhs_wx_authorization where id = {$id};";
        var_dump($sql);
        $sth = $db->query($sql);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $flag = $result[0]['effective_flag'];
        sleep(30);
    }
    return $flag;
}

/**
 * @param $url
 * @param $header
 * @return bool|string
 */
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla%2F5.0+%28Linux%3B+Android+10%3B+ONEPLUS+A6000+Build%2FQKQ1.190716.003%3B+wv%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Version%2F4.0+Chrome%2F87.0.4280.101+Mobile+Safari%2F537.36 QQ/8.5.0.5025 V1_AND_SQ_8.5.0_1596_YYB_D QQ/MiniApp');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_COOKIE, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $detail = curl_exec($ch);
    return $detail;
    // $detail = mb_convert_encoding("$detail", "UTF-8", "GBK");
}

/**
 * 抓取帖子评论具体内容
 * @param $noteid
 * @param $endid
 * @param $Authorization
 * @return false|mixed
 */
function getNoteCotent($noteid, $endid, $Authorization)
{
    $url = "https://www.xiaohongshu.com/fe_api/burdock/qq/v2/notes/{$noteid}/comments?pageSize=10&endId={$endid}";
    $x_sign = Get_xsign($noteid, $endid);
    $header = array(
        "authority: www.xiaohongshu.com",
        "authorization: wxmp.8857a74d-14dc-4ed6-b157-3c8fb7df16de",
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
    );
    $content = curl_get($url, $header);
    if (!empty($content)) {
        $data_json = json_decode($content, true);
        return $data_json;
    } else {
        return false;
    }
}

var_dump(date("Y-m-d H:i:s"));
$flag = $argv[1];
echo 'flag:' . $flag . PHP_EOL;
$notecount = 0;
$result = Get_noteid($db, $flag);
while ($result) {
    sleep(mt_rand(3, 5));
    foreach ($result as $value) {
        $temp = 1;
        $count = 0;
        $notecount++;
        $authorizationid = 0;
        //获取Authorization
//        $Authorization_value = Get_Authorization($db, $authorizationid);
//        $Authorization = $Authorization_value['authorization'];
//        $Authorizationid = $Authorization_value['id'];
//        $Authorizationid = 2;
        $Authorizationid_arr = [
            'wxmp.cae1b1cf-cd6d-4072-a540-5a4c32aad184',
            'wxmp.ecfa3b20-9ecf-411f-8cc0-6ed4e560b06e',
            'wxmp.c707b15c-5c72-451c-8a33-0f226dd503f7',
            'wxmp.03e2fd17-c98e-4fbc-bfa9-1f028f96d642',
            'wxmp.a9ccfc91-69bf-4cf0-a14b-74cdfd87b503',
            'wxmp.8857a74d-14dc-4ed6-b157-3c8fb7df16de',
            'wxmp.a28c644d-fca1-4f8c-a01a-60d6da837f54',
            'wxmp.719f1c87-5ac9-424e-b4c6-1b6511a47d7d',
            'wxmp.c501e4f2-82cd-4d28-a977-e7e940d2a891',
        ];
        $Authorizationids = $Authorizationid_arr[mt_rand(0, count($Authorizationid_arr) - 1)];
        echo "Authorizationids:$Authorizationids" . PHP_EOL;
        var_dump($notecount);
//        var_dump($authorizationid);
//        var_dump($Authorization);
        $noteid = $value['note_id'];
        $url = "https://www.xiaohongshu.com/fe_api/burdock/qq/v2/notes/{$noteid}/comments?pageSize=10";
        $endid = '';
        $x_sign = Get_xsign($noteid, $endid);
        $header = array(
            "authority: www.xiaohongshu.com",
//            "authorization: wxmp.8857a74d-14dc-4ed6-b157-3c8fb7df16de",
            "authorization: {$Authorizationids}",
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
        $data_json = json_decode($content, true);
        //获取评论总数
        $total_count = $data_json['data']['commentsLevel1Count'];
        echo "total_count: $total_count" . PHP_EOL;
        $msg = $data_json['msg'];
        var_dump($msg);
        $success_flag = $data_json['success'];
        if ($total_count === 0) {
            //无评论直接结束
            updateNoteFlag($db, $noteid, 1);
        } elseif ($success_flag == false) {
            //异常情况处理
            if ($msg == 'note censored') {
                updateNoteFlag($db, $noteid, 100);
            } elseif ($msg == 'note not enabled') {
                updateNoteFlag($db, $noteid, 101);
            } elseif ($msg == 'Internal error processing note_comment_list_v2') {
                var_dump($noteid);
                continue;
            } elseif ($msg == 'Internal error processing get_user_base_info_by_id_list') {
                var_dump($noteid);
                continue;
            } elseif ($msg == '登录已过期') {
                checkAuthorizationFlag($db, $Authorizationids);
                continue;
            } elseif ($msg == 'Spam') {
                var_dump("start process");
                // remote_ip = http://203.156.218.106:5002/sm_captcha
                // local = http://10.21.200.12:1212/sm_captcha
                $verify = singleConfigurableScrape([
                    CURLOPT_URL => 'http://10.21.60.140:1212/sm_captcha',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => 'gzip',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_POSTFIELDS => ['authorization' => "{$Authorizationid}"],
                    CURLOPT_CUSTOMREQUEST => strtoupper('get'),
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ]);
                var_dump($verify);
                sleep(mt_rand(3, 5));
                continue;
            } elseif ($msg == 'Unauthorized') {
                checkAuthorizationFlag($db, $Authorizationid);
                continue;
            } else {
                var_dump($content);
                exit();
            }
        } elseif (empty($total_count)) {
            //未获取到评论数打印内容并结束处理
            var_dump($content);
            exit();
        } else {
            //有评论继续抓取后续评论
            var_dump($total_count);
            var_dump(date("Y-m-d H:i:s"));
            while ($count < $total_count) {
                var_dump($count);
                //评论数不增长且接口无内容则认为评论已完全遍历
                if ($temp == $count) {
                    var_dump($content);
                    checkAuthorizationFlag($db, $Authorizationid);
                    $data_json = getNoteCotent($noteid, $endid, $Authorization);
                    if (empty($content)) {
                        continue 2;
                    }
                } else {
                    $temp = $count;
                }
                $comment_insert_arr = array();
                $comment_arr = $data_json['data']['comments'];
                $success_flag = $data_json['success'];
                $msg = $data_json['msg'];
                //评论列表处理
                if (!empty($comment_arr)) {
                    foreach ($comment_arr as $comment_data) {
                        $count = $count + 1;
                        $endid = $comment_data['id'];
                        $comment_id = $comment_data['id'];
                        $comment_content = $comment_data['content'];
                        $comment_time = $comment_data['time'];
                        $comment_nick = $comment_data['user']['nickname'];
                        $user_id = $comment_data['user']['id'];
                        $liked_count = $comment_data['likes'];
                        $sub_comment_count = $comment_data['subCommentsTotal'];
                        $sub_comment_arr = $comment_data['subComments'];
                        $targetNoteId = $comment_data['targetNoteId'];
                        $comment_insert_arr[] = "('{$targetNoteId}', '{$comment_id}', '{$comment_content}', '{$comment_time}', '{$comment_nick}', '{$user_id}', {$liked_count}, {$sub_comment_count}, '{$comment_id}')";
//                        echo 'comment_arr' . PHP_EOL;
//                        var_dump($comment_arr);

                        //追评处理(微信小程序上一般是3条)
                        if (!empty($sub_comment_arr)) {
                            foreach ($sub_comment_arr as $sub_comment_data) {
                                $sub_comment_id = $sub_comment_data['id'];
                                $sub_comment_content = $sub_comment_data['content'];
                                $sub_comment_time = $sub_comment_data['time'];
                                $sub_comment_nick = $sub_comment_data['user']['nickname'];
                                $sub_user_id = $sub_comment_data['user']['id'];
                                $sub_liked_count = $sub_comment_data['likes'];
                                $targetCommentId = $sub_comment_data['targetCommentId'];
                                $comment_insert_arr[] = "('{$targetNoteId}', '{$sub_comment_id}', '{$sub_comment_content}', '{$sub_comment_time}', '{$sub_comment_nick}', '{$sub_user_id}', {$sub_liked_count}, 0, '{$targetCommentId}')";
                            }
                        }
                    }
                    $comment_insert_str = implode(',', $comment_insert_arr);
                    $sql_insert = "insert ignore into xiaohongshu_note_comment2(note_id, comment_id, comment_content, comment_time, comment_nick, user_id, liked_count, sub_comment_count, target_comment_id) values {$comment_insert_str} on duplicate key update liked_count = values(liked_count), sub_comment_count = values(sub_comment_count);";
                    $sth_insert = $db->query($sql_insert);
                    $data_json = getNoteCotent($noteid, $endid, $Authorizationids);
                    // 监控抓入
                    $sql_query = "insert xiaohongshu_command_statistics(note_id, num) values('{$noteid}', '{$liked_count}')";
                    $db->query($sql_query);

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
            //帖子评论flag更新
            updateNoteFlag($db, $noteid, 1);
            var_dump('5:' . time());
        }
    }
    $result = Get_noteid($db, $flag);
}