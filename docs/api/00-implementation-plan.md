# API Integration with JWT & Bearer Token Authentication

## Background

Tích hợp REST API module vào Yii2 Advanced task-manager, hỗ trợ 2 phương thức xác thực:
1. **JWT (JSON Web Token)** — stateless, phù hợp cho mobile/SPA clients
2. **Bearer Token (API Key)** — đơn giản, phù hợp cho server-to-server integration

> [!IMPORTANT]
> Toàn bộ code đề xuất sẽ nằm trong **artifact riêng** (`api_reference_code.md`). Không sửa trực tiếp source code (theo Lesson 3).

## Skills đang sử dụng
- **api-patterns** — REST design, auth pattern selection
- **api-security-best-practices** — JWT flow, rate limiting, input validation  
- **architecture** — Decision framework, SOLID principles
- **backend-dev-guidelines** — Layered architecture patterns

---

## User Review Required

> [!IMPORTANT]
> **JWT có nên dùng không?** Phân tích:
> 
> | Tiêu chí | JWT | Bearer Token (API Key) |
> |----------|-----|----------------------|
> | Stateless | ✅ Không cần query DB mỗi request | ❌ Cần query DB verify token |
> | Revoke được | ❌ Phải dùng blacklist/refresh rotation | ✅ Xóa token khỏi DB |
> | Mobile/SPA | ✅ Phù hợp | ⚠️ Được nhưng kém linh hoạt |
> | Server-to-server | ⚠️ Overkill | ✅ Đơn giản hiệu quả |
> | Security | ⚠️ Token theft = full access đến hết hạn | ✅ Revoke ngay lập tức |
> 
> **Đề xuất**: Dùng **cả hai**. JWT cho user authentication (login/logout), Bearer Token cho API integration (3rd-party). Đây là pattern phổ biến trong thực tế.

> [!WARNING]
> Cần migration tạo bảng `api_token` và thêm cột `access_token` vào bảng `user`. User cần chạy migration sau khi apply code.

---

## Proposed Changes

### Architecture Overview

```
HTTP Request (API)
    ↓
api/v1/config/main.php (module config, CORS, rate limit)
    ↓
Authenticator (JWT or Bearer Token)
    ↓
ApiController (extends BaseApiController)
    ↓
Service Layer (existing: TaskService, AuthService...)
    ↓
Repository → Database
```

```mermaid
graph TD
    A["Client Request"] --> B{"Authorization: Bearer TOKEN"}
    B --> C["CompositeAuth Filter"]
    C --> D{"Token format?"}
    D -->|"3 parts (xxx.yyy.zzz)"| E["JwtHttpBearerAuth"]
    D -->|"Random string"| F["HttpBearerAuth (API Key)"]
    B -->|"No Header"| G["401 Unauthorized"]
    E --> H["Decode JWT + Verify Signature"]
    F --> I["Query api_token table"]
    H --> J{"Valid?"}
    I --> J
    J -->|"Yes"| K["Set User Identity"]
    J -->|"No"| G
    K --> L["Controller Action"]
    L --> M["Service Layer"]
    M --> N["Response JSON"]
```

> [!NOTE]
> ### Giải thích: Tại sao cả JWT lẫn API Key đều dùng `Authorization: Bearer`?
>
> **`Bearer`** là **cơ chế vận chuyển** (transport) theo chuẩn RFC 6750 — nó chỉ định **cách gửi** token trong HTTP header. **JWT** và **API Key** là **định dạng token** (format) — chúng quy định **nội dung** bên trong.
>
> | Khái niệm | Vai trò | Ví dụ |
> |-----------|---------|-------|
> | `Bearer` | Transport mechanism (cách gửi) | `Authorization: Bearer <token>` |
> | JWT | Token format (nội dung) | `eyJhbG...` (3 phần, ngăn cách bởi `.`) |
> | API Key | Token format (nội dung) | `tk_a1b2c3d4e5f6...` (random string) |
>
> Server phân biệt loại token bằng **format**: JWT luôn có 3 phần (`header.payload.signature`), API Key thì không. Cả hai đều gửi qua cùng một header `Authorization: Bearer`.
>
> ```
> # JWT token (3 parts separated by dots)
> Authorization: Bearer eyJhbGci.eyJ1c2Vy.SflKxwRJ
>
> # API Key (random string, no dots)
> Authorization: Bearer tk_a1b2c3d4e5f6g7h8i9j0
> ```

