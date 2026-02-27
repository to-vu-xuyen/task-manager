# Phân Tích 3 Notes — Giải Pháp

---

## Note 1: `getDownloadPath` query thừa

### Vấn đề hiện tại

```php
// Controller — actionDownloadAttachment
$attachment = $this->taskAttachmentService->getById($id);       // Query 1
$downloadPath = $this->taskAttachmentService->getDownloadPath($id); // Query 2 (gọi getById LẦN NỮA)
```

Service bên trong:
```php
public function getDownloadPath(int $attachmentId): string
{
    $attachment = $this->repository->getById($attachmentId); // ← Thừa!
    return Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $attachment->file_path;
}
```

→ **2 queries** cho cùng 1 record. User nhận xét đúng.

### Giải pháp: Đổi `getDownloadPath` nhận `file_path` thay vì ID

**Service** — đổi method signature:

```diff
-public function getDownloadPath(int $attachmentId): string
-{
-    $attachment = $this->repository->getById($attachmentId);
-    return Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $attachment->file_path;
-}
+public function getDownloadPath(string $relativePath): string
+{
+    return Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $relativePath;
+}
```

**ServiceInterface** — cập nhật:

```diff
-public function getDownloadPath(int $attachmentId): string;
+public function getDownloadPath(string $relativePath): string;
```

**Controller** — sử dụng:

```diff
 public function actionDownloadAttachment($id)
 {
     $attachment = $this->taskAttachmentService->getById($id);
-    $downloadPath = $this->taskAttachmentService->getDownloadPath($id);
+    $downloadPath = $this->taskAttachmentService->getDownloadPath($attachment->file_path);

     $task = $this->taskService->getByIdForUser($attachment->task_id, Yii::$app->user->id);
     if (!$task) {
         throw new NotFoundHttpException('Task không tồn tại.');
     }
     if (!file_exists($downloadPath)) {
         throw new NotFoundHttpException('File không tồn tại.');
     }
-    return Yii::$app->response->sendFile($downloadPath);
+    return Yii::$app->response->sendFile($downloadPath, $attachment->file_name);
 }
```

> **Kết quả**: Chỉ 1 query duy nhất. `getDownloadPath()` giờ là pure function — chỉ ghép đường dẫn, không query DB.

---

## Note 2: `getById` — Return nullable hay throw exception?

### 2 Pattern phổ biến

| Pattern | Method name | Return type | Khi không tìm thấy |
|---------|-------------|-------------|---------------------|
| **Find** | `findById()` | `?TaskAttachment` | Return `null` |
| **Get** | `getById()` | `TaskAttachment` | Throw exception |

### Khi nào dùng cái nào?

**`getById()` (throw)** — dùng khi **biết chắc record phải tồn tại**:
```php
// User click "Xoá" trên 1 attachment đang hiện → ID phải hợp lệ
$attachment = $this->repository->getById($id);
// Nếu không tìm thấy → lỗi logic, throw DomainException
```

**`findById()` (nullable)** — dùng khi **có thể không tồn tại và đó là bình thường**:
```php
// Kiểm tra attachment có tồn tại không trước khi xử lý
$attachment = $this->repository->findById($id);
if ($attachment === null) {
    // Xử lý trường hợp không tìm thấy — đây là business logic bình thường
}
```

### Khuyến nghị: Có cả 2

```php
// Interface
interface TaskAttachmentRepositoryInterface
{
    /** Tìm attachment, throw khi không có */
    public function getById(int $id): TaskAttachment;

    /** Tìm attachment, return null khi không có */
    public function findById(int $id): ?TaskAttachment;
}
```

```php
// Implementation
public function getById(int $id): TaskAttachment
{
    $attachment = $this->findById($id);
    if ($attachment === null) {
        throw new \DomainException("TaskAttachment không tồn tại: ID = {$id}");
    }
    return $attachment;
}

public function findById(int $id): ?TaskAttachment
{
    return TaskAttachment::findOne($id);
}
```

> `getById()` gọi `findById()` → không duplicate code.
> Controller dùng `getById()` (throw) vì ở đó ID phải hợp lệ.
> Logic khác có thể dùng `findById()` khi cần check existence.

---

## Note 3: AJAX cho `actionDeleteAttachment`

### Giải pháp: Kiểm tra request type, trả JSON hoặc redirect

```php
public function actionDeleteAttachment($id)
{
    $attachment = $this->taskAttachmentService->getById($id);
    $taskId = $attachment->task_id;

    // Kiểm tra quyền sở hữu
    $task = $this->taskService->getByIdForUser($taskId, Yii::$app->user->id);
    if (!$task) {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'Task không tồn tại.'];
        }
        throw new NotFoundHttpException('Task không tồn tại.');
    }

    try {
        $this->taskAttachmentService->deleteAttachment($id);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => 'Đã xoá file đính kèm.',
                'attachmentId' => $id,
                'taskId' => $taskId,
            ];
        }

        Yii::$app->session->setFlash('success', 'Đã xoá file đính kèm.');
        return $this->redirect(['view', 'id' => $taskId]);

    } catch (\Throwable $e) {
        Yii::error('Lỗi xoá attachment: ' . $e->getMessage());

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi xoá file.'];
        }

        Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi xoá file.');
        return $this->redirect(['view', 'id' => $taskId]);
    }
}
```

### JavaScript phía view (tham khảo)

```javascript
// Gọi AJAX khi click nút xoá
function deleteAttachment(attachmentId) {
    if (!confirm('Bạn có chắc muốn xoá file này?')) return;

    fetch('/task/delete-attachment?id=' + attachmentId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Xoá row khỏi DOM
            const row = document.getElementById('attachment-' + attachmentId);
            if (row) row.remove();

            // Hiển thị thông báo
            showFlash('success', data.message);
        } else {
            showFlash('error', data.message);
        }
    })
    .catch(() => {
        showFlash('error', 'Có lỗi xảy ra khi xoá file.');
    });
}
```

> **VerbFilter**: Hiện tại `delete-attachment` cho phép cả POST và GET. Nếu dùng AJAX thì nên **chỉ cho POST** thôi:
> ```diff
> -'delete-attachment' => ['POST', 'GET'],
> +'delete-attachment' => ['POST'],
> ```
