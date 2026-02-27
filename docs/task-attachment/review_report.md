# Task Attachment — Báo Cáo Kiểm Tra Toàn Diện

> **Ngày**: 2026-02-27  
> **Skills áp dụng**: `php-pro`, `clean-code`, `code-review-excellence`, `file-uploads`, `architect-review`, `error-handling-patterns`  
> **Phương pháp**: Đọc lại toàn bộ source code từ đầu, phân tích theo SOLID, bảo mật, clean code

---

## Tổng Quan

Đã kiểm tra **8 files** trong phần Task Attachment:

| File | Vai trò |
|------|---------|
| [TaskAttachment.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/models/task/TaskAttachment.php) | Model (ActiveRecord) |
| [TaskAttachmentForm.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/forms/task/TaskAttachmentForm.php) | Form validation |
| [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php) | Business logic |
| [TaskAttachmentServiceInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentServiceInterface.php) | Service interface |
| [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php) | Truy vấn DB |
| [TaskAttachmentRepositoryInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php) | Repository interface |
| [TaskAttachmentDto.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/dto/task/TaskAttachmentDto.php) | Data Transfer Object |
| [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php) | Controller (attachment actions) |

---

## Danh Sách Vấn Đề

### 🔴 CRITICAL — Bugs thực sự, cần fix ngay

