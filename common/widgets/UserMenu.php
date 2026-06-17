<?php

namespace common\widgets;

use Yii;
use yii\base\Widget;

class UserMenu extends Widget
{
    /**
     * @var array Additional menu items to append before logout
     */
    public $extraItems = [];

    /**
     * @var string CSS class for the dropdown button
     */
    public $buttonClass = 'btn btn-light d-flex align-items-center gap-2';

    /**
     * @var string CSS class for the dropdown menu
     */
    public $menuClass = 'dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2';

    /**
     * @var bool Whether to show user avatar with initials
     */
    public $showAvatar = true;

    /**
     * @var bool Whether to show user name
     */
    public $showName = true;

    public function run()
    {
        return $this->render('user-menu');
    }

    public function render($view, $params = [])
    {
        $params['widget'] = $this;
        return Yii::$app->view->renderFile('@common/widgets/views/user-menu.php', $params, $this);
    }
}