<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\User */
?>
<div class="user-update">
    <div class="<?= !$model->isNewRecord ? '' : 'card rounded-4' ?>">
        <div class="<?= !$model->isNewRecord ? '' : 'card-body' ?>">
            <?= $this->render('_form', [
                'model' => $model,
                'roleList' => $roleList,
                'selectedRole' => $selectedRole,
            ]) ?>
        </div>
    </div>
</div>