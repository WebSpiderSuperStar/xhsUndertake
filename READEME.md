# 小红书

## File Tree

```dart
├── READEME.md
├── UnderTake
│   ├── User
│   │   ├── xhs_KOL_detail_get.php
│   │   ├── xhs_follow.php
│   │   ├── xhs_follower.py
│   │   └── xhs_user_index.php
│   ├── comment
│   │   ├── xhs_comment.php
│   │   └── xhs_comment2.php
│   ├── id
│   │   ├── id_search.php
│   │   ├── id_search2.php
│   │   ├── note_collect.php
│   │   ├── xhs_search.php
│   │   └── xiaohongshu_note_get.py
│   ├── other
│   │   └── xhs_count.php
│   └── topic
│       ├── xhs_noteget_fromtopic.php
│       └── xhs_topic.php
├── readme.txt
└── res
    ├── 7500.lua
    └── 小红书鉴别代购和售假规则_0527.xlsx
```

## 已布置服务器上的脚本逻辑路径（部分）

```bash
# LV专用版帖子详情与用户详情任务上传逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_note_comment_insert.php                   
# LV专用版帖子详情与用户详情任务处理逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_note_comment_do.php                       
# LV专用版帖子筛选逻辑(注意日期的更新)
dev\command\xiaohongshu\note_comment\xiaohongshu_note_comment_statistic.php    
# 普适版帖子详情任务上传逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_note_detail_collect_insert.php      
# 普适版帖子详情任务处理逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_note_detail_collect_do.php                
# LV专用版帖子与用户失效check任务上传逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_noteanduser_check_insert.php              
# LV专用版帖子与用户失效check任务处理逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_noteanduser_check_do.php                  
# 普适版话题相关帖子抓取上传逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_topic_notelist_insert.php                 
# 普适版话题相关帖子抓取处理逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_topic_notelist_do.php                     
# 普适版用户详情任务上传逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_user_detail_insert.php                    
# 普适版用户详情任务处理逻辑
dev\command\xiaohongshu\note_comment\xiaohongshu_user_detail_do.php                        
```

## LV

```sql
# 上月十四号
select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month)
# 本月十四号
select date_format(curdate(), '%Y-%m-14')
```



### 流程

> 检查LV新入帖子数量，一般每次14日开始抓取，抓取关键词**LV**，**路易威登**，抓取额度10000条

1.check automator方案 获取帖子id进度

```sql
select COUNT(*)
from opinion.xiaohongshu_comment_note
where create_time = '';
```

> 先增加到达11000：抓取帖子id
>
> 逐渐减少（需要执行step2）：抓取帖子详情
>
> 帖子抓取错误：日志
>
> ```sql
> select COUNT(*)
> from jd.spider_log
> where uuid like '1001191%';
> ```

2. 上传帖子id（一次默认4000），堡垒机

```sql
sudo php /var/www/monitor/dev/command/xiaohongshu/note_comment/xiaohongshu_note_comment_insert.php
```

3. 抓取评论

```
xhs_comment2.php
```

3. check 本月抓取评论的进度

```sql
select comment_flag, COUNT(comment_flag)
from opinion.xiaohongshu_comment_note
where create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and create_time < (select date_format(curdate(), '%Y-%m-14'))
group by comment_flag;
```

> 0:初始、1：完成、2：进行中

4. 帖子用户、评论用户导入到用户表

```sql
insert ignore into xiaohongshu_note_usr(user_id, user_name)
select user_id, comment_nick
from xiaohongshu_note_comment;

insert ignore into xiaohongshu_note_usr(user_id, user_name)
select user_id, user_name
from xiaohongshu_comment_note;

update xiaohongshu_note_usr xnu join xiaohongshu_comment_note xcn on xnu.user_id = xcn.user_id
set xnu.flag = 100
where xcn.create_time >= '2021-06-14 00:00'
  and xcn.create_time <= '2021-07-13 00:00'
  and flag = 0;
  
update xiaohongshu_note_usr xnu join xiaohongshu_comment_note xcn on xnu.user_id = xcn.user_id
set xnu.flag = 100
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and flag = 0;


update xiaohongshu_comment_note xcn join xiaohongshu_note_comment xnc on xcn.note_id = xnc.note_id join xiaohongshu_note_usr xnu on xnc.user_id = xnu.user_id
set xnu.flag = 100
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and flag = 0;
```

