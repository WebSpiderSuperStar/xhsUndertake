<?php
/**
 * 小红书automator2搜索获取帖子列表（普适版全量获取方案），本地抓包处理方案，暂时无法部署到服务器上，数据处理第一步
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
    die('Connection failed: ' . $e->getMessage());
}
//抓取品牌列表
$tag_arr = array(
    '薇诺娜',
    '舒痕',
    '融禾',
    '美德玛',
    '疤克',
    '康瑞保'
);
//抓取关键词列表
$keyword_arr = array(
    '特护霜',

    '舒痕胶',
    '舒痕硅',
    'dermatix',
    '舒痕祛疤膏',
    '祛疤膏儿童',

    'roohoo医用',
    '医用硅酮凝胶',
    '融禾霜',
    '融禾凝胶',
    'roohoo祛疤膏',
    'roohoo膏',

    '进口美德玛',
    '美德玛祛疤膏',
    '美德玛疤痕膏',
    '美德玛凝胶',
    '美德玛祛疤儿童',
    '美德玛去疤痕凝胶',
    'mederma ',
    'mederma祛疤',

    '疤克疤痕膏',
    '疤克 疤痕膏',
    '美国疤克',
    '疤克儿童',
    '疤克',
    '芭克祛疤膏',

    '康瑞保',
    '复方肝素钠尿囊凝胶',
    '秀碧',
    '秀碧疤痕凝胶',
    '秀碧祛疤',
    '德国秀碧'
);

$tag = $tag_arr[0];
$keyword = $keyword_arr[0];
$date = date("Ymd");
while (true) {
    //抓包存储路径
    $file = '/Users/wu.zhipeng/Desktop/2.txt';
    //数据处理
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (preg_match("#Coupon\s*body:\s*(.*)#is", $content, $match)) {
            $data = $match[1];
            if (preg_match("#Coupon\s*body:\s*(.*?)\s*Coupon\s*body:#", $content, $match)) {
                $data = $match[1];
            }
            $comment_json = json_decode($data, true);
            if (isset($comment_json['data']['items'])) {
                $comment_str = '';
                $tag_str = '';
                $comment_array = $comment_json['data']['items'];
                foreach ($comment_array as $value) {
                    $note_id = $value['note']['id'];
                    $note_title = $value['note']['title'];
                    if (empty($note_title)) {
                        $note_title = $value['note']['desc'];
                    }
                    $note_type = $value['note']['type'];
                    $liked_count = $value['note']['liked_count'];
                    $user_name = $value['note']['user']['nickname'];
                    $user_id = $value['note']['user']['userid'];
                    if (empty('$user_name')){
                        var_dump($value);
                        exit();
                    }
//                     if($note_type == 'normal'){
//                         $comment_str = $comment_str . "('" . $id . "','" . $note_title . "','" . $user_name . "','" . $user_id . "', 31),";
//                     }
                    $comment_str = $comment_str . "({$db->quote($note_id)}, {$db->quote($note_title)}, {$db->quote($user_name)}, {$db->quote($user_id)}, {$db->quote($liked_count)}),";
                    $tag_str = $tag_str . "({$db->quote($tag)}, {$db->quote($keyword)}, {$db->quote($date)}, {$db->quote($note_id)}),";
                }
                if (!empty($comment_str)) {
                    //去颜文字逻辑
                    // $comment_str = preg_replace_callback(
                    // '/./u',
                    // function (array $match) {
                    // return strlen($match[0]) >= 4 ? '' : $match[0];
                    // },
                    // $comment_str);
                    $comment_str = preg_replace("#\/n#is", "", $comment_str);
                    $comment_str = preg_replace("#\/t#is", "", $comment_str);
                    $comment_str = trim($comment_str, ",");
                    $tag_str = trim($tag_str, ",");
                    var_dump($comment_str);
                    //搜索结果表存入
                    $sql = "insert ignore into xiaohongshu_comment_note_2(id, title, user_name, user_id, liked_count) values {$comment_str} on duplicate key update liked_count = values(liked_count);";
                    var_dump($sql);
                    $res = $db->query($sql);
                    //搜索任务结果映射表存入
                    $sql = "insert ignore into xiaohongshu_search_tag(tag, keyword, date, id) values {$tag_str}";
                    var_dump($sql);
                    $res = $db->query($sql);
                } else {
                    //异常处理，打印包内容
                    var_dump($content);
                }
                //数据处理结束后删除文件
//                unlink($file);
            } else {
                //异常处理，打印包内容并删除文件，避免卡到之后任务处理
                var_dump($content);
//                unlink($file);
            }
        }
    }
    sleep(1);
}