<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\field\FieldRange;

/** @var yii\web\View $this */
/** @var common\forms\user\UserLoginForm $form */

$this->title = 'Đăng nhập';
?>

<div class="auth-login">
    <div class="card shadow-sm">
        <div class="card-header text-center">
            <h4 class="mb-0">
                <?= Html::encode($this->title) ?>
            </h4>
        </div>
        <div class="card-body p-4">
            
            <?php $active_form = ActiveForm::begin([
                'id' => 'login-form',
                'type' => ActiveForm::TYPE_VERTICAL,
                'formConfig' => [
                    'showLabels' => true,
                    'showErrors' => true,
                ],
                'options' => [
                    'class' => 'form-login',
                ],
            ]); ?>

            <?= $active_form->field($form, 'username')->textInput([
                'placeholder' => 'Nhập tên đăng nhập',
                'autofocus' => true,
            ]) ?>

            <?= $active_form->field($form, 'password')->passwordInput([
                'placeholder' => 'Nhập mật khẩu',
            ]) ?>

            <?= $active_form->field($form, 'rememberMe')->checkbox() ?>

            <div class="d-grid gap-2 mt-4">
                <?= Html::submitButton(
                    '<i class="fas fa-sign-in-alt me-2"></i> Đăng nhập',
                    ['class' => 'btn btn-primary btn-lg', 'name' => 'login-button']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">
                    Chưa có tài khoản? 
                    <?= Html::a('Đăng ký ngay', ['auth/signup'], ['class' => 'text-primary fw-bold']) ?>
                </p>
            </div>

        </div>
    </div>
    
</div>
