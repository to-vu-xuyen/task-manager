<?php
use yii\helpers\Html;
use kartik\form\ActiveForm;

$this->title = 'Create Account';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin([
    'id' => 'signup-form',
    // 'enableClientValidation' => true,
    // 'enableAjaxValidation' => false,
]); ?>

<?= $form->field($model, 'username')->textInput([
    'autofocus' => true,
]) ?>

<?= $form->field($model, 'email')->input('email') ?>

<?= $form->field($model, 'password')->passwordInput() ?>
<?= $form->field($model, 'password_confirm')->passwordInput() ?>

<div class="form-group">
    <?= Html::submitButton('Create Account', [
        'class' => 'btn btn-primary',
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
