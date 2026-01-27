<?php

use kartik\form\ActiveForm;
use kartik\datecontrol\DateControl;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\models\User;

/**
 * @var yii\web\View $this
 * @var common\forms\task\TaskCreateForm|common\forms\task\TaskUpdateForm $model
 */
?>

<div class="task-form">

    <?php $form = ActiveForm::begin([
        'id' => 'task-form',
        'type' => ActiveForm::TYPE_HORIZONTAL,
        // 'formConfig' => [
        //     'labelSpan' => 3,
        //     'deviceSize' => ActiveForm::SIZE_SMALL,
        // ],
        'enableAjaxValidation' => false,
        'enableClientValidation' => true,
    ]); ?>

    <?= $form->field($model, 'title')->textInput([
        'maxlength' => true,
        'placeholder' => 'Nhập tiêu đề task...',
    ]) ?>

    <?= $form->field($model, 'description')->textarea([
        'rows' => 3,
        'placeholder' => 'Mô tả ngắn gọn về task...',
    ]) ?>

    <?= $form->field($model, 'content')->textarea([
        'rows' => 6,
        'placeholder' => 'Nội dung chi tiết của task...',
    ]) ?>

    <?= $form->field($model, 'assignee_id')->dropDownList(
        ArrayHelper::map(User::find()->where(['status' => User::STATUS_ACTIVE])->all(), 'id', 'username'),
        [
            'prompt' => '-- Chọn người thực hiện --',
            'class' => 'form-control',
        ]
    ) ?>

    <?= $form->field($model, 'due_at')->widget(DateControl::class, [
        'type' => DateControl::FORMAT_DATETIME,
        'displayFormat' => 'php:d/m/Y H:i',
        'saveFormat' => 'php:Y-m-d H:i:s',
        'options' => [
            'placeholder' => 'Chọn thời hạn...',
        ],
        'pluginOptions' => [
            'autoclose' => true,
            'todayHighlight' => true,
        ],
    ]) ?>

    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
            <?= Html::submitButton(
                '<i class="fas fa-save"></i> ' . ($isCreate ? 'Tạo Task' : 'Cập nhật'),
                ['class' => 'btn btn-success']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-times"></i> Hủy',
                ['index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
