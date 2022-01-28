# ·自动滑动获取APP搜索结果
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


# 滑动逻辑
def dragList(d):
    wsize = d.window_size()
    # d.drag(700, 1000, 700, 100, 1)
    d.swipe(700, 1000, 700, 100)


# d = u2.connect('emulator-5556')
d = u2.connect('127.0.0.1:21513')


def main(d, uname):
    errnum = 0
    keyword = '全部'
    while True:
        dragList(d)
        time.sleep(0.5)
        # 检测当前tag下所有帖子加载完毕
        if d(className="android.widget.TextView", text="- THE END -").exists(0.2):
            errnum = errnum + 1
            if (errnum > 20):
                break
            # tag列表加载出来后新tag加入切换列表
            if d(className="android.widget.TextView").exists(0.2):
                keywordlist = d(className="android.widget.TextView")
            count = keywordlist.count
            # 当前tag遍历结束后切换到下一个tag
            for i in range(count):
                if (keywordlist[i].info['text'] == keyword):
                    print(keywordlist[i + 1].info['text'])
                    keyword = keywordlist[i + 1].info['text']
                    break
            print(keyword)
            # 点击切换tag
            d(className="android.widget.TextView", text=keyword).click()
        elif d(className="android.widget.TextView", text="没有找到相关内容 换个词试试吧").exists(0.2):
            errnum = errnum + 1
            if (errnum > 20):
                break
            if d(className="android.widget.TextView").exists(0.2):
                keywordlist = d(className="android.widget.TextView")
            count = keywordlist.count
            for i in range(count):
                if (keywordlist[i].info['text'] == keyword):
                    print(keywordlist[i + 1].info['text'])
                    keyword = keywordlist[i + 1].info['text']
                    break
            print(keyword)
            d(className="android.widget.TextView", text=keyword).click()
    rs = subprocess.Popen("adb disconnect %s" % (uname), shell=True, stdout=subprocess.PIPE)
    # 防止管道死锁
    rs.communicate()
    time.sleep(1)
    rs = subprocess.Popen("adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE)
    # 防止管道死锁
    rs.communicate()
    cursor.close()


# 守护线程的上游线程
def checkDeal():
    daemonThread()


def daemonThread():
    rs = subprocess.Popen("adb disconnect %s" % (uname), shell=True, stdout=subprocess.PIPE)
    # 防止管道死锁
    rs.communicate()
    time.sleep(1)
    rs = subprocess.Popen("adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE)
    # 防止管道死锁
    rs.communicate()
    time.sleep(1)
    u = u2.connect_usb(uname)
    print(u)
    main(u, uname)


# define
# mobileId是模拟器的运行机器,命令行传入
# mobileId = argv[1]
mobileId = 1
# 启动模拟器
# uname = 'emulator-5556'
uname = '127.0.0.1:21513'
rs = subprocess.Popen("adb connect %s" % (uname), shell=True, stdout=subprocess.PIPE)
# 防止管道死锁
rs.communicate()
time.sleep(10)
# 获取主机名称
myname = socket.getfqdn(socket.gethostname())
# 获取本机ip
myaddr = socket.gethostbyname(myname)
version = str(mobileId) + '|' + str(myaddr)

tmpTableName = 'app_industry_search_rank_result_tmp' + str(mobileId)

# adb connect '127.0.0.1:21503'
mainThread = threading.Thread(target=checkDeal)
mainThread.start()
checkCount = 0
# 死循环判断线程是否存活(任务执行30S秒如果临时表数据量不变认为任务中途出错,结束线程-同时守护线程也一起结束)
while 1:
    if (mainThread.is_alive() == False):
        mainThread = threading.Thread(target=checkDeal)
        mainThread.start()
        checkCount = checkCount + 1
        print(checkCount)
        if (checkCount > 100):
            exit()
    # #reboot
    # os.popen("memuc stop  -i %s"%(mobileId))
    # time.sleep(10)
    # os.popen("memuc start -i %s"%(mobileId))
    # time.sleep(50)
    # os.popen("adb connect %s"%(uname))
    # checkCount = 0
    time.sleep(3)
