<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\task\Task;

/**
 * @var yii\web\View $this
 * @var common\models\task\Task $model
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Danh sách Task', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Status labels
$statusLabels = [
    Task::STATUS_PENDING => ['label' => 'Chờ xử lý', 'class' => 'badge badge-secondary'],
    Task::STATUS_IN_PROGRESS => ['label' => 'Đang làm', 'class' => 'badge badge-primary'],
    Task::STATUS_COMPLETED => ['label' => 'Hoàn thành', 'class' => 'badge badge-success'],
    Task::STATUS_CANCELLED => ['label' => 'Đã hủy', 'class' => 'badge badge-danger'],
];
$status = $statusLabels[$model->status] ?? ['label' => 'Unknown', 'class' => 'badge badge-dark'];
?>

<div class="task-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-edit"></i> Chỉnh sửa', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="fas fa-trash"></i> Xóa', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Bạn có chắc chắn muốn xóa task này?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('<i class="fas fa-arrow-left"></i> Quay lại', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-info-circle"></i> Thông tin Task
                <span class="<?= $status['class'] ?> float-right"><?= $status['label'] ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'title',
                    'description:ntext',
                    'content:ntext',
                    [
                        'attribute' => 'user_id',
                        'label' => 'Người tạo',
                        'value' => $model->user->username ?? 'N/A',
                    ],
                    [
                        'attribute' => 'assignee_id',
                        'label' => 'Người thực hiện',
                        'value' => $model->assignee->username ?? 'Chưa gán',
                    ],
                    [
                        'attribute' => 'due_at',
                        'format' => 'datetime',
                        'label' => 'Thời hạn',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>
