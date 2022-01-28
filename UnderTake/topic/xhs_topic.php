<?php
/**
 * 小红书uiautomator2抓取用户关注话题（普适版方案），本地抓包处理方案，暂时无法部署到服务器上
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

while (true) {
    $file = 'D:\xiaohongshu\6.txt';
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (preg_match_all("#Userid:(.*?);Coupon\s*body:\s*(.*?})\s*GLHF#is", $content, $match)) {
            $userid_list = $match[1];
            $data_list = $match[2];
            var_dump($data_list);
            foreach ($data_list as $key => $data) {
                $user_id = $userid_list[$key];
                $topic_json = json_decode($data, true);
                if (!empty($topic_json['data'])) {
                    $topic_str = '';
                    $relationship_str = '';
                    $topic_list = $topic_json['data'];
                    foreach ($topic_list as $value) {
                        $topic_id_str = $value['oid'];
                        $link = $value['link'];
                        $title = $value['name'];
                        $image = $value['image'];
                        $desc = $value['desc'];
                        $discuss_num = $value['discuss_count'];
                        if (preg_match("#huati\.(.*)$#is", $topic_id_str, $match)) {
                            $topic_id = $match[1];
                        }
                        if (preg_match("#v2\/(.*?)\?page_source#is", $link, $match)) {
                            $page_id = $match[1];
                        }
                        $topic_str = $topic_str . "({$db->quote($topic_id)}, {$db->quote($page_id)}, {$db->quote($title)}, {$db->quote($image)}, {$db->quote($desc)}, {$db->quote($discuss_num)}),";
                        $relationship_str = $relationship_str . "({$db->quote($user_id)}, {$db->quote($topic_id)}),";
                    }
                    if (!empty($topic_str)) {
                        $topic_str = preg_replace("#\/n#is", "", $topic_str);
                        $topic_str = preg_replace("#\/t#is", "", $topic_str);
                        $topic_str = trim($topic_str, ",");
                        $relationship_str = trim($relationship_str, ",");
                        var_dump($topic_str);
                        //话题表存入
                        $sql = "insert ignore into xiaohongshu_topic(topic_id, page_id, title, image, `desc`, discuss_num) value {$topic_str}";
                        var_dump($sql);
                        $res = $db->query($sql);
                        //用户话题表映射表存入
                        $sql = "insert ignore into xiaohongshu_user_topic(user_id, topic_id) value {$relationship_str}";
                        var_dump($sql);
                        $res = $db->query($sql);
                    } else {
                        var_dump($data);
                    }
                }
            }
        }
        unlink($file);
    }
    sleep(1);
}