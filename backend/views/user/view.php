<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\User */
?>
<div class="user-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'username',
            'email:email',
            [
                'attribute' => 'status',
                'value' => $model->getStatusLabel(),
                'format' => 'raw',
            ],
            [
                'label' => 'Role',
                'value' => $model->getRoleName() ?: 'None',
            ],
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>