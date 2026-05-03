-- AIStory MySQL initialization
-- Creates database and user for Docker deployment
-- This runs on first container start via docker-entrypoint-initdb.d

CREATE DATABASE IF NOT EXISTS aistory
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'aistory'@'%' IDENTIFIED BY 'aistory_secret';

GRANT ALL PRIVILEGES ON aistory.* TO 'aistory'@'%';

FLUSH PRIVILEGES;
