<?php
/**
 * 小红书automator2抓取用户关注列表方案（普适版方案），本地抓包处理方案，暂时无法部署到服务器上，用户画像相关
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
while (true){
    $file = 'D:\xiaohongshu\5.txt';
    if(is_file($file)){
        $content = file_get_contents($file);
        if(preg_match_all("#Userid:(.*?);Coupon\s*body:\s*(.*?}})\s*GLHF#is", $content, $match)){
            $userid_list = $match[1];
            $data_list = $match[2];
            foreach ($data_list as $key => $data){
                $user_id = $userid_list[$key];
                $follower_json = json_decode($data, true);
                if(!empty($follower_json['data']['users'])){
                    $follower_str = '';
                    $relationship_str = '';
                    $follower_list = $follower_json['data']['users'];
                    foreach ($follower_list as $value){
                        $follower_user_id = $value['userid'];
                        $user_name = $value['nickname'];
                        $image = $value['images'];
                        $self_introduction = $value['desc'];
                        $follower_str = $follower_str . "({$db->quote($follower_user_id)}, {$db->quote($user_name)}, {$db->quote($image)}, {$db->quote($self_introduction)}),";
                        $relationship_str = $relationship_str . "({$db->quote($user_id)}, {$db->quote($follower_user_id)}),";
                    }
                    if(!empty($follower_str)){
                        $follower_str = preg_replace_callback(
                            '/./u',
                            function (array $match) {
                                return strlen($match[0]) >= 4 ? '' : $match[0];
                            },
                            $follower_str);
                        $follower_str = preg_replace("#\/n#is", "", $follower_str);
                        $follower_str = preg_replace("#\/t#is", "", $follower_str);
                        $follower_str = trim($follower_str, ",");
                        $relationship_str = trim($relationship_str, ",");
                        var_dump($follower_str);
                        $sql = "insert ignore into xiaohongshu_note_usr2(user_id, user_name, image, self_introduction) value {$follower_str}";
                        var_dump($sql);
                        $res = $db->query($sql);
                        $sql = "insert ignore into xiaohongshu_user_follow(user_id, follow_user_id) value {$relationship_str}";
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
