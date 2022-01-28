<?php
/**
 * 小红书automator2抓取账号收藏夹帖子方案（普适版方案），本地抓包处理方案，暂时无法部署到服务器上，以前是手动滑一下，一般3分钟内解决
 */
header("Content-Type: text/html;charset=utf8mb4");
$dsn = 'mysql:dbname=opinion;host=10.21.200.48;port=3306;';
$user = 'opinion';
$password = 'vDGM0lspmy=';
try {
    $db = new PDO($dsn, $user, $password, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8",
        PDO::ATTR_PERSISTENT => true
    ));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    sleep(10);
}
$sql = "set names utf8mb4;";
$res = $db->query($sql);
while (true) {
    $file = 'D:\xiaohongshu\7.txt';
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (preg_match("#Coupon\s*body:\s*(.*)#is", $content, $match)) {
            $data = $match[1];
            if (preg_match("#Coupon\s*body:\s*(.*?)\s*Coupon\s*body:#", $content, $match)) {
                $data = $match[1];
            }
            $comment_json = json_decode($data, true);
            if (isset($comment_json['data'])) {
                $comment_str = '';
                $comment_array = $comment_json['data'];
                foreach ($comment_array as $value) {
                    $note_id = $value['id'];
                    $note_title = $value['title'];
                    if (empty($note_title)) {
                        $note_title = $value['display_title'];
                    }
                    $note_type = $value['type'];
                    $user_name = $value['user']['nickname'];
                    $user_id = $value['user']['userid'];
                    $create_time = $value['time'];
                    if ($note_type == 'normal') {
                        $comment_str = $comment_str . "('" . $note_id . "','" . $note_title . "','" . $create_time . "','" . $user_name . "','" . $user_id . "', '收藏抓取'),";
                    }
                }
                if (!empty($comment_str)) {
                    $comment_str = preg_replace("#\/n#is", "", $comment_str);
                    $comment_str = preg_replace("#\/t#is", "", $comment_str);
                    $comment_str = trim($comment_str, ",");
                    var_dump($comment_str);
                    $sql = "insert ignore into xiaohongshu_comment_note(id, title, create_time, user_name, user_id, keyword) value {$comment_str} on duplicate key update keyword = '收藏抓取'";
                    $res = $db->query($sql);
                } else {
                    var_dump($content);
                }
                unlink($file);
            } else {
                unlink($file);
            }
        }
    }
    sleep(1);
}