---

### Component 1: API Module Configuration

#### [NEW] `api/config/main.php`
- Module config cho REST API
- URL rules: `/api/v1/auth/login`, `/api/v1/tasks`, etc.
- JSON response formatter
- CORS filter
- Error handler trả JSON thay vì HTML

#### [NEW] `api/modules/v1/Module.php`
- Yii2 sub-module class cho API v1
- `controllerNamespace` trỏ đến `api\modules\v1\controllers`

---

### Component 2: JWT Authentication

#### [NEW] `common/components/jwt/JwtHelper.php`
- `encode(array $payload): string` — tạo JWT token
- `decode(string $token): array|null` — verify + decode
- `generateRefreshToken(): string`
- Sử dụng `HMAC-SHA256` với `JWT_SECRET` từ `.env`
- Pure PHP implementation (không cần thêm package) sử dụng `hash_hmac`

#### [NEW] `common/components/jwt/JwtHttpBearerAuth.php`
- Extends `yii\filters\auth\HttpBearerAuth`
- Override `authenticate()` để decode JWT
- Set identity cho Yii user component

---

### Component 3: Bearer Token (API Key) Authentication

#### [NEW] `common/models/ApiToken.php`
- ActiveRecord model cho bảng `api_token`
- Fields: `id`, `user_id`, `token`, `name`, `scopes`, `expires_at`, `last_used_at`, `created_at`
- Method: `generateToken()`, `isExpired()`, `hasScope()`

#### [NEW] Migration: `create_api_token_table`
- Tạo bảng `api_token` với indexes

---

### Component 4: User Model Extension (API Identity)

#### [MODIFY] Concept cho `common/models/User.php`
- Implement `findIdentityByAccessToken()` thay vì throw exception
- Hỗ trợ cả JWT decode và API token lookup
- Phân biệt loại token qua format (JWT = 3 parts separated by `.`)

---

### Component 5: API Controllers

#### [NEW] `api/controllers/BaseApiController.php`
- Extends `yii\rest\Controller`
- Mixin: `CompositeAuth` (JWT + Bearer)
- Common response methods: `success()`, `error()`, `paginated()`
- Default behaviors: authenticator, rate limiter, CORS

#### [NEW] `api/controllers/AuthController.php`
- `POST /auth/login` — username/password → JWT + refresh token
- `POST /auth/refresh` — refresh token → new JWT
- `POST /auth/logout` — invalidate refresh token
- `POST /auth/tokens` — tạo API token (Bearer)
- `DELETE /auth/tokens/{id}` — revoke API token

#### [NEW] `api/controllers/TaskController.php`
- `GET /tasks` — list tasks (paginated)
- `GET /tasks/{id}` — view task
- `POST /tasks` — create task
- `PUT /tasks/{id}` — update task
- `DELETE /tasks/{id}` — soft delete task
- Sử dụng existing `TaskService` + `TaskServiceInterface`

---

### Component 6: API Response & Error Handling

#### [NEW] `api/components/ApiErrorHandler.php`
- JSON error responses thay vì HTML
- Consistent error format: `{ "success": false, "error": { "code": 401, "message": "..." } }`
- Không expose stack trace trong production

#### [NEW] `api/components/ApiResponse.php`
- Envelope pattern: `{ "success": true, "data": {...}, "meta": { "page": 1, "total": 50 } }`

---

### Component 7: Rate Limiting

#### Sử dụng `yii\filters\RateLimiter`
- Implement `RateLimitInterface` trên User model
- Config: 60 requests/phút cho general API, 5 requests/phút cho auth endpoints
- Redis-backed counter

---

### Component 8: Sensitive Data Protection

Khi data chứa thông tin nhạy cảm (PII, financial data, health records...), cần bảo vệ ở 3 tầng:

