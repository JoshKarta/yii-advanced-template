<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $form yii\widgets\ActiveForm */
/* @var $roleList array */

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', ['position' => \yii\web\View::POS_HEAD]);
$isAdmin = Yii::$app->user->can('admin');
?>

<div class="user-form">

	<?php $form = ActiveForm::begin(); ?>

	<?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autofocus' => true, 'disabled' => $model->isNewRecord ? false : true]) ?>

	<?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

	<div class="mb-3">
		<?= Html::activeLabel($model, 'password', ['class' => 'form-label']) ?>
		<div class="input-group">
			<?= Html::activePasswordInput($model, 'password', [
				'class' => 'form-control',
				'id' => 'user-password',
				'placeholder' => 'Enter password',
				'value' => '',
			]) ?>
			<div class="btn-group ms-1" role="group" aria-label="Basic example">
				<button class="btn btn-outline-info" type="button" id="generate-password-btn" title="Generate Password">
					<i class="fas fa-dice"></i>
				</button>
				<button class="btn btn-outline-secondary" type="button" id="copy-password-btn" title="Copy Password">
					<i class="fas fa-copy"></i>
				</button>
			</div>
		</div>
		<div class="form-text text-muted" id="password-help">
			<?= $model->isNewRecord ? 'Required for new user.' : 'Leave blank to keep current password.' ?>
		</div>
	</div>

	<?php if ($isAdmin): ?>
		<div class="row">
			<div class="col-md-6">
				<?= $form->field($model, 'status')->dropDownList([
					\common\models\User::STATUS_ACTIVE => 'Active',
					\common\models\User::STATUS_INACTIVE => 'Inactive',
					\common\models\User::STATUS_DELETED => 'Deleted',
				]) ?>
			</div>
			<div class="col-md-6 mb-3">
				<label class="form-label">Role</label>
				<?= Select2::widget([
					'name' => 'role',
					'id' => 'user-role',
					'value' => $selectedRole ?? null,
					'data' => $roleList,
					'options' => ['placeholder' => 'Select a role'],
					'pluginOptions' => [
						'allowClear' => true,
						'dropdownParent' => '#ajaxCrudModal'
					],
				]) ?>
			</div>
		</div>
	<?php endif; ?>
</div>


<?php if (!Yii::$app->request->isAjax): ?>
	<div class="form-group">
		<?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success rounded-3' : 'btn btn-primary rounded-3']) ?>
	</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>

</div>

<?php
$js = <<<JS
    $('#generate-password-btn').click(function() {
        let password = generateRandomPassword(12);
        $('#user-password').val(password);
    });

    $('#copy-password-btn').click(function() {
        let password = $('#user-password').val();
        if (!password) {
            Swal.fire('Info', 'No password to copy.', 'info');
            return;
        }
        navigator.clipboard.writeText(password).then(function() {
            Swal.fire('Success', 'Password copied to clipboard.', 'success');
        }).catch(function() {
            // Fallback
            let field = document.getElementById('user-password');
            field.select();
            document.execCommand('copy');
            Swal.fire('Success', 'Password copied to clipboard.', 'success');
        });
    });

    function generateRandomPassword(length) {
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        let password = '';
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    // Update help text based on new/edit state
    $(document).ready(function() {
        let isNew = $('#user-password').closest('form').find('[name$="[id]"]').length === 0;
        if (!isNew) {
            $('#password-help').text('Leave blank to keep current password.');
        }
    });
JS;
$this->registerJs($js);
?>