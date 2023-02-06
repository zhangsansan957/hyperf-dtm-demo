# 介绍
Hyperf框架使用dtm的demo项目

# 准备

 - PHP7.4
 - Swoole PHP extension >= 4.6.6，and Disabled `Short Name`
 - OpenSSL PHP extension
 - JSON PHP extension
 - PDO PHP extension （If you need to use MySQL Client）
 - Redis PHP extension （If you need to use Redis Client）
 - Protobuf PHP extension （If you need to use gRPC Server of Client）

# 运行项目
1. 初始化数据迁移`php bin/hyperf.php migrate:install`

2. 初始化数据库`php bin/hyperf.php migrate`

3. 初始化测试数据

   ``` sql
   INSERT INTO `goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t1', 100, 0, now(), now());
   INSERT INTO `goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t2', 100, 0, now(), now());
   INSERT INTO `goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t3', 100, 0, now(), now());
   
   INSERT INTO `other_goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t1', 100, 0, now(), now());
   INSERT INTO `other_goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t2', 100, 0, now(), now());
   INSERT INTO `other_goods` (`code`, `useful_num`, `lock_num`, `created_at`, `updated_at`) VALUES ('t3', 100, 0, now(), now());
   ```

   