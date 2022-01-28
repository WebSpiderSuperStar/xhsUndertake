<?php
/**
 * 小红书automator2搜索获取帖子列表（LV专用表），本地抓包处理方案，暂时无法部署到服务器上，数据处理第一步
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
while (true){
	//抓包存储路径
    $file = 'D:\xiaohongshu\2.txt';
    if(is_file($file)){
		//获取数据包内容
        $content = file_get_contents($file);
		//处理数据
        if(preg_match("#Coupon\s*body:\s*(.*)#is", $content, $match)){
            $data = $match[1];
			//可能会抓到两层外壳，二次去壳
            if(preg_match("#Coupon\s*body:\s*(.*?)\s*Coupon\s*body:#", $content, $match)){
                $data = $match[1];
            }
			//包内容转化为json格式,便于处理
            $comment_json = json_decode($data, true);
			//获取帖子列表
            if(isset($comment_json['data']['items'])){
                $comment_str = '';
                $comment_array = $comment_json['data']['items'];
                foreach ($comment_array as $value){
                    $note_id = $value['note']['id'];
                    $note_title = $value['note']['title'];
                    if(empty($note_title)){
                        $note_title = $value['note']['desc'];
                    }
                    $note_type = $value['note']['type'];
                    $liked_count = $value['note']['liked_count'];
                    $user_name = $value['note']['user']['nickname'];
                    $user_id = $value['note']['user']['userid'];
//                     if($note_type == 'normal'){
//                         $comment_str = $comment_str . "('" . $id . "','" . $note_title . "','" . $user_name . "','" . $user_id . "', 31),";
//                     }
                    $comment_str = $comment_str . "({$db->quote($note_id)}, {$db->quote($note_title)}, {$db->quote($liked_count)}, {$db->quote($user_name)}, {$db->quote($user_id)}),";
                }
                if(!empty($comment_str)){
                    $comment_str = preg_replace("#\/n#is", "", $comment_str);
                    $comment_str = preg_replace("#\/t#is", "", $comment_str);
                    $comment_str = trim($comment_str, ",");
                    var_dump($comment_str);
					//数据存储
                    $sql = "insert ignore into xiaohongshu_comment_note(id, title, liked_count, user_name, user_id) value {$comment_str}";
                    var_dump($sql);
                    $res = $db->query($sql);
                } else {
                    var_dump($content);
                }
				//数据处理结束后删除文件
                unlink($file);
            } else {
				//异常处理，打印包内容并删除文件，避免卡到之后任务处理
				var_dump($content);
                unlink($file);
            }
        }
    }
    sleep(1);
}