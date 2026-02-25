# Phân Tích Task Attachment — Những Gì Còn Thiếu & Cần Sửa

## Skills Đang Sử Dụng

| # | Skill | Mục đích |
|---|-------|----------|
| 1 | `php-pro` | SOLID, PHP 8+, type safety, PSR standards |
| 2 | `clean-code` | Naming, functions, comments, error handling, class design |
| 3 | `code-review-excellence` | Correctness, security, performance, maintainability |
| 4 | `find-bugs` | Bugs, security vulnerabilities, IDOR/CSRF checklist |
| 5 | `production-code-audit` | Architecture, security, performance, testing gaps |
| 6 | `file-uploads` | File upload security: magic bytes, path traversal, size limits |
| 7 | `error-handling-patterns` | Exception handling, error propagation, graceful degradation |
| 8 | `architect-review` | SOLID principles, Repository/Service patterns, DI, layer separation |

## Files Đã Phân Tích

| File | Lines |
|------|-------|
| [TaskAttachment.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/models/task/TaskAttachment.php) | 70 |
| [TaskAttachmentForm.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/forms/task/TaskAttachmentForm.php) | 47 |
| [TaskAttachmentDto.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/dto/task/TaskAttachmentDto.php) | 70 |
| [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php) | 76 |
| [TaskAttachmentRepositoryInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php) | 18 |
| [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php) | 132 |
| [TaskAttachmentServiceInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentServiceInterface.php) | 17 |
| [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php) | 190 |
| [Migration](file:///e:/ProgramFiles/wamp/www/task-manager/console/migrations/m260217_075520_create_task_attachment_table.php) | 56 |
| [view.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/views/task/view.php) | 79 |

---

## 🔴 CRITICAL — Runtime Crash & Security (4 issues)

### 1. Undefined variable `$attachmentForm` → crash

**Skill**: `find-bugs` · **File**: [TaskController.php#L176](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L168-L187)

```php
$model = new TaskAttachmentForm();         // line 170: khai báo $model
// ...
if (empty($attachmentForm->files)) {       // line 176: ❌ dùng $attachmentForm — UNDEFINED
```

**Fix**: Đổi `$attachmentForm` → `$model`.

---

### 2. Redirect sai sau khi xoá attachment

**Skill**: `find-bugs` · **File**: [TaskController.php#L157-L161](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L157-L161)

```php
public function actionDeleteAttachment($id)
{
    $this->taskAttachmentService->deleteAttachment($id);
    return $this->redirect(['view', 'id' => $id]); // ❌ $id = attachment_id, KHÔNG phải task_id
}
```

**Fix**: Lấy `task_id` từ attachment trước khi xoá, rồi redirect về task đó.

---

### 3. Null dereference — gọi upload trước khi check task

**Skill**: `find-bugs`, `error-handling-patterns` · **File**: [TaskController.php#L63-L84](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L63-L84)

```php
$task = $this->taskService->create($model);
$this->handleAttachmentUpload($task->id);  // ❌ Chạy TRƯỚC khi check $task !== null
if ($task !== null) {                       // Check này đã quá muộn
```

---

### 4. IDOR — Không kiểm tra ownership khi xoá/download attachment

**Skill**: `find-bugs` (Security Checklist: Authorization/IDOR), `file-uploads`

```php
// actionDeleteAttachment: bất kỳ user nào cũng xoá được attachment bất kỳ
$this->taskAttachmentService->deleteAttachment($id); // ❌ Không check user_id
```

> [!CAUTION]
> Đây là **IDOR vulnerability** — user A có thể xoá file của user B bằng cách đoán `attachment_id`.

---

## 🟠 SECURITY — File Upload Vulnerabilities (3 issues)

### 5. Chỉ validate file extension, không check magic bytes

**Skill**: `file-uploads` (Sharp Edge: "Trusting client-provided file type → CRITICAL")

```php
// TaskAttachmentForm.php — chỉ check extension
'extensions' => 'jpg, jpeg, png, pdf, doc, docx, xls, xlsx, ppt, pptx',
```

User có thể rename `malware.exe` → `malware.jpg` và upload thành công.

**Fix**: Validate file MIME type qua magic bytes (PHP `finfo_file()`), không tin `$file->type`.

---

### 6. Filename không được sanitize → Path Traversal risk

**Skill**: `file-uploads` (Sharp Edge: "User-controlled filename allows path traversal → CRITICAL")

```php
// Service lưu original filename vào DB
$taskAttachment->file_name = $file->name; // ❌ Không sanitize
```

Tuy `file_name` chỉ lưu DB (không dùng cho path), nhưng nếu hiển thị trên view → **XSS risk** nếu không escape.

---

### 7. File type lưu từ client-provided `$file->type`

**Skill**: `file-uploads`, `find-bugs`

```php
$taskAttachment->file_type = $file->type; // ❌ Client có thể giả mạo MIME type
```

**Fix**: Dùng `finfo_file()` hoặc `mime_content_type()` để detect thực tế.

---

## 🟡 SOLID & Architecture Violations (5 issues)

### 8. Transaction đặt sai layer (Vi phạm SRP)

**Skill**: `architect-review` (Repository/Unit of Work pattern), `clean-code` (SRP)

[TaskAttachmentRepository.php#L11-L29](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php#L11-L29)

| Method | Có Transaction? | Hợp lý? |
|--------|----------------|---------|
| `save()` | ✅ Có | ❌ Không cần — chỉ save 1 record |
| `delete()` | ❌ Không | ❌ Không nhất quán |
| `deleteAllByTaskId()` | ✅ Có | ✅ Hợp lý — multiple deletes |

> [!IMPORTANT]
> Transaction nên ở **Service layer** để wrap cả file I/O + DB operation thành 1 atomic unit. Repository chỉ nên CRUD đơn thuần.

---

### 9. Service làm quá nhiều việc — Vi phạm SRP

**Skill**: `architect-review`, `clean-code` (Functions: "Do One Thing")

`TaskAttachmentService` hiện đang:
1. Quản lý file system (tạo thư mục, lưu/xoá file)
2. Generate unique filenames
3. Build file paths
4. Business logic (validate, create model, orchestrate)

**Fix**: Tách `FileStorageService` chịu trách nhiệm file I/O.

---

### 10. `TaskAttachmentDto` tạo rồi nhưng KHÔNG sử dụng

**Skill**: `clean-code` (Dead Code), `production-code-audit`

- Service trả về `TaskAttachment[]` (ActiveRecord models) trực tiếp cho Controller
- `TaskAttachmentDto` có method `fromModel()` và `toArray()` nhưng **không file nào gọi**
- Vi phạm **Layer Separation** — Controller nhận trực tiếp model thay vì DTO

---

### 11. Interface không khớp Implementation

**Skill**: `architect-review` (Interface Segregation), `php-pro`

| Method | Interface | Implementation |
|--------|-----------|---------------|
| `getById()` | ❌ Thiếu | ✅ Có |
| `upload()` return type | `array` | `array` (nhưng thiếu phpdoc type hint) |

---

### 12. Repository `getById()` return type không nhất quán

**Skill**: `php-pro` (Type system mastery)

```php
// Interface khai báo nullable
public function getById(int $id): ?TaskAttachment;

// Implementation throw exception khi không tìm thấy — nhưng return type vẫn nullable
$attachment = TaskAttachment::findOne($id);
if (empty($attachment)) {
    throw new \DomainException("TaskAttachment not found: ID = {$id}");
}
return $attachment; // Sẽ luôn non-null do throw ở trên
```

**Fix**: Bỏ `?` trong return type → `TaskAttachment`, vì method throw exception khi null.

---

## 🔵 Missing Features (5 issues)

### 13. `actionDownloadAttachment` hoàn toàn rỗng

**Skill**: `production-code-audit` (Feature completeness)

```php
public function actionDownloadAttachment($id)
{
    // Body trống
}
```

---

### 14. Không có view file `upload-attachment.php`

**Skill**: `find-bugs`

Controller render `upload-attachment` nhưng file không tồn tại → sẽ gây exception khi truy cập route.

---

### 15. View chi tiết Task không hiển thị attachments

**Skill**: `production-code-audit`

[view.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/views/task/view.php) — Chỉ có `DetailView` cho task info, **không có** section attachment.

---

### 16. Không có Unit Tests

**Skill**: `production-code-audit` (Testing Gaps), `clean-code` (Unit Tests: F.I.R.S.T.)

Không tìm thấy bất kỳ test nào cho:
- `TaskAttachment` model
- `TaskAttachmentService`
- `TaskAttachmentRepository`
- Controller actions

---

### 17. Thiếu DB Index cho query performance

**Skill**: `production-code-audit` (Missing database indexes)

Migration không tạo index cho `task_id` (mặc dù FK tự tạo index trên MySQL/InnoDB, trên PostgreSQL thì không).

---

## ⚪ Code Quality (6 issues)

### 18. Commented-out code đầy rác

**Skill**: `clean-code` (Comments: "Don't Comment Bad Code—Rewrite It")

| File | Lines |
|------|-------|
| `TaskAttachmentService.php` | 39, 44, 56 |
| `TaskAttachmentRepository.php` | 33-34, 36, 38-39, 42 |
| `TaskService.php` | 39-44, 61-62, 91 |

---

### 19. `codecept_debug()` trong production code

**Skill**: `production-code-audit` (Code Quality), `find-bugs` (Information disclosure)

[TaskService.php#L94](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskService.php#L94) — Hàm debug Codeception trong production.

---

### 20. Error suppression `@unlink` — silent failure

**Skill**: `error-handling-patterns`, `clean-code` (Error Handling)

```php
@unlink($fullPath);   // Service.php line 54
@unlink($filePath);   // Service.php line 111
```

Dùng `@` suppress lỗi → không log được khi xoá file thất bại.

---

### 21. `TimestampBehavior` dùng `date()` — timezone mismatch

**Skill**: `php-pro`, `production-code-audit`

```php
'value' => date('Y-m-d H:i:s'), // PHP server timezone ≠ DB server timezone
```

**Fix**: Dùng `new Expression('NOW()')` hoặc `gmdate()`.

---

### 22. Flash message sai nội dung

**Skill**: `code-review-excellence`

```php
// actionUpdate nhưng nói "tạo"
Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
```

---

### 23. Model `TimestampBehavior` chỉ set `created_at` khi INSERT, không set `updated_at`

**Skill**: `php-pro`

```php
ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],  // ✅
ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],  // ✅ nhưng...
// ❌ Thiếu: EVENT_BEFORE_INSERT không set updated_at (nullable ok, nhưng...)
// ❌ Thực tế: Service.php tự set created_at bằng date() → conflict với behavior
```

---

## Tóm Tắt

| Mức Độ | Số Lượng | IDs |
|--------|----------|-----|
| 🔴 Critical (Crash/Security) | 4 | #1, #2, #3, #4 |
| 🟠 Security (File Upload) | 3 | #5, #6, #7 |
| 🟡 SOLID/Architecture | 5 | #8, #9, #10, #11, #12 |
| 🔵 Missing Features | 5 | #13, #14, #15, #16, #17 |
| ⚪ Code Quality | 6 | #18, #19, #20, #21, #22, #23 |
| **Tổng** | **23** | |

## Đề Xuất Thứ Tự Fix

1. **🔴 Critical bugs (#1-#4)** — Runtime crash & security
2. **🟠 File upload security (#5-#7)** — Prevent malicious uploads
3. **⚪ Quick cleanup (#18-#19)** — Remove commented/debug code
4. **🔵 Missing views (#14, #15)** — User-facing gaps
5. **🔵 Implement download (#13)** — Feature completion
6. **🟡 Refactor SOLID (#8-#12)** — Architecture health
7. **⚪ Code quality (#20-#23)** — Polish
8. **🔵 Unit tests (#16)** — Stability assurance
9. **🔵 DB indexes (#17)** — Performance
