<?php
use kartik\form\ActiveForm;


?>
<div class="content-register">

	<?php $form = ActiveForm::begin([
		// 'id' => 'gc-register-form',
		'options' => ['class' => 'form-horizontal'],
		'fieldConfig' => [
			'template' => "<div class=\"row\">{label}\n<div class=\"col-lg-5\">{input}</div></div>\n<div class=\"col-lg-9 offset-lg-3\">{error}</div>",
			'labelOptions' => ['class' => 'col-lg-2 offset-lg-1 control-label'],
		],
	]) ?>
	<?= $form->field($model, 'email') ?>
	<?= $form->field($model, 'password')->passwordInput() ?>
	<?= $form->field($model, 'password_confirm')->passwordInput() ?>

	<hr/>
	<button class="btn-dangky offset-lg-3">Đăng ký</button>
	<?php ActiveForm::end(); ?>
</div>
