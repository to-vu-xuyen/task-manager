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
use common\helpers\FileStorageHelper;

$iconMap = [
    'application/pdf' => 'fas fa-file-pdf text-danger',
    'image/jpeg' => 'fas fa-file-image text-primary',
    'image/png' => 'fas fa-file-image text-primary',
    'image/gif' => 'fas fa-file-image text-primary',
    'application/msword' => 'fas fa-file-word text-info',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word text-info',
    'application/vnd.ms-excel' => 'fas fa-file-excel text-success',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel text-success',
];

?>

<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-paperclip"></i> File đính kèm
            <span class="badge badge-secondary ml-1"><?= count($attachments) ?></span>
        </h5>
    </div>
    <div class="card-body">

        <?php if (!empty($attachments)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px"></th>
                            <th>Tên file</th>
                            <th style="width:100px">Kích thước</th>
                            <th style="width:160px">Ngày upload</th>
                            <th style="width:120px" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $att): ?>
                            <tr>
                                <td class="text-center">
                                    <i class="<?= $iconMap[$att->file_type] ?? 'fas fa-file text-muted' ?>"></i>
                                </td>
                                <td>
                                    <?= Html::a(
                                        Html::encode($att->file_name),
                                        ['download-attachment', 'id' => $att->id],
                                        ['title' => 'Click để download']
                                    ) ?>
                                </td>
                                <td class="text-muted"><?= FileStorageHelper::formatFileSize($att->file_size) ?></td>
                                <td class="text-muted">
                                    <?= Yii::$app->formatter->asDatetime($att->created_at, 'medium') ?>
                                </td>
                                <td class="text-center">
                                    <?= Html::a(
                                        '<i class="fas fa-download"></i>',
                                        ['download-attachment', 'id' => $att->id],
                                        ['class' => 'btn btn-sm btn-outline-primary mr-1', 'title' => 'Download']
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="fas fa-trash"></i>',
                                        ['delete-attachment', 'id' => $att->id],
                                        [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'title' => 'Xóa',
                                            'data' => [
                                                'confirm' => 'Bạn có chắc chắn muốn xóa file "' . Html::encode($att->file_name) . '"?',
                                                'method' => 'post',
                                            ],
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">
                <i class="fas fa-info-circle"></i> Chưa có file đính kèm nào.
            </p>
        <?php endif; ?>

        <hr>

        <h6><i class="fas fa-upload"></i> Upload file mới</h6>

        <?php $form = ActiveForm::begin([
            'action' => ['upload-attachment', 'taskId' => $taskId],
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <?= $form->field($attachmentForm, 'files[]')->widget(FileInput::class, [
            'options' => ['multiple' => true],
            'pluginOptions' => [
                'showPreview' => true,
                'showCaption' => true,
                'showRemove' => true,
                'showUpload' => false,
                'maxFileCount' => 5,
                'maxFileSize' => 10240,
                'browseLabel' => 'Chọn file',
            ],
        ])->label(false) ?>

        <div class="form-group mt-2">
            <?= Html::submitButton(
                '<i class="fas fa-upload"></i> Upload',
                ['class' => 'btn btn-success btn-sm']
            ) ?>
            <small class="text-muted ml-2">
                Tối đa 10MB/file, 5 file/lần.
            </small>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
