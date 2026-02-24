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
