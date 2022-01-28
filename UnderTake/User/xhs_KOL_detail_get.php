/*
小红书蒲公英抓取特定行业KOL分类tag方案（普适版方案），后续可接入代理池部署在服务器上，注意cookie及时更换，具体刷新周期可后续沟通
*/
<?php
//opinion真服数据库连接
$dsn = 'mysql:dbname=opinion;host=10.21.200.48;port=3306;';
$user = 'opinion';
$password = 'vDGM0lspmy=';
try {
    $db2 = new PDO($dsn, $user, $password, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8mb4",
        PDO::ATTR_PERSISTENT => true
    ));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    sleep(10);
}

//获取小红书蒲公英加密字段
function Get_xs($sign_part)
{
    $dir = __DIR__;
    exec("node \"{$dir}/sign.js\" \"{$sign_part}\"", $output);
    return $output;
}

//curl请求
function curl_get($url, $header, $cookie, $postfield)
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
    $detail = curl_exec($ch);
    return $detail;
    // $detail = mb_convert_encoding("$detail", "UTF-8", "GBK");
}

//获取小红书蒲公英分类列表id，如果没有则新建一条再取id
function getTagid($db2, $tag)
{
    $sql = "select id from xhs_taxonomyTag where tagname = '{$tag}';";
    $sth = $db2->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($result)){
        $tagid = $result[0]['id'];
    } else {
        $sql = "insert ignore into xhs_taxonomyTag(tagname) value ('{$tag}');";
        var_dump($sql);
        $sth = $db2->query($sql);
        $sql = "select id from xhs_taxonomyTag where tagname = '{$tag}';";
        $sth = $db2->query($sql);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $tagid = $result[0]['id'];
    }
    return $tagid;
}

//插入kol与tag的映射表
function insertUserTag($db2, $user_id, $tagid)
{
    $sql = "insert ignore into xhs_kol_tagid(userid, tagid) value ('{$user_id}', {$tagid});";
    $sth = $db2->query($sql);
}

//测试方案只抓取了10页，后续可以优化一下这个部分
for ($page = 1; $page <= 10; $page ++) {
//     $sign_part = "/api/solar/cooperator/blogger/v1?column=comprehensiverank&location=&pageNum={$page}&pageSize=20&userType=1";
	//加密data，基本格式固定，与postfield需要对应上
    $sign_part = '/api/solar/cooperator/blogger/v2{\"fansNumberLower\":null,\"fansNumberUpper\":null,\"location\":null,\"contentTag\":[\"动漫\"],\"cpc\":false,\"column\":\"comprehensiverank\",\"sort\":\"desc\",\"gender\":null,\"personalTags\":[],\"pageNum\":' . $page . ',\"pageSize\":20}';
    $sign_arr = Get_xs($sign_part);
    $xs_pre = $sign_arr[1];
    $xt_pre = $sign_arr[2];
    if (preg_match("#'X-s':\s*'(.*?)'#is", $xs_pre, $match)) {
        $xs = $match[1];
    }
    if (preg_match("#'X-t':\s*(\d+)#is", $xt_pre, $match)) {
        $xt = $match[1];
    }
    $url = "https://pgy.xiaohongshu.com/api/solar/cooperator/blogger/v2";
//     $url = "https://pgy.xiaohongshu.com" . $sign_part;
    $header = array(
        "authorization: ",
        "Connection: keep-alive",
        "pragma: no-cache",
        "cache-control: no-cache",
        'Content-Type: application/json;charset=UTF-8',
        "accept: application/json, text/plain, */*",
        "x-s: {$xs}",
        "x-t: {$xt}",
        'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="91", "Chromium";v="91"',
        "sec-ch-ua-mobile: ?0",
        "Origin: https://pgy.xiaohongshu.com",
        "sec-fetch-site: same-origin",
        "sec-fetch-mode: cors",
        "sec-fetch-dest: empty",
        "accept-language: zh-CN,zh;q=0.9",
        "referer: https://pgy.xiaohongshu.com/solar/advertiser/patterns/kol"
    );
	//cookie为小红书蒲公英登录后cookie，需定期更新
    $cookie = 'xhsTrackerId=fb18a8ca-4d8d-4377-c301-42498681eacb; smidV2=2020021010214369a797a65363a27e709267a02b16f91500337cd5071c441b0; Hm_lvt_d0ae755ac51e3c5ff9b1596b0c09c826=1592279148,1592279359,1592285665,1593681529; xhsuid=SDDCUYmNWHAVegMu; customerClientId=308901689233169; timestamp2=20210611cfa93ee5f602315848b7cf20; timestamp2.sig=iXbgDXpUpALY2gVBs0TnHR8M_6oFG2YjBjw_zeGqKto; xhsTracker=url=user-profile&xhsshare=CopyLink; customerBeakerSessionId=1af0a1302a21e27566e760f8e99da9c1a149007agAJ9cQAoWBAAAABjdXN0b21lclVzZXJUeXBlcQFLA1gOAAAAX2NyZWF0aW9uX3RpbWVxAkdB2DJlQ1JeNVgJAAAAYXV0aFRva2VucQNYQQAAADdkMjgyZjk2ZTQ3NTQxNjNhMGQ5YmEzYzEzM2FlYjZlLTU0Nzg4NWQ5YzQ2YzQ4ZGY4ZmFjM2MwNjliNWRiNjFlcQRYAwAAAF9pZHEFWCAAAAA1NjFiOWE4YzE1ZGE0OGJiOThhZmRjYTNhMTRlZDBiM3EGWA4AAABfYWNjZXNzZWRfdGltZXEHR0HYMmVDUl41WAYAAAB1c2VySWRxCFgYAAAANjA1MmM3Yjg3OTEzMGEwMDAxMDE0MWI1cQl1Lg==; solar.beaker.session.id=1623823629374078645926';
    $post_data = array('cpc' => false,'column' => 'comprehensiverank','sort' => 'desc','pageNum' => $page,'pageSize' => 20);
    $postfield = json_encode($post_data);
    $postfield = '{"fansNumberLower":null,"fansNumberUpper":null,"location":null,"contentTag":["动漫"],"cpc":false,"column":"comprehensiverank","sort":"desc","gender":null,"personalTags":[],"pageNum":' . $page . ',"pageSize":20}';
    $data = curl_get($url, $header, $cookie, $postfield);
    $sleep_time = rand(10, 20);
    sleep($sleep_time);
    $data_arr = json_decode($data, true);
    var_dump($url);
    var_dump($data);
    var_dump($data_arr);
	//数据处理
    if($data_arr['success'] == true){
        $insert_arr = array();
        $insert_str = '';
        $kollist = $data_arr['data']['kols'];
        if(!empty($kollist)){
            foreach ($kollist as $kol){
                $user_id = $kol['userId'];
                $contentTags = $kol['contentTags'];
                if(!empty($contentTags)){
                    foreach ($contentTags as $contentTag){
                        $taxonomy1Tag = $contentTag['taxonomy1Tag'];
                        $taxonomy2Tag_arr = $contentTag['taxonomy2Tags'];
                        foreach ($taxonomy2Tag_arr as $taxonomy2Tag){
                            $tag = $taxonomy1Tag . '-' . $taxonomy2Tag;
                            $tagid = getTagid($db2, $tag);
                            insertUserTag($db2, $user_id, $tagid);
                        }
                    }
                }
            }
        }
    }
}
var_dump($data);