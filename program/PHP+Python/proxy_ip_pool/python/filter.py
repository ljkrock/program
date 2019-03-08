from urllib import request
import re
import urllib.parse
import requests
import json
import uuid
import os, sys
import time
import datetime
import pymysql
import threading
import math
import ssl
import string
from DBUtils.PooledDB import PooledDB


context = ssl._create_unverified_context()
#创建代理池
pool = PooledDB(pymysql, 10, host = 'localhost', user = 'root', passwd ='root', db = 'proxy',port = 3306, maxconnections=200,charset = 'utf8', use_unicode = True)
#查询数据库已经采集好的ip
conn =pool.connection()
cur =conn.cursor()
threads = []
db_table = 'proxy'
start = time.time()
#为了后面多线程分配，先得到总的ip数量
fsql = 'select count(1) from %s;'%(db_table)
cur.execute(fsql)
results = cur.fetchone()
#当我们线程为200时每个线程需要验证的条数，因为数据6w多，就没算得那么细了
total = math.ceil(results[0]/200) + 1
print(total)
#查询出所有ip数据
all_sql = 'select * from %s;'%(db_table)
cur.execute(all_sql)
results = cur.fetchall()
#建立队列，存储爬取网址
#线程类，每个子线程调用，继承Thread类并重写run函数，以便执行我们的爬虫函数
class myThread(threading.Thread):
    def __init__(self,threadName):
        threading.Thread.__init__(self)
        self.threadName = threadName
    def run(self):
        #每个线程要验证的开始ip的序号，这里忽略了第0条
        start = total*(self.threadName-1)+1
        #每个线程最后一个ip的序号
        end = self.threadName*total
        #得到一个数据库连接
        conn2 =pool.connection()
        cur2 =conn2.cursor()
        #循环验证当前线程的ip
        for i in range(start,end):
            try:
                available(i,cur2)
            except:
                continue
        conn2.commit()
        cur2.close()
        conn2.close()
        print("Exiting "+self.name)
 #爬取的函数，爬虫的程序
def available(i,cur2):
    
    try:
        #设置header头
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/56.0.2924.87 Safari/537.36'
        }
        #创建并安装代理
        proxy = request.ProxyHandler({'https':results[i][1]})
        opener = request.build_opener(proxy)
        request.install_opener(opener)
        #发起请求
        req = request.Request(url='https://www.ip.cn/', headers=headers)
        #这里设置3秒超时，得到返回数据
        data = request.urlopen(req,timeout=3).read().decode('utf-8')
        #转换为字符串
        html = str(data)
        #查找返回的代码里有没有特殊标识
        if html.find('class="well"')!=-1:
            #判断代理池里是否已经这个ip了，有就返回验证下一个           
            select_sql = 'select count(*) from available where ip="%s";'%results[i][1]
            cur2.execute(select_sql)
            in_table = cur2.fetchone()
            print(results[i][1]+' 访问正常:'+str(in_table[0]))
            if in_table[0]>0:
                return
            #将新的可用的ip存入代理池
            available_sql = 'INSERT INTO available (`ip`) VALUES ("%s");'%results[i][1]
            cur2.execute(available_sql)
            
    except Exception as e:
        print(e)
    
    
#创建线程
for i in range(1,200):
    thread = myThread(i)
    thread.start()
    threads.append(thread)
for t in threads:
    t.join()
end  = time.time()
#输出时间并结束
print("The total time is:",end-start)
cur.close()
conn.close()