## 基于 <a href="https://github.com/z-song/laravel-admin" target="__blank">laravel-admin</a> 实现的oa系统
默认用户 
> admin:admin   
> xiaozhang 123   
> xiaoli 123   
> xiaoming 123

## docker 安装
- 安装php依赖:  `docker run -v $(pwd):/app composer install --ignore-platform-reqs`
- 启动服务: `docker-compose up -d`
- 初始化表结构: `cat database/schema.sql | docker-compose exec -T mysql mysql -p123 -uroot admin_oa`
- 导入初始数据: `cat database/demo_data.sql | docker-compose exec -T mysql mysql -p123 -uroot admin_oa`
- 如果需要邮件服务: `docker-compose exec -T fpm php /web/artisan queue:work`

从 http://localhost:2000 访问   
可能会遇到权限问题，添加 storage, public/upload 两个目录读写权限，即可解决
