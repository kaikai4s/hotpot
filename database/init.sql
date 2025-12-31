/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 * 
 * 数据库初始化脚本
 * 使用方法：mysql -u root -p < database/init.sql
 */

-- 创建数据库
CREATE DATABASE IF NOT EXISTS hotpot_miniapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建数据库用户（如果不存在）
CREATE USER IF NOT EXISTS 'hotpot_user'@'localhost' IDENTIFIED BY 'hotpot_password';

-- 授予权限
GRANT ALL PRIVILEGES ON hotpot_miniapp.* TO 'hotpot_user'@'localhost';
FLUSH PRIVILEGES;

-- 使用数据库
USE hotpot_miniapp;

