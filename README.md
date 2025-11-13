# task-manager
# Roadmap — Yii2 Task Manager (Checklist)

## Milestone 0 — Chuẩn Bị
- [ ] Tạo GitHub repository và push commit đầu tiên
- [ ] Tạo file `.env.example`
- [ ] Tạo cấu trúc project hoặc copy skeleton
- [ ] Cài Docker & Docker Compose
- [ ] Tạo file `.env` từ `.env.example`

## Milestone 1 — Docker Setup
- [ ] Tạo `docker/php.dockerfile`
- [ ] Tạo `docker/nginx.conf`
- [ ] Tạo `docker-compose.yml` (php, nginx, mysql, redis)
- [ ] Chạy: `docker-compose up -d --build`
- [ ] Kiểm tra: `docker ps`
- [ ] Vào container PHP: `docker-compose exec php bash`

## Milestone 2 — Cấu hình Yii2 + Database
- [ ] Cấu hình `config/db.php` (đọc .env)
- [ ] Tạo migration: user, task, role, activity_log
- [ ] Chạy migrations: `php yii migrate/up --interactive=0`
- [ ] Kiểm tra bảng trong MySQL

## Milestone 3 — Authentication + RBAC
- [ ] Tạo `User` model + hash password
- [ ] Tạo chức năng login, signup, logout
- [ ] Cấu hình RBAC (DbManager)
- [ ] Tạo roles: admin, user
- [ ] Seed admin user

## Milestone 4 — Task CRUD + UI
- [ ] Tạo model `Task`
- [ ] Tạo controller CRUD cho Task
- [ ] Tạo views: index, create, update, view
- [ ] Thêm filter, sort
- [ ] Thêm upload file đính kèm

## Milestone 5 — REST API + JWT
- [ ] Tạo module API hoặc controller API
- [ ] Cài JWT package
- [ ] Tạo endpoint CRUD cho task
- [ ] Bảo vệ API bằng JWT Filter
- [ ] Tạo Postman collection test API

## Milestone 6 — Queue (Background Jobs)
- [ ] Cài yii2-queue + redis
- [ ] Cấu hình queue trong `console.php`
- [ ] Tạo `MailQueueJob`
- [ ] Tạo `ExportTasksJob`
- [ ] Thêm job vào queue trong controller
- [ ] Chạy worker: `php yii queue/listen`

## Milestone 7 — Unit Tests (PHPUnit)
- [ ] Cài PHPUnit
- [ ] Tạo `phpunit.xml`
- [ ] Test model User
- [ ] Test model Task
- [ ] Test API cơ bản
- [ ] Chạy test: `vendor/bin/phpunit`

## Milestone 8 — CI (GitHub Actions)
- [ ] Tạo `.github/workflows/ci.yml`
- [ ] Step: composer install
- [ ] Step: phpunit
- [ ] Step: static analysis (optional)
- [ ] Kiểm tra CI chạy khi push/PR

## Milestone 9 — CD (Deploy)
- [ ] Chọn chiến lược deploy (SSH / Docker Registry)
- [ ] Tạo GitHub Secrets (SSH key, server info)
- [ ] Thêm bước deploy trong GitHub Actions
- [ ] Tự động migrate trên server
- [ ] Chạy queue worker trên server (systemd/docker)

## Milestone 10 — Final Polishing
- [ ] Thêm caching bằng Redis
- [ ] Thêm logging (activity log, error log)
- [ ] Thêm rate limiting cho API
- [ ] Viết README hoàn chỉnh
- [ ] Vẽ kiến trúc hệ thống
- [ ] Viết tài liệu API (OpenAPI/Swagger)
