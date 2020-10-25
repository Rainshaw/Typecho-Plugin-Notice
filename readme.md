## 插件简介
![](https://img.shields.io/badge/Typecho->17.11.15-brightgreen.svg?style=plastic)
![](https://img.shields.io/badge/language-PHP-blue.svg?style=plastic)
![](https://visitor-badge.glitch.me/badge?page_id=https://github.com/RainshawGao/Typecho-Plugin-Notice)
![](https://img.shields.io/badge/license-GPL_v3-000000.svg?style=plastic)

![](https://img.shields.io/badge/Version-0.2.0-yellow.svg?style=plastic)
[![](https://img.shields.io/badge/github-@RainshawGao-red.svg?style=plastic)](http://github.com/RainshawGao)
[![](https://img.shields.io/badge/Email-rxg-red.svg?style=plastic)](mailto:rxg@live.com)

Notice 是 Typecho 评论通知插件，支持 SMTP、Server酱、Qmsg酱 两种接口，均采用异步方式发送

在评论审核通过、用户评论文章、用户评论被回复时发送邮件通知

## 安装方法

1. 点击仓库右上角下载源码或点击[链接](https://github.com/RainshawGao/Typecho-Plugin-Notice/archive/master.zip)下载最新版本插件
2. 解压后重命名文件夹为 `Notice` ，再上传至网站的 `/usr/plugins/` 目录下
3. 启用该插件，正确填写相关信息


## 开发进展
- [x] 邮件推送、自定义邮件模版
- [x] Server酱推送
- [x] QQ 推送
- [ ] 邮件推送测试
- [ ] Server 酱推送测试
- [ ] 在线编辑模版文件
- [ ] 企业微信推送
- [ ] 钉钉推送
- [ ] 自定义推送

## 自定义邮件模板说明

插件共有三个模板，保存在 `template` 目录下，分别为：

1. approved.html：邮件审核通过通知模板
2. owner.html：博主评论通知模板
3. guest.html：游客评论通知模板

三个模板使用变量作为内容替换，您只需在自己的模板中增加相应的模板变量即可，模板变量列表如下：

1. {siteTitle}：站点标题
2. {title}：文章标题
3. {author}：评论者名称
4. {author_p}：被评论者（如果有的话）名称
5. {ip}：评论者 ip 地址
6. {mail}：评论者邮箱
7. {permalink}: 评论的永久链接
8. {manage}: 评论的后台管理页面链接
9. {text}: 评论内容
10. {text_p}: 被评论的内容
11. {time}: 发邮件时间
12. {status}: 评论状态['通过', '待审', '垃圾']
