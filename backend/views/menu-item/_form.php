<?php

use common\models\Role;
use kartik\form\ActiveForm;
use kartik\switchinput\SwitchInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\web\View;

/** @var yii\web\View $this */
/** @var common\models\MenuItem $model */
/** @var yii\widgets\ActiveForm $form */

// Parent Items
$parentItems = \common\models\MenuItem::find()
    ->where(['parent_id' => null])
    ->select(['label', 'id'])
    ->indexBy('id')
    ->column();

?>

<div class="menu-item-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
        </div>
    </div>


    <?= $form->field($model, 'location')->dropDownList([
        'backend' => 'Backend',
        'frontend' => 'Frontend',
        'both' => 'Both',
    ]) ?>

    <?= $form->field($model, 'parent_id')->widget(Select2::classname(), [
        'data' => $parentItems,
        'options' => [
            'placeholder' => 'No Parent (Top Level)',
        ],
        'pluginOptions' => [
            'allowClear' => true,
            'dropdownParent' => '#ajaxCrudModal'
        ]
    ]); ?>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'icon')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'icon_type')->dropDownList([
                'fas' => 'Solid (fas)',
                'far' => 'Regular (far)',
                'fab' => 'Brands (fab)',
            ], ['prompt' => 'Select icon style']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'target')->dropDownList([
                '_self' => 'Same Tab',
                '_blank' => 'New Tab',
            ], ['prompt' => 'Select Target']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'heading')->widget(SwitchInput::classname(), [
                'type' => SwitchInput::CHECKBOX,
                'hashVarLoadPosition' => View::POS_READY,
            ]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'visible')->widget(SwitchInput::classname(), [
                'type' => SwitchInput::CHECKBOX,
                'hashVarLoadPosition' => View::POS_READY,
            ]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'only_developers')->widget(SwitchInput::classname(), [
                'type' => SwitchInput::CHECKBOX,
                'hashVarLoadPosition' => View::POS_READY,
            ]) ?>
        </div>
    </div>

    <?php /* echo $form->field($model, 'visible_to_roles')->widget(Select2::class, [
'data' => ArrayHelper::map(Role::find()->all(), 'id', 'name'), // Replace `name` with your display column
'options' => ['multiple' => true],
'pluginOptions' => [
'allowClear' => true,
'placeholder' => 'Select roles...',
],
]); */ ?>

    <?php ActiveForm::end(); ?>

</div>