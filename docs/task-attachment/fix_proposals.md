# Task Attachment — Code Fix Proposals

> Issues #1, #3 đã được User fix. Dưới đây là code đề xuất cho **21 issues còn lại**.

---

## 🔴 CRITICAL

### Fix #2 — Redirect sai sau xoá attachment

[TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L157-L161)

```diff
 public function actionDeleteAttachment($id)
 {
-    $this->taskAttachmentService->deleteAttachment($id);
-    return $this->redirect(['view', 'id' => $id]);
+    $attachment = $this->taskAttachmentService->getById($id);
+    $taskId = $attachment->task_id;
+
+    // Kiểm tra ownership
+    $task = $this->taskService->getByIdForUser($taskId, Yii::$app->user->id);
+    if (!$task) {
+        throw new NotFoundHttpException('Task not found');
+    }
+
+    $this->taskAttachmentService->deleteAttachment($id);
+    Yii::$app->session->setFlash('success', 'Đã xoá file đính kèm.');
+    return $this->redirect(['view', 'id' => $taskId]);
 }
```

> Fix đồng thời cả **#4 (IDOR)** — check ownership trước khi xoá.

---

### Fix #4 — IDOR cho Download

[TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L163-L166)

> Xem Fix #13 bên dưới (implement download + ownership check).

---

## 🟠 SECURITY

### Fix #5, #7 — Validate magic bytes + MIME type thực tế

[TaskAttachmentService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentService.php)

Thêm method validate file type:

```php
/**
 * Validate file MIME type bằng magic bytes, không tin client.
 */
private function validateFileType(UploadedFile $file): bool
{
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $realMimeType = $finfo->file($file->tempName);

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    return in_array($realMimeType, $allowedMimeTypes, true);
}
```

Cập nhật `upload()` — dùng `$realMimeType` thay vì `$file->type`:

```diff
 foreach ($form->files as $file) {
+    if (!$this->validateFileType($file)) {
+        Yii::warning("File rejected (invalid MIME): {$file->name}");
+        continue;
+    }
+
     $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
```

Cập nhật `createAttachmentModel()`:

```diff
-$taskAttachment->file_type = $file->type;
+$finfo = new \finfo(FILEINFO_MIME_TYPE);
+$taskAttachment->file_type = $finfo->file($file->tempName);
```

---

### Fix #6 — Sanitize filename

```diff
-$taskAttachment->file_name = $file->name;
+$taskAttachment->file_name = $this->sanitizeFileName($file->name);
```

Thêm method:

```php
private function sanitizeFileName(string $fileName): string
{
    // Loại bỏ path separators và ký tự nguy hiểm
    $fileName = basename($fileName);
    $fileName = preg_replace('/[^\w\-. ]/', '_', $fileName);
    return mb_substr($fileName, 0, 200);
}
```

---

## 🟡 SOLID & ARCHITECTURE

### Fix #8 — Di chuyển transaction từ Repository lên Service

**Repository** — bỏ transaction, chỉ CRUD:

[TaskAttachmentRepository.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/TaskAttachmentRepository.php)

```php
public function save(TaskAttachment $taskAttachment): void
{
    if (!$taskAttachment->validate()) {
        throw new \DomainException(
            'Attachment validation failed: ' . json_encode($taskAttachment->errors)
        );
    }
    if (!$taskAttachment->save(false)) {
        throw new \RuntimeException('Cannot save Task Attachment');
    }
}

public function delete(TaskAttachment $taskAttachment): bool
{
    if (!$taskAttachment->delete()) {
        throw new \RuntimeException('Cannot delete Task Attachment');
    }
    return true;
}

public function deleteAllByTaskId(int $taskId): bool
{
    $taskAttachments = $this->getByTaskId($taskId);
    foreach ($taskAttachments as $taskAttachment) {
        if (!$taskAttachment->delete()) {
            throw new \RuntimeException('Cannot delete Task Attachment ID: ' . $taskAttachment->id);
        }
    }
    return true;
}
```

**Service** — wrap transaction khi cần:

```php
public function deleteAllByTaskId(int $taskId): bool
{
    $attachments = $this->repository->getByTaskId($taskId);
    $transaction = Yii::$app->db->beginTransaction();
    try {
        foreach ($attachments as $attachment) {
            $this->deleteFile($attachment->file_path);
            $this->repository->delete($attachment);
        }
        $transaction->commit();
        return true;
    } catch (\Throwable $e) {
        $transaction->rollBack();
        throw $e;
    }
}
```

