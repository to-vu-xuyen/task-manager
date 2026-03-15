# Hướng dẫn Cấu hình Docker Best Practices và Lệnh chạy (Yii2 Advanced)

## Giải đáp: Yii2 Advanced Architecture trong Docker
Yii2 Advanced là cấu trúc **Mono-repo** (nhiều app nằm trong 1 source). `frontend`, `backend`, `api` chỉ là các **Entry Point**, tất cả đều phụ thuộc vào `common/` và `vendor/`.

1.   **Vị trí của common, vendor, composer.json**:
    - Trong các Dockerfile bên dưới, dòng lệnh `COPY . .` đã copy **toàn bộ mã nguồn dự án** (tất cả các thư mục gốc) vào container (`/app`).
    - File [composer.json](file:///e:/ProgramFiles/wamp/www/task-manager/composer.json) chung ở Root Directory được xử lý bởi **Stage 1** tạo ra bộ thư mục `vendor/` cài sẵn gói thư viện, sau đó chép vào thư mục `/app/vendor/` bên trong container ở bước Stage 2.
    - Vì vậy, mỗi Container (`frontend`, `backend`, `api`) đều chứa toàn bộ thư viện và mã code nội bộ `common` giống hệt nhau cũng như toàn bộ các API route và Frontend template.

2.  **Sự khác biệt khi tách các Container**:
    - Điểm khác biệt duy nhất của các Dockerfile này là **Document Root** của Apache (đích đến của máy chủ web nhận Client Request). Ví dụ, Container API sẽ trỏ Apache vào vùng `/app/api/web`. Khi Web Server bắt đầu đọc từ vùng này, hệ thống Autoloader tự động nạp framework và thư viện từ `vendor/` và `common/` ở tầng root folder `/app`.

---

## 1. [.dockerignore](file:///e:/ProgramFiles/wamp/www/task-manager/.dockerignore)
Tạo file này tại thư mục gốc của dự án để ngăn chặn đưa các file rác, dữ liệu cục bộ vào Docker Build context giúp siêu nhẹ:

```ignore
.git
.gitignore
.env
vendor/
node_modules/
backend/runtime/
frontend/runtime/
console/runtime/
backend/web/assets/*
!backend/web/assets/.gitignore
frontend/web/assets/*
!frontend/web/assets/.gitignore
tests/_output/
```

## 2. Cấu trúc Dockerfile
Tạo các thư mục Dockerfile tương ứng của từng phân mảnh. Bạn có thể thấy [api/Dockerfile](file:///e:/ProgramFiles/wamp/www/task-manager/api/Dockerfile), [backend/Dockerfile](file:///e:/ProgramFiles/wamp/www/task-manager/backend/Dockerfile), và [frontend/Dockerfile](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/Dockerfile) chỉ khác nhau duy nhất đường dẫn Apache (dòng `RUN sed...`).

### [api/Dockerfile](file:///e:/ProgramFiles/wamp/www/task-manager/api/Dockerfile) (Tạo API Container)
```dockerfile
# Stage 1: Build dependencies
FROM composer:latest AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Stage 2: Production image
FROM yiisoftware/yii2-php:8.1-apache

# Change document root for Apache tới thư mục web của API
RUN sed -i -e 's|/app/web|/app/api/web|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /app

# Copy thư viện từ builder stage (tránh vác theo cache sinh ra dung lượng file rác dư thừa)
COPY --from=vendor /app/vendor/ /app/vendor/

# Copy TOÀN BỘ APPLICATION CODE (Bao gồm common/, console/...)
COPY . .

# Chuyển quyền folder để ghi log Cache
RUN mkdir -p api/runtime api/web/assets && \
    chown -R www-data:www-data api/runtime api/web/assets

# Basic healthcheck for Apache
HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost:80/ || exit 1
```

### [backend/Dockerfile](file:///e:/ProgramFiles/wamp/www/task-manager/backend/Dockerfile)
```dockerfile
# Stage 1: Build dependencies
FROM composer:latest AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Stage 2: Production image
FROM yiisoftware/yii2-php:8.1-apache
RUN sed -i -e 's|/app/web|/app/backend/web|g' /etc/apache2/sites-available/000-default.conf
WORKDIR /app

COPY --from=vendor /app/vendor/ /app/vendor/
COPY . .
RUN mkdir -p backend/runtime backend/web/assets && \
    chown -R www-data:www-data backend/runtime backend/web/assets

HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost:80/ || exit 1
```

## 3. [docker-compose.yml](file:///e:/ProgramFiles/wamp/www/task-manager/docker-compose.yml)
Nâng cấp bản 3.8, đưa tất cả qua mạng bí mật `yii-net`, khởi chạy 3 containers bao gồm MySQL và thiết lập liên kết nội bộ với API, Frontend, Backend.

```yaml
version: '3.8'

x-app-defaults: &app-defaults
  restart: unless-stopped
  volumes:
    - ./:/app
    - ~/.composer-docker/cache:/root/.composer/cache:delegated
  networks:
    - yii-net

services:
  # Cấu hình API Server
  api:
    <<: *app-defaults
    build: 
      context: .
      dockerfile: api/Dockerfile
    ports:
      - "22080:80"

  # Cấu hình Backend Server
  backend:
    <<: *app-defaults
    build: 
      context: .
      dockerfile: backend/Dockerfile
    ports:
      - "21080:80"
    depends_on:
      mysql:
        condition: service_healthy

  # Cấu hình Frontend Server
  frontend:
    <<: *app-defaults
    build: 
      context: .
      dockerfile: frontend/Dockerfile
    ports:
      - "20080:80"

  # MySQL Server
  mysql:
    image: mysql:5.7
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-verysecret}
      - MYSQL_DATABASE=${DB_NAME:-yii2advanced}
      - MYSQL_USER=${DB_USER:-yii2advanced}
      - MYSQL_PASSWORD=${DB_PASSWORD:-secret}
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - yii-net
    healthcheck:
      test: ["CMD-SHELL", "mysqladmin ping -h localhost -uroot -p$${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  mysql-data:

networks:
  yii-net:
    driver: bridge
```

## 4. Hướng dẫn Khởi chạy (Dành cho CommandLine)

Khởi động Terminal tại Root folder (nơi chứa file [docker-compose.yml](file:///e:/ProgramFiles/wamp/www/task-manager/docker-compose.yml)), và sử dụng các câu lệnh sau để vận hành hệ thống.

**1. Khởi chạy và Build Containers lần đầu (Cần thiết khi có Code Structure hay [composer.json](file:///e:/ProgramFiles/wamp/www/task-manager/composer.json) bị đổi):**
```bash
docker-compose up -d --build
```
Lệnh `-d` dùng để đặt hệ thống vào trạng thái ngầm (Daemon mode). `--build` cho phép tái biên tập Image và gọi đến Composer.

**2. Tải trực tiếp Logs theo thời gian thực:**
Khi hệ thống chạy ngầm hoặc báo lỗi, bạn có thể soi trực tiếp tiến trình lỗi thông qua logs:
```bash
# Xem log tổng của hệ thống
docker-compose logs -f

# Chỉ xem log lỗi của nhánh API đang xử lý
docker-compose logs -f api
```

**3. Khởi chạy Bash trên Container (Để gõ các lệnh nội bộ Framework như Migrate Database):**
Khi cần chạy Yii Migitations (Migration cho database) hoặc cập nhật Cache, bạn sẽ cần tương tác trực tiếp bên trong Linux Terminal của Container:
```bash
docker-compose exec backend bash
```
Sau đó khi truy cập hệ thống bên trong thành công, bạn sẽ gõ trực tiếp được Framework:
```bash
php yii migrate
```

**4. Khởi chạy Component Unit test (VD gọi qua Framework CodeCeption)**
```bash
# Chạy Unit Test Backend từ bên ngoài thông qua Docker Process Execute
docker-compose exec backend php vendor/bin/codecept run unit
```

**5. Dừng hệ thống (Stop Process) và Hủy hoàn toàn Image cũ:**
```bash
# Tạm dừng toàn bộ dịch vụ nhưng giữ nguyên Container
docker-compose stop

# Hủy bỏ và xóa hoàn toàn Network/Container (Giữ lại cấu trúc Data file)
docker-compose down
```

---

## 5. Troubleshooting (Xử lý lỗi thường gặp)

### Lỗi `InvalidConfigException - The directory does not exist: /app/backend/web/assets`
Lỗi này xảy ra do cơ chế **Bind Mount** của Docker. 
- **Nguyên nhân**: Mặc dù trong Dockerfile có lệnh `RUN mkdir`, nhưng khi bạn dùng `volumes: ./:/app`, thư mục tại máy host (máy tính của bạn) sẽ "đè" lên thư mục `/app` trong container. Nếu máy host của bạn chưa có thư mục `assets`, container sẽ báo lỗi không tìm thấy.
- **Cách fix nhanh**: Bạn chỉ cần tạo thủ công các thư mục trống sau trên máy tính của mình (lệnh CMD/PowerShell tại thư mục gốc dự án):
```bash
mkdir backend/web/assets
mkdir backend/runtime
mkdir frontend/web/assets
mkdir frontend/runtime
mkdir api/web/assets
mkdir api/runtime
```
*Sau khi tạo xong, hãy refresh lại trình duyệt.*

### Lỗi `Access denied for user 'root'@'localhost'`
Lỗi này thường xảy ra trong logs của container MySQL khi lệnh `healthcheck` cố gắng kiểm tra trạng thái database mà không cung cấp mật khẩu root.
**Cách fix:** Trong [docker-compose.yml](file:///e:/ProgramFiles/wamp/www/task-manager/docker-compose.yml), cập nhật phần `healthcheck` của `mysql` như sau:
```yaml
    healthcheck:
      test: ["CMD-SHELL", "mysqladmin ping -h localhost -uroot -p$${MYSQL_ROOT_PASSWORD}"]
```

### Lỗi không truy cập được API
Hãy kiểm tra port mapping trong [docker-compose.yml](file:///e:/ProgramFiles/wamp/www/task-manager/docker-compose.yml). Đảm bảo port phía sau (container port) là `80`.
**Sai:** `- "22080:808"`
**Đúng:** `- "22080:80"`
