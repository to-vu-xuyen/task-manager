# Task Attachment — Reference Code

Code tham khảo cho từng layer, tuân thủ SOLID principles.

---

## 1. Model — TaskAttachment (Fixed)

```php
<?php

namespace common\models\task;

use Yii;
use common\models\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Task $task
 * @property User $user
 */
class TaskAttachment extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%task_attachment}}';
    }

    public function rules(): array
    {
        return [
            [['task_id', 'user_id', 'file_name', 'file_path', 'file_type', 'file_size'], 'required'],
            [['task_id', 'user_id', 'file_size'], 'integer'],
            [['file_name', 'file_path', 'file_type'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            ['task_id', 'exist', 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // FIX: dùng Expression thay vì date() — date() chỉ evaluate 1 lần khi class load
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'user_id' => 'User ID',
            'file_name' => 'File Name',
            'file_path' => 'File Path',
            'file_type' => 'File Type',
            'file_size' => 'File Size',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
}
```

### Thêm relation vào Task model

```php
// Thêm vào common\models\task\Task.php

use common\models\task\TaskAttachment;

public function getAttachments(): ActiveQuery
{
    return $this->hasMany(TaskAttachment::class, ['task_id' => 'id']);
}
```

---

## 2. Repository Interface (Fixed)

```php
<?php

namespace common\repositories\task\interfaces;

use common\models\task\TaskAttachment;

interface TaskAttachmentRepositoryInterface
{
    /**
     * Lưu attachment (create hoặc update)
     */
    public function save(TaskAttachment $attachment): void;

    /**
     * Xóa 1 attachment theo primary key
     */
    public function deleteById(int $id): void;

    /**
     * Xóa tất cả attachments theo task_id
     */
    public function deleteAllByTaskId(int $taskId): void;

    /**
     * Tìm attachment theo primary key
     */
    public function getById(int $id): TaskAttachment;

    /**
     * Tìm tất cả attachments của 1 task
     */
    public function getByTaskId(int $taskId): array;
}
```

---

## 3. Repository (Fixed)

```php
<?php

namespace common\repositories\task;

use Yii;
use common\models\task\TaskAttachment;
use common\repositories\task\interfaces\TaskAttachmentRepositoryInterface;

class TaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function save(TaskAttachment $attachment): void
    {
        if (!$attachment->validate()) {
            throw new \DomainException(
                'Attachment validation failed: ' . json_encode($attachment->errors)
            );
        }

        // save(false) vì đã validate ở trên — tránh validate 2 lần
        if (!$attachment->save(false)) {
            throw new \RuntimeException('Cannot save TaskAttachment');
        }
    }

    /**
     * FIX: Xóa 1 attachment theo primary key (KHÔNG PHẢI task_id)
     */
    public function deleteById(int $id): void
    {
        $attachment = $this->getById($id);
        if (!$attachment->delete()) {
            throw new \RuntimeException("Cannot delete TaskAttachment ID={$id}");
        }
    }

    /**
     * Xóa tất cả attachments thuộc 1 task
     */
    public function deleteAllByTaskId(int $taskId): void
    {
        TaskAttachment::deleteAll(['task_id' => $taskId]);
    }

    public function getByTaskId(int $taskId): array
    {
        return TaskAttachment::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * FIX: param name đúng semantic — đây là attachment ID, không phải task ID
     */
    public function getById(int $id): TaskAttachment
    {
        $attachment = TaskAttachment::findOne($id);
        if ($attachment === null) {
            throw new \DomainException("TaskAttachment not found: ID={$id}");
        }
        return $attachment;
    }
}
```

---

## 4. DTO (NEW)

