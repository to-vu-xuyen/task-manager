# Task Attachment — Code Fix Đề Xuất

> Tất cả code trong file này là **đề xuất** — user tự quyết định áp dụng.
> File tham khảo: [review_report.md](file:///C:/Users/Admin/.gemini/antigravity/brain/143913a8-1dc0-4843-93c4-5fca349908d2/review_report.md)

---

## 🔴 P0 — Fix Ngay (CRITICAL)

### Fix #1 — Redirect sai + IDOR khi xoá attachment

**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L157-L162)

```diff
 public function actionDeleteAttachment($id)
 {
-    $this->taskAttachmentService->deleteAttachment($id);
-    return $this->redirect(['view', 'id' => $id]);
+    $attachment = $this->taskAttachmentService->getById($id);
+    $taskId = $attachment->task_id;
+
+    // Kiểm tra quyền sở hữu
+    $task = $this->taskService->getByIdForUser($taskId, Yii::$app->user->id);
+    if (!$task) {
+        throw new NotFoundHttpException('Task không tồn tại.');
+    }
+
+    $this->taskAttachmentService->deleteAttachment($id);
+    Yii::$app->session->setFlash('success', 'Đã xoá file đính kèm.');
+    return $this->redirect(['view', 'id' => $taskId]);
 }
```

**Fix đồng thời**: redirect sai (#1) + IDOR (#2).

---

### Fix #2 — IDOR khi download attachment

**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L164-L171)

```diff
 public function actionDownloadAttachment($id)
 {
-    $downloadPath = $this->taskAttachmentService->getDownloadPath($id);
-    if (!file_exists($downloadPath)) {
-        throw new \yii\web\NotFoundHttpException('File không tồn tại.');
+    $attachment = $this->taskAttachmentService->getById($id);
+
+    // Kiểm tra quyền sở hữu
+    $task = $this->taskService->getByIdForUser($attachment->task_id, Yii::$app->user->id);
+    if (!$task) {
+        throw new NotFoundHttpException('Task không tồn tại.');
     }
-    return Yii::$app->response->sendFile($downloadPath);
+
+    $filePath = $this->taskAttachmentService->getDownloadPath($id);
+    if (!file_exists($filePath)) {
+        throw new NotFoundHttpException('File không tồn tại.');
+    }
+
+    return Yii::$app->response->sendFile($filePath, $attachment->file_name);
 }
```

---

### Fix #3 — VerbFilter cho attachment actions

**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L41-L51)

```diff
 $behaviors['verbs'] = [
     'class' => VerbFilter::class,
     'actions' => [
         'delete' => ['POST'],
+        'delete-attachment' => ['POST'],
+        'upload-attachment' => ['POST', 'GET'],
     ],
 ];
```

---

## 🟠 P1 — Fix Sớm (SECURITY)

### Fix #4 — Sanitize tên file

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php)

Thêm method mới + cập nhật `createAttachmentModel()`:

```php
/**
 * Loại bỏ ký tự nguy hiểm khỏi tên file, chống path traversal.
 */
private function sanitizeFileName(string $fileName): string
{
    $fileName = basename($fileName);
    $fileName = preg_replace('/[^\w\-. ]/', '_', $fileName);
    return mb_substr($fileName, 0, 200);
}
```

```diff
 // Trong createAttachmentModel()
-$taskAttachment->file_name = $file->name;
+$taskAttachment->file_name = $this->sanitizeFileName($file->name);
```

---

### Fix #5 — Lưu MIME type thực tế (từ magic bytes)

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L107-L117)

```diff
 // Trong createAttachmentModel()
-$taskAttachment->file_type = $file->type;
+$taskAttachment->file_type = FileHelper::getMimeType($file->tempName);
```

> `validateFileType()` đã dùng `FileHelper::getMimeType()` để kiểm tra — nên dùng cùng nguồn khi lưu.

---

## 🟡 P2 — Fix khi Refactor (SOLID)

### Fix #6 — Chuyển transaction từ Repository ra, Repository chỉ làm CRUD

**File**: [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php)

