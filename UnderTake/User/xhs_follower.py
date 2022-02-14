# uiautomator自动滑动获取用户关注列表（包括关注话题和用户）
import uiautomator2 as u2
import time
import datetime
import re
import os
import sys
import locale
import subprocess
import pymysql
import threading
from pprint import pprint
from sys import argv
import random
import socket

sys.path.append(".\\lib\\")

# DB连接
def db():
    conn = pymysql.connect(
        host="10.21.200.48",
        user="opinion",
        passwd="vDGM0lspmy=",
        db="opinion",
        charset="utf8",
        cursorclass=pymysql.cursors.DictCursor,
    )
    conn.ping(True)
    return conn


# 滑动模块
def dragList(d, top):
    wsize = d.window_size()
    d.drag(600, top, 600, 200, 0.1)


# 自动打开浏览器
def runTaobaoApp(u):
    # sess = u.session("com.android.browser")
    sess = u.app_start("com.android.browser")
    if sess:
        return 1
    else:
        return 2


# 重启小红书APP
def check_restart(u):
    u.press("home")
    sess = u.session("com.xingin.xhs")


# 检查脚本是否卡死
def check_stop(d):
    if d(resourceId="android:id/scrollView").exists():
        d(resourceId="android:id/button1", text="确定").click()
    elif d(resourceId="android:id/contentPanel").exists():
        d(resourceId="android:id/button1", text="确定").click()


# 检查模拟器是否黑屏
def check_black(d):
    if d(resourceId="android:id/title", text="已连接到 USB 调试").exists():
        d.press("power")
        time.sleep(1)
        d.drag(700, 2000, 700, 100, 0.5)


# d = u2.connect('emulator-5562')
d = u2.connect("127.0.0.1:21513")
errnum = 0


def main(d, uname):
    global errnum
    errnum = errnum + 1
    check_black(d)
    check_stop(d)
    # 预防之前卡死在APP里，先关闭APP
    # d.app_stop("com.android.browser")
    # 数据库连接
    dbn = db()
    cursor = dbn.cursor()
    dbnprod = dbprod()
    cursorprod = dbnprod.cursor()
    insert_date = time.strftime("%Y%m%d", time.localtime(time.time()))

    while True:
        # 启动App
        runTaobaoApp(d)
        time.sleep(1)
        # 取任务b2c_id
        sql = "select user_id from xiaohongshu_note_usr2 where flag in (5,10) and upload < 5 order by user_id desc limit 1;"
        cursor.execute(sql)
        res = cursor.fetchone()
        if res is None:
            # 停止app并程序退出
            d.app_stop("com.android.browser")
            break
        user_id = res["user_id"]

        sql_upload = (
            "update xiaohongshu_note_usr2 set flag = 10,upload = upload + 1 where user_id = '"
            + str(user_id)
            + "';"
        )
        cursor.execute(sql_upload)
        dbn.commit()
        url = "https://www.xiaohongshu.com/user/profile/" + user_id
        # 输入关键字
        d(resourceId="com.android.browser:id/url").set_text(url)
        # 搜索按钮
        # d(resourceId="com.taobao.taobao:id/searchbtn").click()
        d.press("enter")
        time.sleep(5)
        if d(resourceId="com.xingin.xhs:id/bo3").exists():
            print("打开App")
            d.click(400, 1150)
            # d(className="android.view.View", description="打开App").click()
        time.sleep(3)
        errnum = 0

        # 滑动抓取评论界面
        finish_flag = 0
        top = 800
        count = 0
        lasttopic = ""

        if d(resourceId="com.xingin.xhs:id/fh").exists():
            d(resourceId="com.xingin.xhs:id/fh").click()
            time.sleep(1)
            if d(resourceId="com.xingin.xhs:id/c_c").exists():
                # 该用户无关注列表
                if d(resourceId="com.xingin.xhs:id/a0d", text="还没有关注任何小红薯哦").exists():
                    sql_upload = (
                        "update xiaohongshu_note_usr2 set flag = 20 where user_id = '"
                        + str(user_id)
                        + "';"
                    )
                    cursor.execute(sql_upload)
                    dbn.commit()
                    break
                # 用户关注话题或用户遍历逻辑，每次滑动后记录当前页最后一位关注的名字，检测上次最后一位和当前最后一位是否相同，相同则表示已遍历结束
                if d(resourceId="com.xingin.xhs:id/c97", text="话题和专辑").exists():
                    file_path = "D:\\xiaohongshu\\5.txt"
                    d(resourceId="com.xingin.xhs:id/c97", text="话题和专辑").click()
                    time.sleep(2)
                    if d(className="android.widget.TextView", text="话题").exists():
                        if d(
                            resourceId="com.xingin.xhs:id/c_c", text="还没有关注任何话题哦"
                        ).exists():
                            d.press("back")
                            break
                        topiclist = d(resourceId="com.xingin.xhs:id/c_4")
                        lasttopic = topiclist[0].info["text"]
                        while True:
                            dragList(d, top)
                            time.sleep(1)
                            topiclist = d(resourceId="com.xingin.xhs:id/c_4")
                            checktopic = topiclist[0].info["text"]
                            if checktopic == lasttopic:
                                break
                            else:
                                lasttopic = checktopic
                                while os.path.exists(file_path):
                                    time.sleep(0.2)
                    d.press("back")
                file_path = "D:\\xiaohongshu\\6.txt"
                usernamelist = d(resourceId="com.xingin.xhs:id/c97")
                lastusername = usernamelist[0].info["text"]
                while True:
                    dragList(d, top)
                    time.sleep(0.5)
                    usernamelist = d(resourceId="com.xingin.xhs:id/c97")
                    checkusername = usernamelist[0].info["text"]
                    print(checkusername)
                    print(lastusername)
                    if checkusername == lastusername:
                        sql_upload = (
                            "update xiaohongshu_note_usr2 set flag = 20 where user_id = '"
                            + str(user_id)
                            + "';"
                        )
                        cursor.execute(sql_upload)
                        dbn.commit()
                        break
                    else:
                        lastusername = checkusername
                        while os.path.exists(file_path):
                            time.sleep(0.2)
    rs = subprocess.Popen(
        "adb disconnect %s" % (uname), shell=True, stdout=subprocess.PIPE
    )
    # 防止管道死锁
    rs.communicate()
    time.sleep(2)
    rs = subprocess.Popen(
        "adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE
    )
    # 防止管道死锁
    rs.communicate()
    cursor.close()