```php
<?php

namespace common\dto\task;

use common\models\task\TaskAttachment;

class TaskAttachmentDto
{
    public readonly int $id;
    public int $taskId;
    public int $userId;
    public string $fileName;
    public string $filePath;
    public string $fileType;
    public int $fileSize;
    public string $createdAt;
    public ?string $downloadUrl;

    public static function fromModel(TaskAttachment $model): self
    {
        $dto = new self();

        $dto->id = $model->id;
        $dto->taskId = $model->task_id;
        $dto->userId = $model->user_id;
        $dto->fileName = $model->file_name;
        $dto->filePath = $model->file_path;
        $dto->fileType = $model->file_type;
        $dto->fileSize = $model->file_size;
        $dto->createdAt = $model->created_at;
        $dto->downloadUrl = '/task/download-attachment?id=' . $model->id;

        return $dto;
    }

    public static function fromModels(array $models): array
    {
        return array_map(
            fn(TaskAttachment $model) => self::fromModel($model),
            $models
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'taskId' => $this->taskId,
            'fileName' => $this->fileName,
            'fileType' => $this->fileType,
            'fileSize' => $this->fileSize,
            'createdAt' => $this->createdAt,
            'downloadUrl' => $this->downloadUrl,
        ];
    }

    /**
     * Format file size cho hiển thị
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

---

## 5. Service (Fixed)

```php
<?php

namespace common\services\task;

use Yii;
use common\models\task\TaskAttachment;
use common\forms\task\TaskAttachmentForm;
use common\repositories\task\interfaces\TaskAttachmentRepositoryInterface;

class TaskAttachmentService implements TaskAttachmentServiceInterface
{
    private const UPLOAD_DIR = 'uploads/task';

    private TaskAttachmentRepositoryInterface $repository;

    public function __construct(TaskAttachmentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Upload nhiều file đính kèm cho 1 task
     *
     * @return TaskAttachment[] danh sách attachment đã lưu thành công
     * @throws \DomainException nếu form validation fail
     */
    public function upload(TaskAttachmentForm $form): array
    {
        if (!$form->validate()) {
            throw new \DomainException(
                'Attachment validation failed: ' . json_encode($form->errors)
            );
        }

        $taskDir = self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $form->task_id;
        $uploadPath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $taskDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $attachments = [];
        foreach ($form->files as $file) {
            $uniqueName = uniqid('task_att_', true) . '.' . $file->extension;
            $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $uniqueName;

            if (!$file->saveAs($fullPath)) {
                Yii::error("Cannot save file: {$file->name}", 'task.attachment');
                continue;
            }

            try {
                $attachment = new TaskAttachment();
                $attachment->task_id = $form->task_id;
                $attachment->user_id = $form->user_id;
                $attachment->file_name = $file->name;
                $attachment->file_path = $taskDir . DIRECTORY_SEPARATOR . $uniqueName;
                $attachment->file_type = $file->type;
                $attachment->file_size = $file->size;

                $this->repository->save($attachment);
                $attachments[] = $attachment;
            } catch (\Throwable $e) {
                // Rollback file nếu DB save fail
                @unlink($fullPath);
                Yii::error("Error saving attachment: {$e->getMessage()}", 'task.attachment');
            }
        }

        return $attachments;
    }

    public function getByTaskId(int $taskId): array
    {
        return $this->repository->getByTaskId($taskId);
    }

    /**
     * FIX: Xóa 1 attachment — dùng deleteById() thay vì delete()
     */
    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = $this->repository->getById($attachmentId);
        $this->deleteFile($attachment->file_path);
        $this->repository->deleteById($attachmentId);
    }

    /**
     * Xóa tất cả attachments của 1 task
     */
    public function deleteAllByTaskId(int $taskId): void
    {
        $attachments = $this->repository->getByTaskId($taskId);

        foreach ($attachments as $attachment) {
            $this->deleteFile($attachment->file_path);
        }

        $this->repository->deleteAllByTaskId($taskId);
    }

    private function deleteFile(string $relativePath): void
    {
        $fullPath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $relativePath;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}
```

---

## 6. Controller (tích hợp attachment)

```php
<?php
// Thêm vào TaskController.php

use yii\web\UploadedFile;
use common\services\task\TaskAttachmentServiceInterface;
use common\forms\task\TaskAttachmentForm;

class TaskController extends BaseController
{
    private TaskRepositoryInterface $taskRepository;
    private TaskServiceInterface $taskService;
    private TaskAttachmentServiceInterface $attachmentService;

