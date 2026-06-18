<?php

use common\models\MenuItem;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

// Detect current application (backend or frontend)
$appId = Yii::$app->id;
$currentLocation = ($appId === 'app-backend') ? 'backend' : 'frontend';

// Cache key includes location to separate menus
$cacheKey = 'menu_items_' . $currentLocation;

/**
 * Recursively build the nested menu tree.
 */
function buildMenuTree($items, $parentId = null)
{
    $result = [];
    foreach ($items as $item) {
        if ($item->parent_id == $parentId) {
            // Skip if not visible (or if user lacks permission)
            if (!$item->visible) {
                continue;
            }

            // Optional: role‑based visibility (if you enable `visible_to_roles`)
            if (!empty($item->visible_to_roles)) {
                $allowedRoles = explode(',', $item->visible_to_roles);
                $userRoles = array_keys(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id));
                if (!array_intersect($allowedRoles, $userRoles)) {
                    continue;
                }
            }

            $menuNode = [
                'label' => $item->label,
                'icon' => $item->icon ?: null,
            ];

            if ($item->heading) {
                // This is a header (section title)
                $menuNode['header'] = true;
            } else {
                // Generate the proper URL for the current application context
                // Url::to() will respect the application's base URL and routing rules
                if ($item->url == '/') {
                    $menuNode['url'] = Url::home();      // respects Yii::$app->homeUrl
                } elseif ($item->url) {
                    $menuNode['url'] = Url::to($item->url);
                } else {
                    $menuNode['url'] = '#';
                }

                // Add target if set
                if ($item->target) {
                    $menuNode['linkOptions'] = ['target' => $item->target];
                }

                // Map icon_type (e.g., 'far', 'fas') to the widget's 'iconStyle'
                if ($item->icon_type) {
                    $menuNode['iconStyle'] = $item->icon_type;
                }
            }

            // Recursively fetch children
            $children = buildMenuTree($items, $item->id);
            if (!empty($children)) {
                $menuNode['items'] = $children;
            }

            // Developer‑only items: only show if user has the 'admin' permission
            if ($item->only_developers && !Yii::$app->user->can('admin')) {
                continue;
            }

            $result[] = $menuNode;
        }
    }
    return $result;
}

// Try to fetch from cache
$menuItems = Yii::$app->cache->get($cacheKey);

if ($menuItems === false) {
    // Fetch all active menu items for the current location (backend/frontend)
    $records = MenuItem::find()
        ->where(['in', 'location', [$currentLocation, 'both']])
        ->andWhere(['visible' => 1])
        ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
        ->all();

    $menuItems = buildMenuTree($records);
    // Cache for 1 hour (or you can use a database dependency for automatic invalidation)
    Yii::$app->cache->set($cacheKey, $menuItems, 3600);
}
?>


<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
        <img src="<?= $assetDir ?>/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
            style="opacity: .8">
        <span class="brand-text font-weight-light">AdminLTE 3</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= $assetDir ?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">Alexander Pierce</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <?php
            echo \hail812\adminlte\widgets\Menu::widget([
                'items' => $menuItems,
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>