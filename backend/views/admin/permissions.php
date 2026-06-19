<?php
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\select2\Select2;

$this->title = 'Assign Routes to Role';

// Display sync notification
if (isset($syncAdded)) {
    $message = $syncAdded > 0
        ? "{$syncAdded} new route(s) were added as permissions."
        : "No new routes found. Permissions are up to date.";
    $icon = $syncAdded > 0 ? 'success' : 'info';
    $this->registerJs("
        $(document).ready(function() {
            Swal.fire({
                icon: '$icon',
                title: 'Routes Synced',
                text: '$message',
                timer: 3000,
                showConfirmButton: true
            });
        });
    ");
}

// Get role from URL query parameter
$selectedRoleParam = Yii::$app->request->get('role');
$selectedRole = null;
if ($selectedRoleParam && isset($roles[$selectedRoleParam])) {
    $selectedRole = $selectedRoleParam;
}


// Register required assets
$this->registerJsFile('https://code.jquery.com/jquery-3.6.0.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
?>

<div class="assign-routes-to-role">
    <div class="card rounded-4">
        <div class="drag-drop-container card-body" style="padding: 20px;">
            <div class="row">
                <div class="div col-md-8">
                    <h3 style="color: #198754;"><i class="fas fa-link"></i> Assign URL Routes to Role</h3>
                    <p class="text-muted">Select a role, then drag routes between lists.</p>
                    <hr>
                </div>
                <div class="mb-3 col-md-4 float-right">
                    <a href="<?= Url::to(['rescan-routes']) ?>" class="btn btn-warning rounded-3">
                        <i class="fas fa-sync"></i> Rescan Routes
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Role</label>
                        <?= Select2::widget([
                            'name' => 'role',
                            'value' => $selectedRole,   // <-- pre-select if provided
                            'data' => array_combine(array_keys($roles), array_map(function ($r) {
                                                        return $r->name . ' - ' . $r->description;
                                                    }, $roles)),
                            'options' => ['placeholder' => 'Choose a role ...', 'id' => 'role-select'],
                            'pluginOptions' => ['allowClear' => true, 'theme' => 'krajee', 'width' => '100%'],
                            'pluginEvents' => ['change' => 'function() { loadRoutesForRole($(this).val()); }']
                        ]) ?>
                    </div>
                </div>
            </div>

            <div id="routes-container" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card rounded-3">
                            <div class="card-header">Available Routes <input type="search" id="search-available"
                                    class="form-control input-sm pull-right" placeholder="Filter..."
                                    style="width: 200px;">
                            </div>
                            <div id="available-routes-list" class="routes-list"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card rounded-3">
                            <div class="card-header">Assigned Routes <input type="search" id="search-assigned"
                                    class="form-control input-sm pull-right" placeholder="Filter..."
                                    style="width: 200px;">
                            </div>
                            <div id="assigned-routes-list" class="routes-list"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center" style="margin-top: 20px;">
                    <button id="save-assignments" class="btn btn-outline-primary rounded-3" disabled><i
                            class="fas fa-save"></i> Save
                        Assignments</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    let currentRole = null;
    let allRoutes = {};
    let availableRoutes = [];
    let assignedRoutes = [];

    function loadRoutesForRole(roleName) {
        console.log('Loading routes for role:', roleName);

        if (!roleName) {
            $('#routes-container').hide();
            return;
        }
        currentRole = roleName;
        $('#save-assignments').prop('disabled', true);
        Swal.fire({
            title: 'Loading...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        $.ajax({
            url: '<?= Url::to(['get-role-routes']) ?>',
            type: 'GET',
            data: { role: roleName },
            dataType: 'json',
            success: function (res) {
                Swal.close();
                if (res.success) {
                    allRoutes = res.allRoutes;
                    availableRoutes = res.availableRoutes;
                    assignedRoutes = res.assignedRoutes;
                    renderLists();
                    $('#routes-container').show();
                    $('#save-assignments').prop('disabled', false);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: () => {
                Swal.close();
                Swal.fire('Error', 'Could not load routes', 'error');
            }
        });
    }

    function renderLists() {
        renderList('available-routes-list', availableRoutes, 'available');
        renderList('assigned-routes-list', assignedRoutes, 'assigned');
        attachDragEvents();
        attachSearch();
    }

    function renderList(containerId, routes, type) {
        const $container = $('#' + containerId);
        $container.empty();
        if (routes.length === 0) {
            $container.html('<div class="text-muted p-3">No routes found.</div>');
            return;
        }
        for (let route of routes) {
            const data = allRoutes[route] || {};
            const $item = $(`
                <div class="route-item" data-route="${route}" draggable="true">
                    <div><code>${escapeHtml(route)}</code></div>
                    <div class="route-desc">${escapeHtml(data.description || '')}</div>
                     <div class="route-methods">
                        ${(data.http_methods || []).map(m => `<span class="badge-method">${escapeHtml(m)}</span>`).join('')}
                    </div>
                </div>
            `);
            $container.append($item);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function attachDragEvents() {
        $('.route-item').off('dragstart').on('dragstart', function (e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('route'));
            $(this).addClass('dragging');
        }).on('dragend', function () {
            $(this).removeClass('dragging');
        });

        $('#available-routes-list, #assigned-routes-list').off('dragover').on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        }).off('dragleave').on('dragleave', function () {
            $(this).removeClass('drag-over');
        }).off('drop').on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            const route = e.originalEvent.dataTransfer.getData('text/plain');
            const targetId = $(this).attr('id');
            moveRoute(route, targetId);
        });
    }

    function moveRoute(route, targetId) {
        const toAvailable = (targetId === 'available-routes-list');
        if (toAvailable && !assignedRoutes.includes(route)) return;
        if (!toAvailable && !availableRoutes.includes(route)) return;
        if (toAvailable) {
            assignedRoutes = assignedRoutes.filter(r => r !== route);
            availableRoutes.push(route);
        } else {
            availableRoutes = availableRoutes.filter(r => r !== route);
            assignedRoutes.push(route);
        }
        availableRoutes.sort();
        assignedRoutes.sort();
        renderLists();
    }

    function attachSearch() {
        $('#search-available').off('keyup').on('keyup', function () {
            const term = $(this).val().toLowerCase();
            $('#available-routes-list .route-item').each(function () {
                const text = $(this).find('code').text().toLowerCase();
                $(this).toggle(text.indexOf(term) > -1);
            });
        });
        $('#search-assigned').off('keyup').on('keyup', function () {
            const term = $(this).val().toLowerCase();
            $('#assigned-routes-list .route-item').each(function () {
                const text = $(this).find('code').text().toLowerCase();
                $(this).toggle(text.indexOf(term) > -1);
            });
        });
    }

    $('#save-assignments').click(function () {
        if (!currentRole) return;
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        $.ajax({
            url: '<?= Url::to(['save-role-routes']) ?>',
            type: 'POST',
            data: { role: currentRole, routes: assignedRoutes },
            dataType: 'json',
            success: function (res) {
                Swal.close();
                if (res.success) {
                    Swal.fire('Success', res.message, 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: () => {
                Swal.close();
                Swal.fire('Error', 'Failed to save', 'error');
            }
        });
    });

    // When the page is ready, if a role is preselected, set the value and trigger load
    $(document).ready(function () {
        const preselectedRole = '<?= $selectedRole ?>';
        console.log('Preselected role:', preselectedRole);
        if (preselectedRole) {
            // Set the Select2 value visually
            $('#role-select').val(preselectedRole);
            // Directly call the load function
            loadRoutesForRole(preselectedRole);
        }
    });
</script>

<style>
    .card {
        background: white;
        /* border-radius: 8px; */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .card-header {
        background: #f5f5f5;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }

    .routes-list {
        min-height: 400px;
        max-height: 500px;
        overflow-y: auto;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 0 0 8px 8px;
    }

    .route-item {
        padding: 8px;
        margin: 5px 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        cursor: move;
    }

    .route-item.dragging {
        opacity: 0.5;
    }

    .drag-over {
        background: #e3f2fd !important;
        border: 2px dashed #2196f3 !important;
    }

    .route-desc {
        font-size: 11px;
        color: #666;
    }

    .badge-method {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        color: white;
        background: #28a745;
        margin-right: 3px;
    }

    .pull-right {
        float: right;
    }

    .input-sm {
        padding: 4px 8px;
        font-size: 12px;
    }
</style>