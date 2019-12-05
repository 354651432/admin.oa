<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## 基于 <a href="https://github.com/z-song/laravel-admin" target="__blank">laravel-admin</a> 实现的oa系统
默认用户 
> admin:admin   
> xiaozhang 123   
> xiaoli 123   
> xiaoming 123

## docker 安装
- 安装php依赖:  `docker run -v $(pwd):/app composer install --ignore-platform-reqs`
- 启动服务: `docker-compose up -d -p admin_oa`
- 创建数据库: `docker-compose exec mysql mysql -uroot -p123 -e "create database admin_oa"`
- 初始化表结构: `cat database/schema.sql | docker-compose exec -T mysql mysql -uroot -p123 admin_oa`
- 导入初始数据: `cat database/demo_data.sql | docker-compose exec -T mysql mysql -uroot -p123 admin_oa`
- 如果需要邮件服务: `docker-compose exec -T fpm php /web/artisan queue:work`

从 http://localhost:2000 访问
