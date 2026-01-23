<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var common\forms\task\TaskCreateForm $model
 */

$this->title = 'Tạo Task mới';
$this->params['breadcrumbs'][] = ['label' => 'Danh sách Task', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-plus-circle"></i> Thông tin Task
            </h5>
        </div>
        <div class="card-body">
            <?= $this->render('_form', [
                'model' => $model,
                'isCreate' => true,
            ]) ?>
        </div>
    </div>

</div>