#### Tầng 1: Mã hóa dữ liệu lưu trữ (Encryption at Rest)

##### [NEW] `common/components/security/DataEncryptor.php`
- Sử dụng **Yii2 Security component** (built-in, không cần package bên ngoài)
- `encryptByKey()` / `decryptByKey()` — AES-256 + authenticated encryption (MAC tự động)
- Key derivation bằng HKDF (an toàn hơn PBKDF2 cho server-side)

```php
// Mã hóa (encrypt)
$encrypted = Yii::$app->security->encryptByKey($sensitiveData, $encryptionKey);

// Giải mã (decrypt) — tự động verify MAC, nếu bị tamper → return false
$decrypted = Yii::$app->security->decryptByKey($encrypted, $encryptionKey);
```

**Ứng dụng:** Mã hóa trước khi lưu DB cho các trường nhạy cảm (SSN, số tài khoản, token...).

##### [NEW] `common/behaviors/EncryptedFieldBehavior.php`
- ActiveRecord behavior tự động encrypt/decrypt các trường chỉ định
- Transparent: Model dùng bình thường, data tự động được mã hóa khi save và giải mã khi load

```php
// Cách dùng trên Model:
public function behaviors() {
    return [
        'encrypted' => [
            'class' => EncryptedFieldBehavior::class,
            'attributes' => ['phone_number', 'id_card', 'bank_account'],
        ],
    ];
}
```

#### Tầng 2: Che giấu dữ liệu trong API Response (Data Masking)

##### [NEW] `api/components/DataMasker.php`
- Masking các trường nhạy cảm trước khi trả về API response
- Pattern: `***` cho phone, `****1234` cho card number, truncate email

```php
// Input:  "0901234567"
// Output: "090***4567"

// Input:  "4111111111111111" 
// Output: "****1111"

// Input:  "user@company.com"
// Output: "u***@company.com"
```

**Khi nào dùng?** Khi API consumer **không cần full data** (ví dụ: hiển thị phone đã mask trên UI).

#### Tầng 3: Xác thực toàn vẹn dữ liệu (Data Integrity / HMAC)

##### [NEW] `common/components/security/DataSigner.php`
- HMAC-SHA256 để ký và xác thực data = đảm bảo **data không bị sửa đổi**
- Use cases: webhook payloads, inter-service communication, callback URLs

```php
// Ký data
$signature = DataSigner::sign($payload, $secretKey);
// → "sha256=abc123..."

// Xác thực 
$isValid = DataSigner::verify($payload, $signature, $secretKey);
// → true/false
```

#### Khi nào dùng tầng nào?

| Scenario | Tầng 1 (Encrypt) | Tầng 2 (Mask) | Tầng 3 (HMAC Sign) |
|----------|:-:|:-:|:-:|
| Lưu số CMND/CCCD vào DB | ✅ | — | — |
| Hiển thị SĐT user trên API | — | ✅ | — |
| Webhook gửi đi cho partner | — | — | ✅ |
| Token/Password lưu DB | ✅ | — | — |
| Export data nhạy cảm qua API | ✅ | ✅ | ✅ |
| Verify callback từ payment gateway | — | — | ✅ |

> [!TIP]
> **`.env` cần thêm:**
> ```
> # Encryption key cho sensitive data (khác JWT_SECRET)
> DATA_ENCRYPTION_KEY=change_this_to_random_32_char_string
> DATA_SIGNING_KEY=change_this_to_another_random_string
> ```

---

### File Structure Summary (with API Versioning)

