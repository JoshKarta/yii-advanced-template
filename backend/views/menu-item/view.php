<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\MenuItem */
?>
<div class="menu-item-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'label',
            'url:url',
            'icon',
            'icon_type',
            'parent_id',
            'location',
            'sort_order',
            'target',
            'heading',
            'visible',
            'only_developers',
            'created_at',
            'updated_at',
            'visible_to_roles',
        ],
    ]) ?>

</div>
