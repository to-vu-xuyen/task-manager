# Lessons Learned

## 2026-02-24: TaskAttachment Code Review

### Lesson 1: DB Transaction ≠ File System Rollback
- **Mistake:** Phân tích rằng "transaction bị comment out → gây file orphan"
- **Correction:** DB transaction KHÔNG rollback được file trên disk. Chúng là 2 hệ thống hoàn toàn độc lập. Code đã xử lý đúng bằng `@unlink()` trong catch block.
- **Rule:** Khi phân tích transaction, luôn nhớ: transaction chỉ rollback DB operations, KHÔNG rollback side effects (file I/O, API calls, emails, etc.)

### Lesson 2: DIRECTORY_SEPARATOR là đúng cho file operations
- **Mistake:** Đề xuất thay `DIRECTORY_SEPARATOR` bằng `/` trong file path
- **Correction:** `DIRECTORY_SEPARATOR` là best practice cho file system operations. PHP trên Windows xử lý được cả `\` và `/`. Chỉ cần dùng `/` khi tạo web URL.
- **Rule:** Phân biệt rõ file system path vs web URL path. Dùng `DIRECTORY_SEPARATOR` cho file ops, `/` cho web URLs.

## 2026-02-25: Artifact-Only Workflow

### Lesson 3: KHÔNG BAO GIỜ sửa trực tiếp source code
- **Mistake:** Trực tiếp sửa 9 file source code (Model, Repository, Service, Controller, Views) thay vì tạo artifact.
- **Correction:** User yêu cầu rõ ràng: toàn bộ code đề xuất phải nằm trong artifact. User tự quyết định áp dụng.
- **Rule:** Luôn tạo code trong artifact file (reference_code.md). KHÔNG ĐƯỢC dùng write_to_file hoặc replace_file_content trên source code của project. Chỉ sửa artifact files trong brain/ hoặc tasks/.
- **Check:** Trước khi gọi write_to_file/replace_file_content, kiểm tra path — nếu target là source code → DỪNG LẠI → tạo artifact thay thế.

### Lesson 4: Luôn check global rules trước khi thực hiện
- **Mistake:** Không kiểm tra lại global rules trước khi bắt đầu implement.
- **Rule:** Đầu mỗi task, đọc lại global rules và workflow-orchestration. Đặc biệt rule: "kiểm tra thư mục agent skill global trong thư mục community skill".