> [!NOTE]
> ### Chiến lược versioning: Module-based
>
> Có 3 cách phổ biến để version API:
>
> | Cách | Ví dụ | Ưu/nhược |
> |------|-------|----------|
> | **URL path (module)** | `/api/v1/tasks` | ✅ Rõ ràng, dễ maintain, Yii2 native |
> | URL query | `/api/tasks?v=1` | ❌ Dễ quên, khó enforce |
> | Header | `Accept: application/vnd.api.v1+json` | ⚠️ Phức tạp, khó debug |
>
> **Chọn: Module-based** — mỗi version là 1 Yii2 sub-module trong `api/modules/v1/`. Khi cần v2, tạo thêm `api/modules/v2/` mà **không ảnh hưởng v1** (OCP — Open/Closed Principle).
>
> ```
> # URL mapping:
> /api/v1/auth/login   → api/modules/v1/controllers/AuthController::actionLogin
> /api/v1/tasks        → api/modules/v1/controllers/TaskController::actionIndex
>
> # Tương lai (v2):
> /api/v2/tasks        → api/modules/v2/controllers/TaskController::actionIndex
> ```

```
task-manager/
├── api/                              ← [NEW] API app (tương tự backend/frontend)
│   ├── config/
│   │   └── main.php                  ← URL rules, CORS, JSON formatter
│   ├── modules/
│   │   └── v1/                       ← [VERSIONING] API v1 sub-module
│   │       ├── Module.php            ← Yii2 Module class cho v1
│   │       ├── controllers/
│   │       │   ├── BaseApiController.php
│   │       │   ├── AuthController.php
│   │       │   └── TaskController.php
│   │       └── resources/            ← Response transformers (optional)
│   ├── components/                   ← Shared across all versions
│   │   ├── ApiErrorHandler.php
│   │   ├── ApiResponse.php
│   │   └── DataMasker.php
│   └── web/
│       ├── index.php
│       └── .htaccess
├── common/
│   ├── components/
│   │   ├── jwt/
│   │   │   ├── JwtHelper.php
│   │   │   └── JwtHttpBearerAuth.php
│   │   └── security/                 ← [NEW] Sensitive data protection
│   │       ├── DataEncryptor.php
│   │       └── DataSigner.php
│   ├── behaviors/
│   │   └── EncryptedFieldBehavior.php
│   ├── models/
│   │   ├── User.php                  ← [MODIFY] findIdentityByAccessToken
│   │   └── ApiToken.php              ← [NEW]
│   └── config/
│       └── main.php                  ← [MODIFY] DI container bindings
└── console/
    └── migrations/
        └── mXXX_create_api_token_table.php  ← [NEW]
```

> [!TIP]
> **Tại sao `components/` nằm ngoài `modules/v1/`?**
> Vì `ApiErrorHandler`, `ApiResponse`, `DataMasker` là **shared** — mọi version đều dùng chung. Chỉ controllers/resources mới khác nhau giữa các version.

---

## Verification Plan

### Automated Tests
Hiện tại project chỉ có `LoginFormTest.php`. Do code nằm trong artifact (không apply trực tiếp), automated testing sẽ cần thực hiện sau khi user apply code.

**Đề xuất test commands sau khi apply:**
```bash
# Chạy migration
php yii migrate --interactive=0

# Test JWT encode/decode
php yii test/jwt

# Test API endpoints bằng curl
curl -X POST http://localhost/task-manager/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Test protected endpoint
curl -X GET http://localhost/task-manager/api/v1/tasks \
  -H "Authorization: Bearer <jwt_token>"
```

### Manual Verification
1. **Kiểm tra login endpoint** → nhận được JWT + refresh token
2. **Kiểm tra refresh** → nhận JWT mới
3. **Kiểm tra protected endpoint** → 200 OK với valid token, 401 với invalid
4. **Kiểm tra Bearer API key** → tạo key, dùng key gọi API
5. **Kiểm tra rate limit** → gọi endpoint liên tục, nhận 429 sau khi vượt limit
6. **Kiểm tra CORS** → request từ origin khác, nhận headers phù hợp
7. **Kiểm tra data encryption** → lưu data nhạy cảm, verify DB chứa ciphertext (không đọc được), query lại được plaintext
8. **Kiểm tra data masking** → API response trả về phone/email đã mask
9. **Kiểm tra HMAC signing** → ký payload, tamper data, verify phải fail

> [!NOTE]  
> Do code sẽ nằm trong artifact, user sẽ tự apply và verify. Tôi sẽ cung cấp hướng dẫn test chi tiết trong artifact.
