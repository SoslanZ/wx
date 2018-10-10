## 目录结构

初始的目录结构如下：

~~~
www  WEB部署目录（或者子目录）
├─app                   应用目录
│  ├─admin              Tplay核心目录
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图模板目录
│  │
│  ├─command.php        命令行工具配置文件
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─database.php       数据库配置文件
│
├─public                WEB目录（对外访问目录）
│  ├─static             css、js等资源目录
│  │   ├─admin              Tplay后台css、js文件
│  │   ├─public             公共css、js文件
│  ├─uploads          图片等资源文件
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
│
├─simport              框架系统目录
│  ├─thinkphp             thinkphp核心文件
│  ├─extend          扩展类库目录
│  └─vendor          第三方类库目录（Composer依赖库
│
├─runtime               应用的运行时目录（可写，可定制）
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
├─tplay.sql             Tplay框架sql文件
~~~

## 安装使用

1. 首先克隆下载应用项目仓库
    

2. 将根目录下的`sql`文件导入`mysql`数据库

3. 修改项目`/app/database.php`文件中的数据库配置信息

4. 将你的域名指向根目录下的public目录（重要）.

5. 浏览器访问：`你的域名/admin`，默认管理员账户：`admin` 密码：`123123`

6. 如果你用到了短信配置，请前往阿里大鱼官网申请下载自己的sdk文件，替换/extend/dayu下的文件，在后台配置自己的appkey即可


## 服务环境部署 
####  Nginx 虚拟主机配置参考

```bash
server {
    listen 80;

    server_name dsp.com;

    root "....../public";
    index index.php index.html index.htm;

    location / {
        if (!-e $request_filename) {
            rewrite  ^(.*)$  /index.php?s=/$1  last;
            break;
        }
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php7.1.9-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf)$ {
        access_log  off;
        error_log   off;
        expires     30d;
    }

    location ~ .*\.(js|css)?$ {
        access_log   off;
        error_log    off;
        expires      12h;
    }
}
```