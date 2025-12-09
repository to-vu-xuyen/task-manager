# ✅ CHECKLIST CHI TIẾT – TASK MANAGER (Yii2)
*(Đã chuyển, sắp xếp theo thứ tự ưu tiên và chia thành bước nhỏ để dễ theo dõi)*

## 1) Chuẩn bị môi trường
- [ ] Tạo `.env.example` (APP_ENV, APP_KEY, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, REDIS_HOST, REDIS_PORT, JWT_SECRET, MAIL_*).
- [ ] Thiết lập cấu trúc thư mục chuẩn (`src/`, `config/`, `runtime/`, `web/`, `docker/`).
- [ ] Viết `docker-compose.yml` cho PHP-FPM, Nginx, MySQL, Redis.
- [ ] Tạo `Dockerfile` cho PHP (cài ext: `pdo_mysql`, `redis`, `intl`, `gd`/`imagick` nếu cần).
- [ ] Kiểm tra chạy Yii2 trong Docker (dev stack).

## 2) Database — Migrations & Seeds
- [ ] Migration: `user` (id, username, email, password_hash, auth_key, status, created_at, updated_at).
- [ ] Migration: `task` (id, uuid, title, description, status, priority, creator_id, assignee_id, due_at, created_at, updated_at).
- [ ] Migration: `attachment` / `file_uploads` (id, task_id, filename, path, mime, size, created_at).
- [ ] Migration: `activity_log` (id, user_id, action, target_type, target_id, meta JSON, created_at).
- [ ] Migration: RBAC tables nếu dùng `DbManager` (auth_item, auth_item_child, auth_assignment, auth_rule).
- [ ] Seed: admin user + initial roles/permissions.

## 3) Authentication
- [ ] User model + password hashing (bcrypt/argon2) + auth_key.
- [ ] Signup endpoint / form.
- [ ] Login endpoint / form + session or JWT.
- [ ] Logout.
- [ ] Password reset (token, expiry) — optional.

## 4) RBAC — Roles & Permissions
- [ ] Cấu hình `authManager` = `yii\rbac\DbManager`.
- [ ] Tạo roles: `admin`, `manager`, `user`.
- [ ] Tạo permissions: `task.create`, `task.update`, `task.assign`, `task.view`, `task.delete`, v.v.
- [ ] Seed role assignments cho admin.

## 5) Task Management (Core)
- [ ] Task model + service layer.
- [ ] CRUD endpoints / controllers.
- [ ] Assign user to task.
- [ ] Status, priority, due date.
- [ ] Filter, sort, pagination, search.
- [ ] Activity logs on changes.

## 6) File Uploads
- [ ] Upload service — validate MIME/type & size.
- [ ] Store files to mounted volume / S3 (configurable via `.env`).
- [ ] Store metadata in DB.
- [ ] Serve/download with authorization.

## 7) Queue System
- [ ] Configure Redis queue (`yiisoft/yii2-queue` or equivalent).
- [ ] Implement worker command (`yii queue/listen`) and sample jobs (email, cleanup).
- [ ] Supervisor or container orchestration for worker processes.

## 8) Scheduled Tasks / Cron
- [ ] Cron for reminders (deadline reminders), cleanup, overdue checks.
- [ ] Implement via console command and schedule runner.

## 9) Email Notifications
- [ ] SMTP config from `.env`.
- [ ] Email templates for events (task created, assigned, comments, reminders).
- [ ] Send emails via queue.

## 10) API Layer (REST)
- [ ] Module `/api/v1`.
- [ ] JWT auth for API endpoints.
- [ ] Endpoints: `/auth/login`, `/auth/refresh`, `/tasks`, `/tasks/{id}/comments`, `/users`.
- [ ] Response formatter and error handling.

## 11) Testing
- [ ] Set up PHPUnit, phpunit.xml.
- [ ] Unit tests: User, Task models/services.
- [ ] Integration tests: API endpoints (use SQLite or MySQL service in CI).
- [ ] Mock mail and queue.

## 12) CI/CD
- [ ] GitHub Actions (or GitLab CI) pipeline:
- [ ] `composer install`
- [ ] Run static analysis (PHPStan/Psalm)
- [ ] Run PHPUnit
- [ ] Lint (PHP-CS-Fixer)
- [ ] Build Docker image and push to registry.
- [ ] Deploy to staging/production with migration step.

## 13) Deployment & Monitoring
- [ ] Write production `.env` template and deploy docs.
- [ ] Run migrations safely during deploy (with downtime or non-blocking migrations policy).
- [ ] Configure logging, monitoring, and backups.
- [ ] Enable OPCache and caching.

---

## Quick actions (first 7 days)
1. Commit `.env.example` + `docker-compose.yml` minimal.  
2. Create migrations for `user` + `task`.  
3. Implement User signup/login + seed admin.  
4. Skeleton Task CRUD (no UI, API only).  
5. Add basic PHPUnit tests for User and Task.