```php
// ĐỀ XUẤT: Repository thuần — không transaction
public function save(TaskAttachment $taskAttachment): void
{
    if (!$taskAttachment->validate()) {
        throw new \DomainException(
            'Attachment validation failed: ' . json_encode($taskAttachment->errors)
        );
    }
    if (!$taskAttachment->save(false)) {
        throw new \RuntimeException('Không thể lưu Task Attachment.');
    }
}

public function delete(TaskAttachment $taskAttachment): bool
{
    if (!$taskAttachment->delete()) {
        throw new \RuntimeException('Không thể xoá Task Attachment.');
    }
    return true;
}

public function deleteAllByTaskId(int $taskId): bool
{
    $taskAttachments = $this->getByTaskId($taskId);
    foreach ($taskAttachments as $taskAttachment) {
        if (!$taskAttachment->delete()) {
            throw new \RuntimeException('Không thể xoá Task Attachment ID: ' . $taskAttachment->id);
        }
    }
    return true;
}
```

---

### Fix #7 — Interface return type khớp thực tế (Liskov)

**File**: [TaskAttachmentRepositoryInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php)

```diff
-public function getById(int $id): ?TaskAttachment;
+public function getById(int $id): TaskAttachment;
```

**File**: [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php)

```diff
-public function getById(int $id): ?TaskAttachment
+public function getById(int $id): TaskAttachment
```

---

### Fix #8 — Thứ tự xoá: file trước, DB sau

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L75-L81)

```diff
 public function deleteAttachment(int $attachmentId): bool
 {
     $attachment = $this->repository->getById($attachmentId);
-    $this->repository->delete($attachment);
-    $this->deleteFile($attachment->file_path);
+    $this->deleteFile($attachment->file_path);   // 1. Xoá file trước
+    $this->repository->delete($attachment);       // 2. Xoá DB sau
     return true;
 }
```

> Orphan DB record (file đã xoá nhưng record còn) → dễ phát hiện.
> Orphan file (record đã xoá nhưng file còn) → chiếm disk, khó phát hiện.

---

### Fix #9 — `deleteAllByTaskId()` cần transaction ở Service

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L83-L93)

```diff
 public function deleteAllByTaskId(int $taskId): bool
 {
     $attachments = $this->repository->getByTaskId($taskId);
+    if (empty($attachments)) {
+        return true;
+    }

+    // Bước 1: Xoá files trước
     foreach ($attachments as $attachment) {
         $this->deleteFile($attachment->file_path);
-        $this->repository->delete($attachment);
     }

+    // Bước 2: Xoá DB records trong transaction (atomic)
+    $transaction = Yii::$app->db->beginTransaction();
+    try {
+        foreach ($attachments as $attachment) {
+            $this->repository->delete($attachment);
+        }
+        $transaction->commit();
+    } catch (\Throwable $e) {
+        $transaction->rollBack();
+        throw $e;
+    }
+
     return true;
 }
```

> Cần `use Yii;` ở đầu file (đã có).

---

## ⚪ P3 — Cleanup (Chất lượng code)

### Fix #10 — Xoá commented-out code

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php)

```diff
 // Dòng 45: Xoá comment
-            // throw new \RuntimeException('Cannot save file: ' . $file->name);

 // Dòng 50: Xoá comment
-            // $transaction = Yii::$app->db->beginTransaction();

 // Dòng 62: Xoá comment
-                // throw $th;

 // Dòng 148-149: Xoá comment
-        // $finfo = new \finfo(FILEINFO_MIME_TYPE);
-        // $realMimeType = $finfo->file($file->tempName);
```

**File**: [TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php)

```diff
 // Dòng 33-34, 36, 38-39, 42: Xoá comments
-        // $taskAttachment = TaskAttachment::find()->where(['task_id' => $taskId])->all();
-        // $transaction = Yii::$app->db->beginTransaction();
-            // foreach ($taskAttachment as $attachment) {
-            // }
-            // $transaction->commit();
-            // $transaction->rollBack();
```

**File**: [TaskAttachment.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/models/task/TaskAttachment.php)

```diff
 // Dòng 40: Xoá comment
-                // 'value' => date('Y-m-d H:i:s'),
```

**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php)

```diff
 // Dòng 22: Xoá comment
-    // private TaskRepositoryInterface $taskRepository;
```

