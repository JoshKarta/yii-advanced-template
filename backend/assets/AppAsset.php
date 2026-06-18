<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace backend\assets;

use common\assets\ColorModeAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';

    public $baseUrl = '@web';

    public $css = [
        'css/site.css',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
    ];

    public $js = [
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
        "https://unpkg.com/lucide@1.0.0",
        "https://unpkg.com/lucide@1.0.0/dist/umd/lucide.js"
    ];

    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        ColorModeAsset::class,
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        SortableAsset::class
    ];
}