# 守护线程的上游线程
def checkDeal():
    daemonThread()


def daemonThread():
    rs = subprocess.Popen(
        "adb disconnect %s" % (uname), shell=True, stdout=subprocess.PIPE
    )
    # 防止管道死锁
    rs.communicate()
    time.sleep(1)
    rs = subprocess.Popen(
        "adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE
    )
    # 防止管道死锁
    rs.communicate()
    time.sleep(1)
    u = u2.connect(uname)
    print(u)
    main(u, uname)


# 启动模拟器
mobileId = 7
# while Dnconsole.is_running(int(mobileId)) == False:
# print("is_running")
# Dnconsole.launch(mobileId)
# time.sleep(10)
mobileId = 5556
# mobileId = 1
# seachItemCount = 50
# uname = 'emulator-5562'
uname = "127.0.0.1:21513"
rs = subprocess.Popen("adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE)
# 防止管道死锁
rs.communicate()
time.sleep(10)
# 获取主机名称
myname = socket.getfqdn(socket.gethostname())
# 获取本机ip
myaddr = socket.gethostbyname(myname)
version = str(mobileId) + "|" + str(myaddr)

tmpTableName = "app_industry_search_rank_result_tmp" + str(mobileId)

# adb connect 'emulator-5568'
mainThread = threading.Thread(target=checkDeal)
mainThread.start()
checkCount = 0
# 死循环判断线程是否存活(任务执行30S秒如果临时表数据量不变认为任务中途出错,结束线程-同时守护线程也一起结束)
while 1:
    if mainThread.is_alive() == False:
        mainThread = threading.Thread(target=checkDeal)
        mainThread.start()
        checkCount = checkCount + 1
        print(checkCount)
        if checkCount > 100:
            exit()
        # #reboot
        # os.popen("memuc stop  -i %s"%(mobileId))
        # time.sleep(10)
        # os.popen("memuc start -i %s"%(mobileId))
        # time.sleep(50)
        # os.popen("adb connect %s"%(uname))
        # checkCount = 0
    time.sleep(3)