---

### Fix #11 — Thay `@unlink` bằng proper error handling

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php)

```diff
 // Dòng 60 (trong upload catch block)
-@unlink($fullPath);
+if (file_exists($fullPath) && !unlink($fullPath)) {
+    Yii::warning("Không thể xoá file tạm: {$fullPath}");
+}

 // Dòng 122-124 (trong deleteFile)
 private function deleteFile(string $relativePath): void
 {
     $filePath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $relativePath;
     if (file_exists($filePath)) {
-        @unlink($filePath);
+        if (!unlink($filePath)) {
+            Yii::warning("Không thể xoá file: {$filePath}");
+        }
     }
 }
```

---

### Fix #12 — Flash messages đúng nội dung

**File**: [TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php)

```diff
 // actionUpdate — dòng 101
-Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
+Yii::$app->session->setFlash('success', 'Task đã được cập nhật thành công!');

 // actionUpdate — dòng 105
-Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
+Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi cập nhật task.');

 // actionUploadAttachment — dòng 147
-Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
+Yii::$app->session->setFlash('success', 'File đã được upload thành công!');

 // actionUploadAttachment — dòng 150
-Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
+Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi upload file.');
```

---

### Fix #13 — Upload trả về kết quả chi tiết hơn

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php#L23-L68)

Thay vì trả `array` attachments thuần, trả kèm thông tin files bị skip:

```diff
 public function upload(TaskAttachmentForm $form): array
 {
     if (!$form->validate()) {
         throw new \DomainException(
             'Attachment validation failed: ' . json_encode($form->errors)
         );
     }

     $taskDir = $this->getTaskDir($form->task_id);
     $uploadPath = $this->getUploadDirectory($form->task_id);

     $attachments = [];
+    $skipped = [];
     foreach ($form->files as $file) {
-        $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
-        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;

         if (!$this->validateFileType($file)) {
-            Yii::error('Invalid file type: ' . $file->name);
+            $skipped[] = ['file' => $file->name, 'reason' => 'Loại file không được phép'];
             continue;
         }

+        $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
+        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;
+
         if (!$file->saveAs($fullPath)) {
-            Yii::error('Cannot save file: ' . $file->name);
+            $skipped[] = ['file' => $file->name, 'reason' => 'Không thể lưu file'];
             continue;
         }

         try {
             $taskAttachment = $this->createAttachmentModel($form, $file, $taskDir, $uniqueName);
             $this->repository->save($taskAttachment);
             $attachments[] = $taskAttachment;
         } catch (\Throwable $th) {
-            @unlink($fullPath);
-            Yii::error('Error saving file: ' . $th->getMessage());
+            if (file_exists($fullPath) && !unlink($fullPath)) {
+                Yii::warning("Không thể xoá file tạm: {$fullPath}");
+            }
+            $skipped[] = ['file' => $file->name, 'reason' => 'Lỗi DB: ' . $th->getMessage()];
         }
     }

-    return $attachments;
+    return ['uploaded' => $attachments, 'skipped' => $skipped];
 }
```

> [!WARNING]
> Fix #13 thay đổi return type → cần cập nhật cả Controller nơi gọi `upload()`:
> ```php
> $result = $this->taskAttachmentService->upload($model);
> $uploaded = $result['uploaded'];
> $skipped = $result['skipped'];
> ```
> Cân nhắc kỹ trước khi áp dụng vì ảnh hưởng phạm vi rộng.

---

### Fix #14 — Sử dụng DTO (tuỳ chọn, khuyến nghị)

**File**: [TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php)

```diff
+use common\dto\task\TaskAttachmentDto;

 public function getByTaskId(int $taskId): array
 {
-    return $this->repository->getByTaskId($taskId);
+    return array_map(
+        fn(TaskAttachment $a) => TaskAttachmentDto::fromModel($a),
+        $this->repository->getByTaskId($taskId)
+    );
 }
```

> [!NOTE]
> Fix này là **tuỳ chọn** — chỉ áp dụng nếu muốn tách biệt rõ giữa domain model và presentation layer.
> Nếu áp dụng, cần cập nhật code ở Controller/View nơi sử dụng kết quả.
