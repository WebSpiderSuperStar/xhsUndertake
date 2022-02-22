<?php
/**
 * 小红书微信小程序抓取帖子评论方案（lv专用版方案），后续可接入代理池部署在服务器上，与普适版仅表有部分差异，具体注释参照普适版
 */
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
 * 获取lv id
 * @param $db
 * @param $flag
 * @return mixed
 */
function Get_LVnoteid($db, $flag)
{
    $start_date = date('Y-m-14', strtotime("-1 month"));
    $last_date = date('Y-m-14');
    $time_limit = "create_time >= '{$start_date}' AND create_time < '{$last_date}'";
    $sql = "SELECT note_id FROM xiaohongshu_comment_note where {$time_limit} and comment_flag in (0, {$flag}) order by comment_flag desc limit 1000;";
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    $update_arr = array();
    foreach ($result as $value) {
        $noteid = $value['note_id'];
        $update_arr[] = "'" . $noteid . "'";
    }
    if (!empty($update_arr)) {
        $update_str = implode(',', $update_arr);
        $sql = "update xiaohongshu_comment_note set comment_flag = {$flag} where note_id in ({$update_str});";
        $sth = $db->query($sql);
    }
    return $result;
}

/**
 * 更新状态
 * @param $db
 * @param $noteid
 * @param $flag
 */
function updateNoteFlag($db, $noteid, $flag)
{
    $sql = "update xiaohongshu_comment_note set comment_flag = {$flag} where note_id = '{$noteid}';";
    var_dump($sql);
    $sth = $db->query($sql);
}


function updateAuthorizationFlag($db, $Authorizationid, $flag)
{
    $sql = "update xhs_wx_authorization set effective_flag = {$flag} where id = {$Authorizationid};";
    var_dump($sql);
    $sth = $db->query($sql);
}

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

function curl_get($url, $header)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla%2F5.0+%28Linux%3B+Android+10%3B+ONEPLUS+A6000+Build%2FQKQ1.190716.003%3B+wv%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Version%2F4.0+Chrome%2F87.0.4280.101+Mobile+Safari%2F537.36 QQ/8.5.0.5025 V1_AND_SQ_8.5.0_1596_YYB_D QQ/MiniApp');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIE, '');
    // ignore SSL verify
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    return curl_exec($ch);
}

function getNoteCotent($noteid, $endid, $Authorization)
{
    $url = "https://www.xiaohongshu.com/fe_api/burdock/qq/v2/notes/{$noteid}/comments?pageSize=10&endId={$endid}";
    $x_sign = Get_xsign($noteid, $endid);
    $header = array(
        "authority: www.xiaohongshu.com",
        "Authorization: ${Authorization}",
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
    $data_json = !empty($content) ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : false;
    return $data_json;
}

var_dump(date("Y-m-d H:i:s"));
if ($argv[1]) {
    $flag = $argv[1];
} else {
    $flag = 2;
}
$notecount = 0;
$sql = "set names utf8mb4;";
$res = $db->query($sql);
$result = Get_LVnoteid($db, $flag);
while ($result) {
    foreach ($result as $value) {
        $temp = 1;
        $count = 0;
        $notecount++;
        $Authorizationid_arr = [
            'wxmp.25a1d7d4-5ced-4b1a-b037-a99be272c99c',
            'wxmp.8857a74d-14dc-4ed6-b157-3c8fb7df16de',
            'wxmp.03e2fd17-c98e-4fbc-bfa9-1f028f96d642',
            'wxmp.a009620c-7b90-4674-8418-19ef76334816',
            'wxmp.df473d8f-dbb0-43dc-8688-4b0eefc201eb',
        ];
        $Authorizationid = $Authorizationid_arr[mt_rand(0, count($Authorizationid_arr) - 1)];
        $noteid = $value['note_id'];
        $url = "https://www.xiaohongshu.com/fe_api/burdock/qq/v2/notes/{$noteid}/comments?pageSize=10";
        $endid = '';
        $x_sign = Get_xsign($noteid, $endid);
        $header = array(
            "authority: www.xiaohongshu.com",
            "Authorization: {$Authorizationid}",
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
        var_dump($header[1]);
        $content = curl_get($url, $header);
        try {
            $data_json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo "parse json while error:";
            var_dump($e);
        }
        $total_count = $data_json['data']['commentsLevel1Count'];
        $success_flag = $data_json['success'];
        $msg = $data_json['msg'];
        sleep(mt_rand(3, 5));
        if ($total_count === 0) {
            updateNoteFlag($db, $noteid, 1);
        } elseif ($success_flag == false) {
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
                var_dump($noteid);
                sleep(60);
                continue;
            } elseif ($msg == 'Spam') {
                checkAuthorizationFlag($db, $Authorizationid);
                continue;
            } elseif ($msg == 'Unauthorized') {
                checkAuthorizationFlag($db, $Authorizationid);
                continue;
            } else {
                var_dump($content);
                exit();
            }
        } elseif (empty($total_count)) {
            var_dump($content);
            exit();
        } else {
            var_dump($total_count);
            var_dump(date("Y-m-d H:i:s"));
            while ($count < $total_count) {
                var_dump($count);
                if ($temp == $count) {
                    var_dump($content);
                    checkAuthorizationFlag($db, $Authorizationid);
                    $data_json = getNoteCotent($noteid, $endid, $Authorizationid);
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
                    $sql_insert = "insert ignore into xiaohongshu_note_comment(note_id, comment_id, comment_content, comment_time, comment_nick, user_id, liked_count, sub_comment_count, target_comment_id) values {$comment_insert_str} on duplicate key update liked_count = values(liked_count), sub_comment_count = values(sub_comment_count);";
                    $sth_insert = $db->query($sql_insert);
                    $data_json = getNoteCotent($noteid, $endid, $Authorizationid);
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
            updateNoteFlag($db, $noteid, 1);
        }
    }
    $result = Get_LVnoteid($db, $flag);
}
