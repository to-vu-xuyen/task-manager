<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use common\models\task\Task;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

$this->title = 'Danh sách Task';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-plus"></i> Tạo Task mới', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'responsive' => true,
        // 'hover' => true,
        // 'striped' => true,
        // 'bordered' => true,
        'pjax' => true,
        'pjaxSettings' => [
            'options' => [
                'id' => 'task-grid-pjax',
            ],
        ],
        // 'panel' => [
        //     'type' => GridView::TYPE_PRIMARY,
        //     'heading' => '<i class="fas fa-tasks"></i> Danh sách công việc',
        // ],
        // 'toolbar' => [
        //     [
        //         'content' =>
        //             Html::a('<i class="fas fa-plus"></i>', ['create'], [
        //                 'class' => 'btn btn-success',
        //                 'title' => 'Tạo mới',
        //             ]) . ' ' .
        //             Html::a('<i class="fas fa-sync"></i>', ['index'], [
        //                 'class' => 'btn btn-outline-secondary',
        //                 'title' => 'Refresh',
        //                 'data-pjax' => 0,
        //             ]),
        //     ],
        //     '{export}',
        //     '{toggleData}',
        // ],
        // 'export' => [
        //     'fontAwesome' => true,
        // ],
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn'],
            
            'id',
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a($model->title, ['view', 'id' => $model->id], [
                        'class' => 'text-primary font-weight-bold',
                    ]);
                },
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    $statusLabels = [
                        Task::STATUS_PENDING => ['label' => 'Chờ xử lý', 'class' => 'badge badge-secondary'],
                        Task::STATUS_IN_PROGRESS => ['label' => 'Đang làm', 'class' => 'badge badge-primary'],
                        Task::STATUS_COMPLETED => ['label' => 'Hoàn thành', 'class' => 'badge badge-success'],
                        Task::STATUS_CANCELLED => ['label' => 'Đã hủy', 'class' => 'badge badge-danger'],
                    ];
                    $status = $statusLabels[$model->status] ?? ['label' => 'Unknown', 'class' => 'badge badge-dark'];
                    return Html::tag('span', $status['label'], ['class' => $status['class']]);
                },
                'filter' => [
                    Task::STATUS_PENDING => 'Chờ xử lý',
                    Task::STATUS_IN_PROGRESS => 'Đang làm',
                    Task::STATUS_COMPLETED => 'Hoàn thành',
                    Task::STATUS_CANCELLED => 'Đã hủy',
                ],
            ],
            // [
            //     'attribute' => 'priority',
            //     'format' => 'raw',
            //     'value' => function ($model) {
            //         $priorityLabels = [
            //             Task::PRIORITY_LOW => ['label' => 'Thấp', 'class' => 'badge badge-info'],
            //             Task::PRIORITY_MEDIUM => ['label' => 'Trung bình', 'class' => 'badge badge-warning'],
            //             Task::PRIORITY_HIGH => ['label' => 'Cao', 'class' => 'badge badge-danger'],
            //         ];
            //         $priority = $priorityLabels[$model->priority] ?? ['label' => 'Unknown', 'class' => 'badge badge-dark'];
            //         return Html::tag('span', $priority['label'], ['class' => $priority['class']]);
            //     },
            //     'filter' => [
            //         Task::PRIORITY_LOW => 'Thấp',
            //         Task::PRIORITY_MEDIUM => 'Trung bình',
            //         Task::PRIORITY_HIGH => 'Cao',
            //     ],
            // ],
            [
                'attribute' => 'due_at',
                'format' => 'datetime',
                'value' => function ($model) {
                    return $model->due_at;
                },
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info',
                            'title' => 'Xem chi tiết',
                        ]);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Chỉnh sửa',
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Xóa',
                            'data-confirm' => 'Bạn có chắc chắn muốn xóa task này?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>

</div>