    public function __construct(
        $id,
        $module,
        TaskRepositoryInterface $taskRepository,
        TaskServiceInterface $taskService,
        TaskAttachmentServiceInterface $attachmentService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->taskRepository = $taskRepository;
        $this->taskService = $taskService;
        $this->attachmentService = $attachmentService;
    }

    // ... existing actions ...

    /**
     * Upload attachment cho task
     */
    public function actionUploadAttachment(int $taskId)
    {
        // FIX: dùng getById() thay vì getTask()
        $task = $this->taskService->getById($taskId);
        if (!$task) {
            throw new NotFoundHttpException('Task không tồn tại.');
        }

        $form = new TaskAttachmentForm();
        $form->task_id = $taskId;
        $form->user_id = Yii::$app->user->id;
        $form->files = UploadedFile::getInstances($form, 'files');

        if (empty($form->files)) {
            Yii::$app->session->setFlash('warning', 'Chưa chọn file nào.');
            return $this->redirect(['view', 'id' => $taskId]);
        }

        try {
            $attachments = $this->attachmentService->upload($form);
            $count = count($attachments);
            Yii::$app->session->setFlash('success', "Đã upload {$count} file thành công.");
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', 'Upload thất bại: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $taskId]);
    }

    /**
     * Xóa 1 attachment
     */
    public function actionDeleteAttachment(int $id)
    {
        try {
            $this->attachmentService->deleteAttachment($id);
            Yii::$app->session->setFlash('success', 'Đã xóa file đính kèm.');
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', 'Không thể xóa file.');
        }

        return $this->redirect(Yii::$app->request->referrer ?? ['index']);
    }

    /**
     * Download attachment
     */
    public function actionDownloadAttachment(int $id)
    {
        try {
            $attachments = $this->attachmentService->getByTaskId(0); // placeholder
            // Lấy từ repo trực tiếp qua service
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('File không tồn tại.');
        }

        // Cách đúng: thêm method getById vào service hoặc dùng trực tiếp
        $attachment = Yii::createObject(TaskAttachmentRepositoryInterface::class)->getById($id);
        $filePath = Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $attachment->file_path;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File không tồn tại trên server.');
        }

        return Yii::$app->response->sendFile($filePath, $attachment->file_name);
    }

    /**
     * View task — cập nhật để load attachments
     */
    public function actionView($id)
    {
        $task = $this->taskService->getById($id);  // FIX: getTask() → getById()

        if ($task === null) {
            throw new NotFoundHttpException('Task không tồn tại.');
        }

        $attachments = $this->attachmentService->getByTaskId($id);
        $attachmentForm = new TaskAttachmentForm();

        return $this->render('view', [
            'model' => $task,
            'attachments' => $attachments,
            'attachmentForm' => $attachmentForm,
        ]);
    }
}
```

> [!TIP]
> Nên thêm `actionDownloadAttachment` vào `behaviors()` VerbFilter nếu cần restrict HTTP method, và thêm permission check nếu dùng RBAC.

---

## 7. Views

### _attachments.php (hiển thị danh sách file)

```php
<?php
/**
 * @var yii\web\View $this
 * @var common\models\task\TaskAttachment[] $attachments
 * @var common\forms\task\TaskAttachmentForm $attachmentForm
 * @var int $taskId
 */

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\file\FileInput;

// Icon mapping theo file type
$iconMap = [
    'application/pdf' => 'fas fa-file-pdf text-danger',
    'image/jpeg' => 'fas fa-file-image text-primary',
    'image/png' => 'fas fa-file-image text-primary',
    'application/msword' => 'fas fa-file-word text-info',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word text-info',
    'application/vnd.ms-excel' => 'fas fa-file-excel text-success',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel text-success',
];

