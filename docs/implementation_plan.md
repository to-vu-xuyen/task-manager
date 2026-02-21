# Task Attachment — Phân Tích & Kế Hoạch Triển Khai

Phân tích toàn bộ code Task Attachment hiện có, xác định các bug/code smell, và đề xuất kế hoạch triển khai hoàn chỉnh.

## Tổng quan hiện trạng

### Các file đã có

| Layer | File | Trạng thái |
|-------|------|-----------|
| **Migration** | [m260217_075520_create_task_attachment_table.php](file:///c:/wamp64/www/task-manager/console/migrations/m260217_075520_create_task_attachment_table.php) | ⚠️ `created_at` dùng `date()` thay vì `datetime()` |
| **Model** | [TaskAttachment.php](file:///c:/wamp64/www/task-manager/common/models/task/TaskAttachment.php) | ⚠️ `TimestampBehavior` dùng `date()` thay vì `new Expression('NOW()')` |
| **Repository Interface** | [TaskAttachmentRepositoryInterface.php](file:///c:/wamp64/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php) | ❌ `delete(int $taskId)` — param sai semantic, `getById` param name sai |
| **Repository** | [TaskAttachmentRepository.php](file:///c:/wamp64/www/task-manager/common/repositories/task/TaskAttachmentRepository.php) | ❌ `delete()` xóa theo `task_id` nhưng service gọi bằng `attachment->id` |
| **Service Interface** | [TaskAttachmentServiceInterface.php](file:///c:/wamp64/www/task-manager/common/services/task/TaskAttachmentServiceInterface.php) | ✅ OK |
| **Service** | [TaskAttachmentService.php](file:///c:/wamp64/www/task-manager/common/services/task/TaskAttachmentService.php) | ⚠️ Commented code, `deleteAttachment()` gọi `delete($attachment->id)` nhưng repo xóa theo `task_id` |
| **Form** | [TaskAttachmentForm.php](file:///c:/wamp64/www/task-manager/common/forms/task/TaskAttachmentForm.php) | ✅ OK |
| **View** | [_attachments.php](file:///c:/wamp64/www/task-manager/frontend/views/task/_attachments.php) | ❌ File rỗng |
| **DI Config** | [main.php](file:///c:/wamp64/www/task-manager/common/config/main.php#L91-L97) | ✅ Đã đăng ký |
| **DTO** | *Chưa có* | ❌ Cần tạo `TaskAttachmentDto` |
| **Controller** | [TaskController.php](file:///c:/wamp64/www/task-manager/frontend/controllers/TaskController.php) | ❌ Chưa tích hợp attachment |

---

## Các Bug & Code Smell Phát Hiện

### 🔴 Bug Nghiêm Trọng

**1. Repository `delete()` parameter mismatch**

```diff
// Interface định nghĩa:
- public function delete(int $taskId): bool;  // ← nói là taskId

// Repository implement: xóa TẤT CẢ attachment theo task_id
- $taskAttachment = TaskAttachment::find()->where(['task_id' => $taskId])->all();

// Nhưng Service gọi:
- $this->repository->delete($attachment->id);  // ← truyền attachment ID → XÓA NHẦM!
```

> [!CAUTION]
> Đây là bug nghiêm trọng: `deleteAttachment()` truyền `$attachment->id` (attachment ID) vào `delete()` — nhưng repo lại dùng nó làm `task_id` để query → **xóa nhầm hoàn toàn tất cả attachment của một task khác!**

**2. `getById()` parameter named `$taskId` nhưng thực chất tìm bằng primary key**

```php
// Interface + Repo:
public function getById(int $taskId): TaskAttachment  // ← param name misleading
// Thực tế: TaskAttachment::findOne($taskId) → tìm theo PK attachment
```

**3. `TaskController` gọi `getTask()` nhưng `TaskServiceInterface` chỉ có `getById()`**

```php
// Controller line 82, 115:
$task = $this->taskService->getTask($id);  // ← method không tồn tại
```

### 🟡 Code Smell

1. **`TimestampBehavior`** dùng `date('Y-m-d H:i:s')` — giá trị được đánh giá **1 lần khi file load**, không phải lúc insert. Nên dùng `new Expression('NOW()')`.
2. **Migration**: `created_at` dùng `$this->date()` (chỉ `DATE`) nhưng `TimestampBehavior` ghi `datetime` format → mất time component.
3. **Service `upload()`**: Commented-out transaction/throw. Logic rollback file (`@unlink`) đúng nhưng swallow exception.
4. **Repository `save()`**: Gọi `$taskAttachment->validate()` rồi `$taskAttachment->save()` (save mặc định validate lại) → validate 2 lần.
5. **Repository `delete()`**: `return false` sau try-catch không bao giờ chạy vì catch luôn throw.
6. **Task model** thiếu `getAttachments()` relation.

---

## Proposed Changes

### Layer 1: Migration

#### [MODIFY] [m260217_075520_create_task_attachment_table.php](file:///c:/wamp64/www/task-manager/console/migrations/m260217_075520_create_task_attachment_table.php)

> [!IMPORTANT]
> Nếu migration đã chạy trên database, cần tạo migration mới thay vì sửa file cũ. Nếu chưa chạy thì sửa trực tiếp.

- Đổi `$this->date()` → `$this->dateTime()` cho `created_at` và `updated_at`

---

### Layer 2: Model

#### [MODIFY] [TaskAttachment.php](file:///c:/wamp64/www/task-manager/common/models/task/TaskAttachment.php)

- Fix `TimestampBehavior`: `'value' => date(...)` → `'value' => new Expression('NOW()')`
- Thêm `@property` PHPDoc cho type hinting

#### [MODIFY] [Task.php](file:///c:/wamp64/www/task-manager/common/models/task/Task.php)

- Thêm `getAttachments()` relation: `$this->hasMany(TaskAttachment::class, ['task_id' => 'id'])`

---

### Layer 3: Repository

#### [MODIFY] [TaskAttachmentRepositoryInterface.php](file:///c:/wamp64/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php)

- Tách `delete()` thành 2 method rõ ràng:
  - `deleteById(int $id): void` — xóa 1 attachment theo PK
  - `deleteAllByTaskId(int $taskId): void` — xóa tất cả theo task
- Rename `getById(int $taskId)` → `getById(int $id)` cho đúng semantic

#### [MODIFY] [TaskAttachmentRepository.php](file:///c:/wamp64/www/task-manager/common/repositories/task/TaskAttachmentRepository.php)

- Implement `deleteById()` và `deleteAllByTaskId()` riêng biệt
- Fix `save()`: bỏ `validate()` thủ công, dùng `save(false)` sau khi validate
- Fix `getById()`: rename param, thêm throw nếu không tìm thấy

---

### Layer 4: Service

#### [MODIFY] [TaskAttachmentService.php](file:///c:/wamp64/www/task-manager/common/services/task/TaskAttachmentService.php)

- Fix `deleteAttachment()`: gọi `$this->repository->deleteById($attachmentId)`
- Fix `deleteAllByTaskId()`: gọi `$this->repository->deleteAllByTaskId($taskId)`
- Clean up commented code
- Đảm bảo proper error handling

---

### Layer 5: DTO (NEW)

#### [NEW] [TaskAttachmentDto.php](file:///c:/wamp64/www/task-manager/common/dto/task/TaskAttachmentDto.php)

- Tạo DTO giống pattern `TaskDto`: `fromModel()`, `fromModels()`, `toArray()`
- Fields: `id`, `taskId`, `fileName`, `filePath`, `fileType`, `fileSize`, `createdAt`, `downloadUrl`

---

### Layer 6: Controller

#### [MODIFY] [TaskController.php](file:///c:/wamp64/www/task-manager/frontend/controllers/TaskController.php)

- Inject `TaskAttachmentServiceInterface` vào constructor
- Thêm `actionUploadAttachment($taskId)` — xử lý upload file
- Thêm `actionDeleteAttachment($id)` — xóa 1 attachment
- Thêm `actionDownloadAttachment($id)` — download file
- Fix `getTask()` → `getById()`
- Cập nhật `actionView()` để load attachments

---

### Layer 7: Views

#### [MODIFY] [_form.php](file:///c:/wamp64/www/task-manager/frontend/views/task/_form.php)

- Thêm `enctype='multipart/form-data'` vào form options
- Thêm `FileInput` widget cho upload file

#### [MODIFY] [_attachments.php](file:///c:/wamp64/www/task-manager/frontend/views/task/_attachments.php)

- Triển khai hiển thị danh sách file đính kèm: tên file, kích thước, icon theo loại, nút download/xóa

#### [MODIFY] [view.php](file:///c:/wamp64/www/task-manager/frontend/views/task/view.php)

- Render `_attachments.php` partial trong trang view

---

## Verification Plan

### Automated Tests

Hiện tại **chưa có test nào** cho Task Attachment module (thư mục `tests/` trống cho attachment).

Đề xuất tạo test sau khi implement:

```bash
# Chạy toàn bộ test suite
vendor/bin/codecept run unit
```

- Unit test cho `TaskAttachmentRepository` (save, deleteById, deleteAllByTaskId, getById, getByTaskId)
- Unit test cho `TaskAttachmentService` (upload, deleteAttachment, deleteAllByTaskId)
- Unit test cho `TaskAttachmentDto` (fromModel, fromModels, toArray)

### Manual Verification

> [!IMPORTANT]
> User cần xác nhận migration đã chạy hay chưa trước khi bắt đầu implement.

1. **Upload**: Vào trang Create/Update task → chọn file → submit → kiểm tra file lưu trong `frontend/web/uploads/task/{task_id}/`
2. **View**: Vào trang View task → xác nhận hiển thị danh sách file đính kèm
3. **Download**: Click vào link download → file tải về đúng
4. **Delete**: Click nút xóa attachment → file bị xóa khỏi DB và filesystem

---

## User Review Required

> [!WARNING]
> Cần user xác nhận: migration `m260217_075520` đã chạy trên database hay chưa? Điều này quyết định cách fix column type (`date` → `datetime`).

1. **Migration đã chạy?** → Cần tạo migration mới `alter_task_attachment_column_types` để sửa column type
2. **Migration chưa chạy?** → Sửa trực tiếp file migration hiện có