5. Check 用户信息抓取是否完成

> sudo php /var/www/monitor/dev/command/xiaohongshu/note_comment/xiaohongshu_note_comment_insert.php

```sql
SELECT count(*)
FROM xiaohongshu_note_usr
where flag = 101;
```

> 抓取用户信息（100（未上传） -> 101(处理) -> 1（处理完成））

6. LV专用版帖子筛选逻辑(注意日期的更新)

```
dev\command\xiaohongshu\note_comment\xiaohongshu_note_comment_statistic.php
```


7. 打假数据提取

```sql
# 刷新当月需要更新的评论用户信息，时间周期随报告时间变化
update xiaohongshu_note_usr xnu join xiaohongshu_comment_note xcn on xnu.user_id = xcn.user_id
set xnu.flag = 100
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and flag = 0;

# 刷新当月需要更新的帖子作者信息，时间周期随报告时间变化
update xiaohongshu_comment_note xcn join xiaohongshu_note_comment xnc on xcn.note_id = xnc.note_id join xiaohongshu_note_usr xnu on xnc.user_id = xnu.user_id
set xnu.flag = 100
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and flag = 0;

# 刷新当月造假评论（关键词），时间周期随报告时间变化，关键词参照 小红书鉴别代购和售假规则_0527.xlsx->鉴别售假评论->评论涉及造假
update xiaohongshu_comment_note xcn join xiaohongshu_note_comment xnc on xcn.note_id = xnc.note_id
set xnc.fake_flag = 1,
    xnc.notice    = '售假评论-评论涉及造假：私聊'
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and comment_content like '%私聊%';

# 刷新当月造假评论（纯数字），时间周期随报告时间变化
update xiaohongshu_comment_note xcn join xiaohongshu_note_comment xnc on xcn.note_id = xnc.note_id
set xnc.fake_flag = 1,
    xnc.notice    = '售假评论-评论涉及造假：评论回复数字，无文字内容'
where xcn.create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and xcn.create_time <= (select date_format(curdate(), '%Y-%m-14'))
  and comment_content REGEXP '(^[0-9]+$)';
```

```sql
# 提取数据
# 售假帖子集合，时间周期随报告时间变化

SELECT *
FROM xiaohongshu_comment_note
where create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and create_time < (select date_format(curdate(), '%Y-%m-14'))
  and fake_flag = 1;

# 代购帖子集合，时间周期随报告时间变化
SELECT *
FROM xiaohongshu_comment_note
where create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and create_time < (select date_format(curdate(), '%Y-%m-14'))
  and fake_flag = 1
  and daigoubuyer_flag > 0
group by user_id;

# 特殊账号关注帖子集合，时间周期随报告时间变化
select *
from xiaohongshu_comment_note
where keyword = '收藏抓取'
  and create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and create_time < (select date_format(curdate(), '%Y-%m-14'))

# 疑似售假评论集合，时间周期随报告时间变化
select xnc.*
from xiaohongshu_comment_note xcn
         join xiaohongshu_note_comment xnc on xcn.note_id = xnc.note_id
where create_time >= (select DATE_SUB(date_format(curdate(), '%Y-%m-14'), INTERVAL 1 month))
  and create_time < (select date_format(curdate(), '%Y-%m-14'))
  and xnc.fake_flag = 1;
```


## TODO

### 完善

数据表的整理

更熟悉代码

### 新功能

搜索接口

数美滑块