/**
 * Format file size
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-paperclip"></i> File đính kèm
            <span class="badge badge-secondary"><?= count($attachments) ?></span>
        </h5>
    </div>
    <div class="card-body">

        <?php if (!empty($attachments)): ?>
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th>Tên file</th>
                        <th>Kích thước</th>
                        <th>Ngày upload</th>
                        <th style="width:120px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $att): ?>
                        <tr>
                            <td>
                                <i class="<?= $iconMap[$att->file_type] ?? 'fas fa-file text-muted' ?>"></i>
                            </td>
                            <td><?= Html::encode($att->file_name) ?></td>
                            <td><?= formatFileSize($att->file_size) ?></td>
                            <td><?= Yii::$app->formatter->asDatetime($att->created_at) ?></td>
                            <td>
                                <?= Html::a(
                                    '<i class="fas fa-download"></i>',
                                    ['download-attachment', 'id' => $att->id],
                                    ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Download']
                                ) ?>
                                <?= Html::a(
                                    '<i class="fas fa-trash"></i>',
                                    ['delete-attachment', 'id' => $att->id],
                                    [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'title' => 'Xóa',
                                        'data' => [
                                            'confirm' => 'Bạn có chắc chắn muốn xóa file này?',
                                            'method' => 'post',
                                        ],
                                    ]
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mb-0">Chưa có file đính kèm.</p>
        <?php endif; ?>

        <hr>

        <!-- Upload form -->
        <h6><i class="fas fa-upload"></i> Upload file mới</h6>
        <?php $form = ActiveForm::begin([
            'action' => ['upload-attachment', 'taskId' => $taskId],
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <?= $form->field($attachmentForm, 'files[]')->widget(FileInput::class, [
            'options' => ['multiple' => true, 'accept' => '.jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx'],
            'pluginOptions' => [
                'showPreview' => true,
                'showCaption' => true,
                'showRemove' => true,
                'showUpload' => false,
                'maxFileCount' => 5,
                'maxFileSize' => 10240, // 10MB in KB
            ],
        ])->label(false) ?>

        <?= Html::submitButton(
            '<i class="fas fa-upload"></i> Upload',
            ['class' => 'btn btn-success btn-sm']
        ) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
```

### view.php — thêm render attachments

```php
<!-- Thêm vào cuối file view.php, trước </div> đóng -->

<?= $this->render('_attachments', [
    'attachments' => $attachments,
    'attachmentForm' => $attachmentForm,
    'taskId' => $model->id,
]) ?>
```

### _form.php — thêm file upload vào form create/update

```php
<!-- Thêm enctype vào ActiveForm::begin() -->
<?php $form = ActiveForm::begin([
    'id' => 'task-form',
    'type' => ActiveForm::TYPE_HORIZONTAL,
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'options' => ['enctype' => 'multipart/form-data'],  // ← THÊM DÒNG NÀY
]); ?>

<!-- Thêm field upload trước nút submit -->
<?= $form->field($attachmentForm, 'files[]')->widget(\kartik\file\FileInput::class, [
    'options' => ['multiple' => true],
    'pluginOptions' => [
        'showPreview' => false,
        'maxFileCount' => 5,
    ],
])->label('File đính kèm') ?>
```

---

## 8. Migration Fix (nếu chưa chạy)

```php
// Đổi trong safeUp():
'created_at' => $this->dateTime()->notNull(),   // date() → dateTime()
'updated_at' => $this->dateTime(),               // date() → dateTime()
```

Nếu migration đã chạy, tạo migration mới:

```php
<?php

use yii\db\Migration;

class m260221_000000_fix_task_attachment_datetime_columns extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%task_attachment}}', 'created_at', $this->dateTime()->notNull());
        $this->alterColumn('{{%task_attachment}}', 'updated_at', $this->dateTime());
    }

    public function safeDown()
    {
        $this->alterColumn('{{%task_attachment}}', 'created_at', $this->date()->notNull());
        $this->alterColumn('{{%task_attachment}}', 'updated_at', $this->date());
    }
}
```
