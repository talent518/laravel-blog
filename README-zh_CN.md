# Laravel 博客
基于Laravel 6.2框架的博客。

## 1. 用户认证
  * 添加composer依赖：composer require laravel/ui
  * 生成UI视图：./artisan ui vue --auth
  * 安装node依赖：npm install
  * 编译vue：npm run dev
  * 创建数据库表：./artisan migrate:refresh
  * **Forgot Your Password? ** 页面发送邮件时报错
      * local.ERROR: Expected response code 250 but got code "553", with message "553 Mail from must equal authorized user
      * **解决：**在配置文件.env中添加MAIL_FROM_ADDRESS和MAIL_FROM_NAME，并且MAIL_FROM_ADDRESS与MAIL_USERNAME相等。

## 2. 

