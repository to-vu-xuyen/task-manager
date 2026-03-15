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

## 2026-02-27: Tự Ý Sửa Code + Xóa Lessons

### Lesson 5: "Verify" ≠ "Fix" — phân biệt rõ yêu cầu
- **Mistake:** User yêu cầu "kiểm tra lại xem đã fix chưa" → agent tự ý sửa 6 files source code.
- **Correction:** "Kiểm tra" / "verify" / "review" = chỉ ĐỌC và BÁO CÁO. Liệt kê cái nào fix rồi ✅, cái nào chưa ❌. Hỏi user trước khi sửa.
- **Rule:** Khi user dùng từ "kiểm tra", "review", "verify", "check" → KHÔNG sửa code. Chỉ báo cáo kết quả và hỏi permission nếu muốn fix.

### Lesson 6: KHÔNG BAO GIỜ overwrite file có sẵn mà không đọc trước
- **Mistake:** Dùng `Overwrite: true` khi ghi `lessons.md` → xóa mất 4 lessons cũ.
- **Correction:** Luôn đọc file trước bằng `view_file`. Nếu file có nội dung → dùng `replace_file_content` để append, KHÔNG dùng `write_to_file` với `Overwrite: true`.
- **Rule:** Trước khi ghi file bất kỳ → `view_file` trước. Nếu có nội dung cũ → append, không overwrite. Đặc biệt `lessons.md` — KHÔNG BAO GIỜ overwrite.

### Lesson 7: Đọc lại lessons.md ĐẦU MỖI PHIÊN (lặp lại Lesson 4)
- **Mistake:** Phiên này không đọc `lessons.md` trước khi bắt đầu → vi phạm lại Lesson 3 (lần thứ 2).
- **Rule:** Bước đầu tiên mỗi phiên: đọc `tasks/lessons.md`. Nếu file tồn tại → đọc và tuân thủ. Đây là bắt buộc, không bỏ qua.

## 2026-03-02: API Docs Audit — Inconsistency

### Lesson 8: Cross-reference TẤT CẢ chỗ liên quan trước khi sửa — TOÀN BỘ PROJECT
- **Mistake:** Trong `00-implementation-plan.md`, Component 1 ghi `api/Module.php` nhưng phần File Structure Summary và `01-setup-config.md` ghi đúng `api/modules/v1/Module.php`. Sửa cấu trúc versioning nhưng sót 1 chỗ cũ.
- **Correction:** Khi thay đổi bất kỳ khái niệm nào (file path, class name, config key...), phải tìm TẤT CẢ chỗ nó xuất hiện trước khi sửa.
- **Scope: TOÀN BỘ PROJECT** — không chỉ docs, mà cả source code, configs, migrations, views, tests, .env, composer.json... Bất kỳ file nào trong project đều có thể reference đến thứ đang sửa.
- **Rule — Checklist trước khi sửa:**
  1. **Grep/Search toàn project** (`grep_search` trên root project) cụm từ cần sửa
  2. **Liệt kê** tất cả file + line chứa reference đó (cả source code lẫn docs)
  3. **Sửa đồng bộ** tất cả → không sót
  4. **Verify** bằng grep lại lần nữa sau khi sửa
- **Áp dụng cho:** File paths, class names, namespace, config keys, URL patterns, env variables, table names, route rules — bất kỳ thứ gì xuất hiện ở nhiều nơi trong project.

## 2026-03-15: Direct Code Modification Policy

### Lesson 9: Ghi code vào Artifact thay vì sửa trực tiếp
- **Mistake:** Tự động ghi đè file cấu hình Docker (`backend/Dockerfile`, v.v.) khi user chưa rõ ràng cho phép.
- **Correction:** User nhắc nhở "không được tự ý sửa, ghi tất cả code vào artifact". Phải luôn viết code ra một file Artifact độc lập (như `docker_best_practices.md`) trừ khi user ra lệnh trực tiếp "hãy sửa file này".
- **Rule:** Nếu có nhiệm vụ Refactor/Setup lớn, HÃY ƯU TIÊN tạo một Artifact Report chứa code đề xuất, thay vì tự ý edit source code của user, trừ phi đó là task debug/sửa lỗi đã thống nhất.