---

### Fix #9 — Tách FileStorageService (SRP)

> [!NOTE]
> Đây là refactor lớn, có thể làm sau. Tạo interface + class mới:

```php
// common/services/storage/FileStorageServiceInterface.php
interface FileStorageServiceInterface
{
    public function save(UploadedFile $file, string $directory): string; // returns relative path
    public function delete(string $relativePath): void;
    public function getAbsolutePath(string $relativePath): string;
}
```

```php
// common/services/storage/LocalFileStorageService.php
class LocalFileStorageService implements FileStorageServiceInterface
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = Yii::getAlias('@frontend/web');
    }

    public function save(UploadedFile $file, string $directory): string
    {
        $uploadPath = $this->basePath . DIRECTORY_SEPARATOR . $directory;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;

        if (!$file->saveAs($fullPath)) {
            throw new \RuntimeException('Cannot save file: ' . $file->name);
        }

        return $directory . DIRECTORY_SEPARATOR . $uniqueName;
    }

    public function delete(string $relativePath): void
    {
        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                Yii::warning("Failed to delete file: {$filePath}");
            }
        }
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
    }
}
```

---

### Fix #10 — Sử dụng TaskAttachmentDto

**Service** trả về DTO thay vì Model:

```diff
-public function upload(TaskAttachmentForm $form): array
+public function upload(TaskAttachmentForm $form): array
 {
     // ... (existing logic)
-    return $attachments;
+    return array_map(
+        fn(TaskAttachment $a) => TaskAttachmentDto::fromModel($a),
+        $attachments
+    );
 }

-public function getByTaskId(int $taskId): array
+public function getByTaskId(int $taskId): array
 {
-    return $this->repository->getByTaskId($taskId);
+    return array_map(
+        fn(TaskAttachment $a) => TaskAttachmentDto::fromModel($a),
+        $this->repository->getByTaskId($taskId)
+    );
 }
```

---

### Fix #11 — Thêm `getById()` vào ServiceInterface

[TaskAttachmentServiceInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskAttachmentServiceInterface.php)

```diff
 interface TaskAttachmentServiceInterface
 {
     public function upload(TaskAttachmentForm $form): array;
     public function getByTaskId(int $taskId): array;
     public function deleteAttachment(int $attachmentId): bool;
     public function deleteAllByTaskId(int $taskId): bool;
+    public function getById(int $attachmentId): TaskAttachment;
 }
```

---

### Fix #12 — Return type nhất quán

[TaskAttachmentRepositoryInterface.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/repositories/task/interfaces/TaskAttachmentRepositoryInterface.php)

```diff
-public function getById(int $id): ?TaskAttachment;
+public function getById(int $id): TaskAttachment;
```

---

## 🔵 MISSING FEATURES

### Fix #13 — Implement Download + Ownership check (Fix #4)

[TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php#L163-L166)

```php
public function actionDownloadAttachment($id)
{
    $attachment = $this->taskAttachmentService->getById($id);

    // Ownership check
    $task = $this->taskService->getByIdForUser($attachment->task_id, Yii::$app->user->id);
    if (!$task) {
        throw new NotFoundHttpException('Task not found');
    }

    $filePath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $attachment->file_path;

    if (!file_exists($filePath)) {
        throw new NotFoundHttpException('File not found');
    }

    return Yii::$app->response->sendFile($filePath, $attachment->file_name);
}
```

---

### Fix #14 — Tạo view `upload-attachment.php`

Tạo file `frontend/views/task/upload-attachment.php`:

```php
<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\forms\task\TaskAttachmentForm $model */

$this->title = 'Upload File Đính Kèm';
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-upload-attachment">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

            <?= $form->field($model, 'files[]')->fileInput([
                'multiple' => true,
                'accept' => '.jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx',
            ])->label('Chọn file (tối đa 5 file, 10MB/file)') ?>

            <div class="form-group mt-3">
                <?= Html::submitButton('<i class="fas fa-upload"></i> Upload', [
                    'class' => 'btn btn-success',
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
```

---

### Fix #15 — Hiển thị attachments trong task view

[view.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/views/task/view.php) — Thêm sau card thông tin task:

```php
<?php
// Lấy attachments — cần pass từ controller
$attachments = $model->taskAttachments ?? [];
?>

<?php if (!empty($attachments)): ?>
<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-paperclip"></i> File Đính Kèm (<?= count($attachments) ?>)
        </h5>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tên file</th>
                    <th>Loại</th>
                    <th>Kích thước</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attachments as $att): ?>
                <tr>
                    <td><?= Html::encode($att->file_name) ?></td>
                    <td><?= Html::encode($att->file_type) ?></td>
                    <td><?= Yii::$app->formatter->asShortSize($att->file_size) ?></td>
                    <td><?= Yii::$app->formatter->asDatetime($att->created_at) ?></td>
                    <td>
                        <?= Html::a('<i class="fas fa-download"></i>', ['download-attachment', 'id' => $att->id], [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Download',
                        ]) ?>
                        <?= Html::a('<i class="fas fa-trash"></i>', ['delete-attachment', 'id' => $att->id], [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data' => [
                                'confirm' => 'Bạn có chắc muốn xoá file này?',
                                'method' => 'post',
                            ],
                            'title' => 'Xoá',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
```

Cập nhật `actionView()` trong Controller:

```diff
 public function actionView($id)
 {
     $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
     if ($task === null) {
         throw new NotFoundHttpException('Task không tồn tại.');
     }

+    $attachments = $this->taskAttachmentService->getByTaskId($id);

     return $this->render('view', [
         'model' => $task,
+        'attachments' => $attachments,
     ]);
 }
```

> Trong view nhận `$attachments` thay vì `$model->taskAttachments`.

---

### Fix #15b — Thêm relation trong Task model

Nếu muốn dùng `$model->taskAttachments`:

```php
// common/models/task/Task.php — thêm relation
public function getTaskAttachments(): ActiveQuery
{
    return $this->hasMany(TaskAttachment::class, ['task_id' => 'id'])
        ->orderBy(['id' => SORT_DESC]);
}
```

---

## ⚪ CODE QUALITY

### Fix #18 — Xoá commented code

Xoá tất cả commented-out code trong:
- `TaskAttachmentService.php` (lines 39, 44, 56)
- `TaskAttachmentRepository.php` (lines 33-34, 36, 38-39, 42)
- `TaskController.php` (line 22)

---

### Fix #19 — Xoá `codecept_debug()` trong production

[TaskService.php#L94](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskService.php#L94)

```diff
-codecept_debug($task->deleted_at);
```

---

### Fix #20 — Thay `@unlink` bằng proper error handling

```diff
-@unlink($fullPath);
+if (file_exists($fullPath) && !unlink($fullPath)) {
+    Yii::warning("Failed to cleanup file: {$fullPath}");
+}
```

---

### Fix #21 — Dùng DB time thay PHP time

[TaskAttachment.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/models/task/TaskAttachment.php#L31-L43)

```diff
 'timestamp' => [
     'class' => TimestampBehavior::class,
     'attributes' => [
         ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
         ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
     ],
-    'value' => date('Y-m-d H:i:s'),
+    'value' => new Expression('NOW()'),
 ],
```

---

### Fix #22 — Flash message đúng nội dung

[TaskController.php](file:///e:/ProgramFiles/wamp/www/task-manager/frontend/controllers/TaskController.php)

```diff
 // actionUpdate — lines 101, 105
-Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
+Yii::$app->session->setFlash('success', 'Task đã được cập nhật thành công!');

-Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
+Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi cập nhật task.');

 // actionUploadAttachment — lines 147, 150
-Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
+Yii::$app->session->setFlash('success', 'File đã được upload thành công!');

-Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
+Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi upload file.');
```

---

### Fix #23 — Bỏ conflict giữa Behavior và Service set timestamp

[TaskService.php](file:///e:/ProgramFiles/wamp/www/task-manager/common/services/task/TaskService.php)

```diff
 // create() — để TimestampBehavior lo
-$task->created_at = date('Y-m-d H:i:s');

 // update() — để TimestampBehavior lo
-$task->updated_at = date('Y-m-d H:i:s');
```

---

## VerbFilter bổ sung

Thêm verb filter cho các action attachment:

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
