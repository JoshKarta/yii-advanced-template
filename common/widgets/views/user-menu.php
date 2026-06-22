<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\Dropdown;

/* @var $this yii\web\View */
/* @var $widget \app\components\UserMenuWidget */

$extraItems = $widget->extraItems;
$buttonClass = $widget->buttonClass;
$menuClass = $widget->menuClass;
$showAvatar = $widget->showAvatar;
$showName = $widget->showName;
$hashedId = Yii::$app->hashId->encode(Yii::$app->user->id);
?>

<div class="user-menu-widget">
    <?php if (Yii::$app->user->isGuest): ?>
        <?= Html::a('Login', ['site/login'], [
            'class' => 'btn btn-outline-primary rounded-4 px-4',
            'style' => 'border-color: #0f5132; color: #0f5132; font-weight: 500; white-space: nowrap;'
        ]) ?>
    <?php else: ?>
        <?php
        $user = Yii::$app->user->identity;
        $username = $user->username;
        $initial = strtoupper(substr($username, 0, 1));

        // Build dropdown items
        $dropdownItems = [
            [
                'label' => '<i class="fas fa-id-card me-2"></i> Dashboard',
                'url' => ['site/dashboard'],
            ],
            [
                'label' => '<i class="fas fa-user me-2"></i> Profile',
                'url' => ['user/profile', 'id' => $hashedId],
            ],
        ];

        // Add extra items passed from widget
        foreach ($extraItems as $item) {
            if (is_string($item)) {
                $dropdownItems[] = $item; // can be a divider or raw HTML
            } else {
                $dropdownItems[] = [
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'linkOptions' => $item['linkOptions'] ?? [],
                ];
            }
        }

        // Check admin access (RBAC or legacy role)
        $isAdmin = Yii::$app->user->can('admin') || (property_exists($user, 'role') && $user->role === 'admin');
        if ($isAdmin) {
            $dropdownItems[] = '<hr class="dropdown-divider">';
            $dropdownItems[] = [
                'label' => '<i class="fas fa-cog me-2"></i> Admin Panel',
                'url' => ['admin/index'],
            ];
        }

        $dropdownItems[] = '<hr class="dropdown-divider">';
        $dropdownItems[] = [
            'label' => '<i class="fas fa-sign-out-alt me-2"></i> Logout',
            'url' => ['site/logout'],
            'linkOptions' => ['data-method' => 'post', 'class' => 'text-danger'],
        ];
        ?>

        <div class="dropdown">
            <?php
            $buttonContent = '';
            if ($showAvatar) {
                $buttonContent .= '<div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: linear-gradient(135deg, #0f5132 0%, #0a4a7a 100%);">
                                        <i class="fas fa-user text-white fa-xs"></i>
                                    </div>';
            }
            if ($showName) {
                $buttonContent .= '<span class="fw-semibold" style="color: #2d3748;">' . Html::encode($username) . '</span>';
            }
            echo Html::button($buttonContent, [
                'class' => $buttonClass . ' dropdown-toggle',
                'data-bs-toggle' => 'dropdown',
                'aria-expanded' => 'false',
                'style' => 'border-radius: 40px; padding: 6px 16px 6px 12px; background: #f8f9fa; border: 1px solid #e9ecef;'
            ]);
            ?>
            <?= Dropdown::widget([
                'items' => $dropdownItems,
                'options' => ['class' => $menuClass, 'style' => 'border-radius: 12px; min-width: 200px;'],
                'encodeLabels' => false,
            ]) ?>
        </div>
    <?php endif; ?>
</div>