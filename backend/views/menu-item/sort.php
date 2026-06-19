<?php

use common\models\MenuItem;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\web\View;
use yii\jui\JuiAsset;

JuiAsset::register($this);
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

$this->title = 'Sort Menu Items';
$this->params['breadcrumbs'][] = ['label' => 'Menu Items', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

function buildTreeForSort($items, $parentId = null)
{
    $result = [];
    foreach ($items as $item) {
        if ($item->parent_id == $parentId) {
            $node = [
                'id' => $item->id,
                'label' => $item->label,
                'icon' => $item->icon,
                'children' => buildTreeForSort($items, $item->id),
            ];
            $result[] = $node;
        }
    }
    return $result;
}

$allItems = MenuItem::find()
    ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
    ->all();

$tree = buildTreeForSort($allItems);
$updateOrderUrl = Url::to(['update-order']);

function renderSortItem($node, $depth = 0)
{
    $icon = !empty($node['icon']) ? '<i data-lucide="' . $node['icon'] . '" style="width:18px;height:18px;" class="me-2"></i>' : '';
    $html = '<div class="sort-item" data-id="' . $node['id'] . '" style="padding-left: ' . (20 + $depth * 20) . 'px;">';
    $html .= '<span class="drag-handle"><i class="fas fa-grip-vertical text-muted me-2"></i></span>';
    $html .= $icon . Html::encode($node['label']);

    if (!empty($node['children'])) {
        $html .= '<div class="children-container" data-parent="' . $node['id'] . '">';
        foreach ($node['children'] as $child) {
            $html .= renderSortItem($child, $depth + 1);
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
?>

<div class="menu-item-sort">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Drag and drop items to reorder (nested supported)</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Drag items to change order or nest them under other items. Drop an item onto another
                to make it a child.</p>
            <div id="sortable-root" class="list-group">
                <?php if (empty($tree)): ?>
                    <div class="alert alert-info">No menu items found.</div>
                <?php else: ?>
                    <?php foreach ($tree as $rootNode): ?>
                        <?= renderSortItem($rootNode, 0) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer">
            <a href="<?= Url::to(['index']) ?>" class="btn btn-outline-secondary rounded-3">
                <i class="fas fa-angle-left me-1"></i> Back to list
            </a>
        </div>
    </div>
</div>

<?php
$css = <<<CSS
    .sort-item {
        cursor: grab;
        user-select: none;
        border: 1px solid #e9ecef;
        margin-bottom: 2px;
        border-radius: 4px;
        transition: background 0.2s;
        padding: 6px 10px;
        background: #fff;
    }
    .sort-item:hover {
        background: #f8f9fa;
    }
    .sort-item:active {
        cursor: grabbing;
    }
    .drag-handle {
        cursor: grab;
    }
    .children-container {
        margin-left: 20px;
        border-left: 2px dashed #dee2e6;
        padding-left: 10px;
        margin-top: 4px;
    }
    .children-container .sort-item {
        margin-left: 0;
    }
    .placeholder {
        background: #e9ecef;
        border: 2px dashed #6c757d;
        height: 40px;
        margin: 4px 0;
        border-radius: 4px;
    }
    .loading-overlay {
        position: relative;
    }
    .loading-overlay::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.6);
        z-index: 1000;
        pointer-events: none;
    }
CSS;

$js = <<<JS
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    function getMenuStructure() {
        let structure = [];
        let order = 0;

        function processItem(\$item, parentId = null) {
            const id = parseInt(\$item.data('id'));
            order++;
            structure.push({
                id: id,
                parent_id: parentId,
                sort_order: order
            });

            const \$childrenContainer = \$item.children('.children-container');
            if (\$childrenContainer.length) {
                \$childrenContainer.children('.sort-item').each(function() {
                    processItem(\$(this), id);
                });
            }
        }

        $('#sortable-root').children('.sort-item').each(function() {
            processItem(\$(this), null);
        });

        return structure;
    }

    $('#sortable-root, .children-container').sortable({
        cursor: 'move',
        placeholder: 'placeholder',
        connectWith: '.children-container, #sortable-root',
        tolerance: 'pointer',
        handle: '.drag-handle',
        items: '> .sort-item',
        update: function(event, ui) {
            if (this === ui.item.parent()[0]) {
                const structure = getMenuStructure();
                $('.card-body').addClass('loading-overlay');

                $.ajax({
                    url: '$updateOrderUrl',
                    type: 'POST',
                    data: {
                        items: structure,
                        _csrf: yii.getCsrfToken()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to update order',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'An error occurred while updating the order.',
                            confirmButtonColor: '#d33'
                        });
                    },
                    complete: function() {
                        $('.card-body').removeClass('loading-overlay');
                    }
                });
            }
        }
    }).disableSelection();
JS;

$this->registerCss($css);
$this->registerJs($js);
?>