#### 1. Redirect sai sau khi xoá attachment
**File**: [TaskController.php#L157-L162](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L157-L162)

```php
public function actionDeleteAttachment($id)
{
    $this->taskAttachmentService->deleteAttachment($id);
    return $this->redirect(['view', 'id' => $id]); // ❌ $id = attachment ID, KHÔNG phải task ID
}
```
**Vấn đề**: `$id` ở đây là ID của attachment, nhưng redirect về `view` (cần task ID) → sẽ gây 404 hoặc hiển thị sai task.

---

#### 2. IDOR — Không kiểm tra quyền sở hữu khi xoá/download
**File**: [TaskController.php#L157-L171](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L157-L171)

Cả `actionDeleteAttachment` và `actionDownloadAttachment` đều **không kiểm tra** user hiện tại có quyền truy cập task đó hay không. Bất kỳ user nào biết attachment ID → xoá/download file của người khác.

> [!CAUTION]
> Đây là lỗ hổng bảo mật IDOR (Insecure Direct Object Reference). Trong khi `actionUploadAttachment` có check `getByIdForUser()`, hai action kia thì không.

---

#### 3. VerbFilter thiếu cho `delete-attachment`
**File**: [TaskController.php#L41-L51](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L41-L51)

`delete-attachment` không bị giới hạn HTTP method → có thể xoá bằng GET request. Vi phạm CSRF protection best practice.

---

### 🟠 SECURITY — Lỗ hổng bảo mật

#### 4. Tên file không được sanitize
**File**: [TaskAttachmentService.php#L112](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L112)

```php
$taskAttachment->file_name = $file->name; // ❌ Input thô từ client
```
**Rủi ro**: Path traversal khi hiển thị tên file, XSS. Skill `file-uploads` cảnh báo: _"User-controlled filename allows path traversal — CRITICAL — SANITIZE FILENAMES"_.

---

#### 5. MIME type lưu từ client, không phải từ magic bytes
**File**: [TaskAttachmentService.php#L114](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L114)

```php
$taskAttachment->file_type = $file->type; // ❌ Client gửi gì lưu nấy
```
Mặc dù `validateFileType()` đã dùng `FileHelper::getMimeType()` để **kiểm tra** — nhưng lại **lưu vào DB** giá trị `$file->type` do client cung cấp → DB chứa dữ liệu không đáng tin cậy.

---

### 🟡 SOLID & Kiến Trúc

#### 6. Repository tự quản lý transaction — vi phạm SRP
**File**: [TaskAttachmentRepository.php#L11-L28](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php#L11-L28)

Method `save()` tự wrap transaction cho **từng record đơn lẻ**. Theo nguyên tắc SOLID:
- **Repository** chỉ nên làm CRUD thuần (persistence concern)
- **Transaction** thuộc về Service layer (business logic concern)

Nếu cần save 5 attachments atomically, mỗi `save()` có transaction riêng → không atomic.

---

#### 7. Interface return type không khớp thực tế — vi phạm Liskov (LSP)
**File**: [TaskAttachmentRepositoryInterface.php#L17](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php#L17)

```php
public function getById(int $id): ?TaskAttachment; // Interface nói: có thể null
```
Nhưng implementation **luôn throw exception** khi không tìm thấy, không bao giờ return `null`:
```php
if (empty($attachment)) {
    throw new \DomainException("TaskAttachment not found: ID = {$id}");
}
```
→ Caller dựa vào interface sẽ check null — nhưng thực tế nhận exception.

---

#### 8. DTO tồn tại nhưng không được sử dụng
**File**: [TaskAttachmentDto.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/dto/task/TaskAttachmentDto.php)

`TaskAttachmentDto` có `fromModel()` và `toArray()` nhưng **không được gọi ở bất kỳ đâu**. Service trả thẳng `TaskAttachment` model ra Controller → leak domain model ra ngoài boundary.

---

#### 9. `deleteAttachment()` — Thứ tự không tối ưu
**File**: [TaskAttachmentService.php#L75-L81](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L75-L81)

```php
$this->repository->delete($attachment);  // 1. Xoá DB trước
$this->deleteFile($attachment->file_path); // 2. Xoá file sau
```
Nếu bước 2 fail → DB record đã mất nhưng file vẫn còn trên disk (orphan file). **Xoá file trước, DB sau** an toàn hơn vì orphan DB record dễ phát hiện hơn orphan file.

> [!NOTE]
> Theo Lesson 1: DB transaction KHÔNG rollback file. Thứ tự operations quan trọng khi 2 hệ thống độc lập.

---

#### 10. `deleteAllByTaskId()` — Không có transaction ở Service
**File**: [TaskAttachmentService.php#L83-L93](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L83-L93)

Vòng lặp xoá từng attachment mà không wrap transaction → nếu lỗi giữa chừng, một số đã xoá, số còn lại chưa → **partial delete state**.

---

### ⚪ Chất Lượng Code

#### 11. Commented-out code rải rác
Theo skill `clean-code`: _"Don't Comment Bad Code — Rewrite It"_

| File | Dòng |
|------|------|
| [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php) | 45, 50, 62, 148-149 |
| [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php) | 33-34, 36, 38-39, 42 |
| [TaskAttachment.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/models/task/TaskAttachment.php) | 40 |
| [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php) | 22 |

---

#### 12. `@unlink` — Che giấu lỗi
**Files**: [TaskAttachmentService.php#L60](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L60), [#L123](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L123)

```php
@unlink($fullPath);  // Error suppression
@unlink($filePath);  // Error suppression
```
Theo `error-handling-patterns`: error suppression với `@` che giấu failures. Nên log lỗi thay vì im lặng.

---

#### 13. Flash messages sai nội dung
**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php)

| Action | Hiện tại | Nên là |
|--------|----------|--------|
| `actionUpdate` dòng 101 | "Task đã được **tạo** thành công!" | "Task đã được **cập nhật** thành công!" |
| `actionUpdate` dòng 105 | "Có lỗi xảy ra khi **tạo** task." | "Có lỗi xảy ra khi **cập nhật** task." |
| `actionUploadAttachment` dòng 147 | "Task đã được **tạo** thành công!" | "File đã được **upload** thành công!" |
| `actionUploadAttachment` dòng 150 | "Có lỗi xảy ra khi **tạo** task." | "Có lỗi xảy ra khi **upload** file." |

---

#### 14. Upload silently skip files lỗi — User không biết
**File**: [TaskAttachmentService.php#L39-L48](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L39-L48)

Khi file bị reject (MIME sai hoặc save fail) → chỉ log error, user không nhận thông báo gì → tưởng upload thành công.

---

## Bảng Ưu Tiên

| Mức | Issues | Ảnh hưởng |
|-----|--------|-----------|
| 🔴 P0 | #1, #2, #3 | Bug thực sự + bảo mật |
| 🟠 P1 | #4, #5 | Bảo mật — path traversal, data integrity |
| 🟡 P2 | #6, #7, #9, #10 | SOLID, luồng xoá không an toàn |
| ⚪ P3 | #8, #11, #12, #13, #14 | Chất lượng code, UX |

---

> [!IMPORTANT]
> Báo cáo này chỉ **ĐỌC và PHÂN TÍCH** — không sửa source code trực tiếp (theo Lesson 3 & 5).
> Code fix đề xuất nằm trong file [code_fix_proposals.md](file:///C:/Users/Admin/.gemini/antigravity/brain/143913a8-1dc0-4843-93c4-5fca349908d2/code_fix_proposals.md).
