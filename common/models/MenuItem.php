<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "menu_item".
 *
 * @property int $id
 * @property string $label
 * @property string|null $url
 * @property string|null $icon
 * @property string|null $icon_type
 * @property int|null $parent_id
 * @property string $location
 * @property int|null $sort_order
 * @property string|null $target
 * @property int|null $heading
 * @property int|null $visible
 * @property int|null $only_developers
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $visible_to_roles
 *
 * @property MenuItem[] $menuItems
 * @property MenuItem $parent
 */
class MenuItem extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'menu_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url', 'icon', 'icon_type', 'parent_id', 'target', 'visible_to_roles'], 'default', 'value' => null],
            [['location'], 'default', 'value' => 'backend'],
            [['only_developers'], 'default', 'value' => 0],
            [['visible'], 'default', 'value' => 1],
            [['label'], 'required'],
            [['parent_id', 'sort_order', 'heading', 'visible', 'only_developers'], 'integer'],
            [['created_at', 'updated_at', 'visible_to_roles'], 'safe'],
            [['label', 'url'], 'string', 'max' => 255],
            [['icon'], 'string', 'max' => 100],
            [['icon_type', 'target'], 'string', 'max' => 20],
            [['location'], 'string', 'max' => 50],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => MenuItem::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'label' => 'Label',
            'url' => 'Url',
            'icon' => 'Icon',
            'icon_type' => 'Icon Type',
            'parent_id' => 'Parent ID',
            'location' => 'Location',
            'sort_order' => 'Sort Order',
            'target' => 'Target',
            'heading' => 'Heading',
            'visible' => 'Visible',
            'only_developers' => 'Only Developers',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'visible_to_roles' => 'Visible To Roles',
        ];
    }

    /**
     * Gets query for [[MenuItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMenuItems()
    {
        return $this->hasMany(MenuItem::class, ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(MenuItem::class, ['id' => 'parent_id']);
    }

}
