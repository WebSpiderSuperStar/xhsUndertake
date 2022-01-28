<?php
/**
 * 小红书微信小程序抓取帖子转评赞数方案（普适版方案），后续可接入代理池部署在服务器上，与普适版仅表有部分差异，具体注释参照普适版
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

function Get_xsign($noteid)
{
    $dir = __DIR__;
    exec("node \"{$dir}/test3.js\" {$noteid}", $output);
    $x_sign = $output[0];
    return $x_sign;
}


function singleConfigurableScrape($request_config)
{
    $curl = curl_init();

    curl_setopt_array($curl, $request_config);

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

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
}

function getUserCotent($userid, $page, $Authorization)
{
//    $url =
    $x_sign = Get_xsign($userid, $page);
    $content = singleConfigurableScrape([
        CURLOPT_URL => "https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/user/{$userid}/notes?page={$page}&page_size=15",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => strtoupper('get'),
        CURLOPT_HTTPHEADER => array(
            "authority: www.xiaohongshu.com",
            "authorization: $Authorization",
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
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    if (!empty($content)) {
        $data_json = json_decode($content, true);
        return $data_json;
    } else {
        return false;
    }
}

function Get_noteid($db)
{
    $sql = "SELECT n.note_id
FROM opinion.xiaohongshu_comment_note_2 n
         join opinion.xiaohongshu_search_result t on t.note_id = n.note_id
where n.note_id <> ''
  and t.date > '20211222'
  and t.tag = ''
    and liked_count=0;";
    var_dump($sql);
    $sth = $db->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

var_dump(date("Y-m-d H:i:s"));
$notecount = 0;
$sql = "set names utf8mb4;";
$res = $db->query($sql);
$result = Get_noteid($db, 111);
foreach ($result as $value) {
//    mt_rand(3, 5);
    $count = 0;
    $notecount++;
//    $authorizationid = intval($notecount / 10) % 2;
//    $Authorization_value = Get_Authorization($db, $authorizationid);
//    $Authorization = $Authorization_value['authorization'];
//    $Authorizationid = $Authorization_value['id'];
//    echo "---authorizationid---";
//    var_dump($authorizationid);
//    echo "---Authorization---";
//    var_dump($Authorization);
//    echo "---noteid---";
    $Authorizationid_arr = [
        'wxmp.cae1b1cf-cd6d-4072-a540-5a4c32aad184',
        'wxmp.ecfa3b20-9ecf-411f-8cc0-6ed4e560b06e',
        'wxmp.c707b15c-5c72-451c-8a33-0f226dd503f7',
        'wxmp.03e2fd17-c98e-4fbc-bfa9-1f028f96d642',
        'wxmp.a9ccfc91-69bf-4cf0-a14b-74cdfd87b503',
        'wxmp.8857a74d-14dc-4ed6-b157-3c8fb7df16de',
//        'wxmp.2fbb5b6b-230e-4022-a939-10446069571c',
        'wxmp.a28c644d-fca1-4f8c-a01a-60d6da837f54',
        'wxmp.719f1c87-5ac9-424e-b4c6-1b6511a47d7d',
        'wxmp.c501e4f2-82cd-4d28-a977-e7e940d2a891',
    ];
    $Authorizationid = $Authorizationid_arr[mt_rand(0, count($Authorizationid_arr) - 1)];
    echo "Authorizationid:$Authorizationid" . PHP_EOL;
    $noteid = $value['note_id'];
    var_dump($noteid);
    $page = 0;
    $x_sign = Get_xsign($noteid);
    $content = curl_get("https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/note/{$noteid}/single_feed", array(
        "authority: www.xiaohongshu.com",
        "authorization: {$Authorizationid}",
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
    ));
    var_dump('2:' . time());
    var_dump($noteid);
    sleep(mt_rand(1, 3));
    $data_json = json_decode($content, true);

    $success_flag = $data_json['success'];
    $msg = $data_json['msg'] ? $data_json['msg'] : null;
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
//            sleep(60);
            continue;
        } elseif ($msg == 'Spam') {
            var_dump("start process");
            // remote_ip = http://203.156.218.106:5002/sm_captcha
            // local = http://10.21.200.12:1212/sm_captcha
            $token = singleConfigurableScrape([
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
            // var_dump($verify);
            $a = json_decode($token);
            $process = singleConfigurableScrape([
                CURLOPT_URL => 'https://www.xiaohongshu.com/fe_api/burdock/weixin/v2/shield/captchaV2',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => 'gzip',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => [
                    "rid: {$a['rid']}",
                    "status: 1",
                    "callFrom: wxMiniProgram"
                ],
                CURLOPT_CUSTOMREQUEST => strtoupper('post'),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HEADER => array(
                    "Host: www.xiaohongshu.com",
                    "authority: www.xiaohongshu.com",
                    "x-sign: {$x_sign}",
                    "pragma: no-cache",
                    "authorization: {$a['authorization']}",
                    "cache-control: no-cache",
                    "accept: application/json, text/plain, */*",
                    "content-type: application/json",
                    "sec-fetch-site: same-origin",
                    "sec-fetch-mode: cors",
                    "sec-fetch-dest: empty",
                    "accept-language: zh-CN,zh;q=0.9",
                    "Connection: Keep-Alive",
                    "content-type: application/json",
                    "device-fingerprint: {$a['deviceId']}",
                    "User-Agent: Mozilla/5.0 (Linux; Android 6.0.1; Nexus 6P Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/86.0.4240.99 XWEB/3171 MMWEBSDK/20210501 Mobile Safari/537.36 MMWEBID/5616 MicroMessenger/8.0.6.1900(0x28000635) Process/appbrand0 WeChat/arm64 Weixin NetType/WIFI Language/zh_CN ABI/arm64 MiniProgramEnv/android",
                )
                ]);
            sleep(mt_rand(3, 5));
            continue;
        } elseif ($msg == '用户被封号') {
            updateUserFlag($db, $userid, 104);
            continue;
        } else {
            echo "unknown mistake" . \n;
            var_dump($content);
//            exit();
        }
    } else {
        $collects = isset($data_json['data']['collects']) ? $data_json['data']['collects'] : 0;
        $comments = isset($data_json['data']['comments']) ? $data_json['data']['comments'] : 0;
        $likes = isset($data_json['data']['likes']) ? $data_json['data']['likes'] : 0;
        $shareCount = isset($data_json['data']['shareCount']) ? $data_json['data']['shareCount'] : 0;
        $sql = "update xiaohongshu_comment_note_2 set comment_count = {$comments}, liked_count = {$likes}, collected_count = {$collects}, shared_count = {$shareCount}, content_flag = 100 where id = '{$noteid}';";
        var_dump($sql);
        $sth = $db->query($sql);
        # 转评赞数功能新增
        $sql_query = "insert xiaohongshu_note_statistics(note_id, num, statistics_type) values('{$noteid}', '{$shareCount}', 0), ('{$noteid}', '{$collects}', 1), ('{$noteid}', '{$likes}', 2)";
        $db->query($sql_query);
        var_dump($sql_query);
    }
}