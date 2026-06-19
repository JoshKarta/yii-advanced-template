<?php
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\select2\Select2;
use yii\widgets\Pjax;

$this->title = 'Manage Roles';
?>

<div class="roles-index">
    <div class="card rounded-4">
        <div class="card-body">
            <h3><i class="fas fa-user-tag"></i> Roles</h3>
            <p class="text-muted">Create, edit, or delete roles.</p>
            <hr>

            <div class="mb-3">
                <button class="btn btn-success" id="create-role-btn"><i class="fas fa-plus"></i> Create Role</button>
            </div>

            <?php Pjax::begin(['id' => 'roles-pjax', 'timeout' => 5000]); ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr data-name="<?= Html::encode($role->name) ?>">
                            <td><code><?= Html::encode($role->name) ?></code></td>
                            <td><?= Html::encode($role->description) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-role-btn"
                                    data-name="<?= Html::encode($role->name) ?>"
                                    data-description="<?= Html::encode($role->description) ?>"><i
                                        class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger delete-role-btn"
                                    data-name="<?= Html::encode($role->name) ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="role-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Role</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="role-form">
                    <input type="hidden" id="role-old-name" value="">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" id="role-name" class="form-control" placeholder="e.g., editor" required>
                        <small class="text-muted">Only lowercase letters and underscores.</small>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" id="role-description" class="form-control" placeholder="Description">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-role-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('https://code.jquery.com/jquery-3.6.0.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

$createUrl = Url::to(['ajax-create-role']);
$updateUrl = Url::to(['ajax-update-role']);
$deleteUrl = Url::to(['ajax-delete-role']);
$js = <<<JS
    let modal = $('#role-modal');
    let form = $('#role-form');
    let saveBtn = $('#save-role-btn');
    let oldNameField = $('#role-old-name');
    let nameField = $('#role-name');
    let descField = $('#role-description');

    // Create
    $('#create-role-btn').click(function() {
        form[0].reset();
        oldNameField.val('');
        modal.find('.modal-title').text('Create Role');
        modal.modal('show');
    });

    // Edit
    $(document).on('click', '.edit-role-btn', function() {
        let name = $(this).data('name');
        let desc = $(this).data('description');
        oldNameField.val(name);
        nameField.val(name);
        descField.val(desc);
        modal.find('.modal-title').text('Edit Role');
        modal.modal('show');
    });

    // Save (Create or Update)
    saveBtn.click(function() {
        let oldName = oldNameField.val();
        let name = nameField.val().trim();
        let description = descField.val().trim();

        if (!name) {
            Swal.fire('Error', 'Role name is required.', 'error');
            return;
        }

        let url = oldName ? '$updateUrl' : '$createUrl';
        let data = {
            name: name,
            description: description,
            oldName: oldName
        };

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $.post(url, data, function(res) {
            saveBtn.prop('disabled', false).html('Save');
            if (res.success) {
                Swal.fire('Success', res.message, 'success').then(() => {
                    modal.modal('hide');
                    $.pjax.reload({container: '#roles-pjax'});
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(() => {
            saveBtn.prop('disabled', false).html('Save');
            Swal.fire('Error', 'Could not save role.', 'error');
        });
    });

    // Delete
    $(document).on('click', '.delete-role-btn', function() {
        let name = $(this).data('name');
        Swal.fire({
            title: 'Delete Role?',
            text: 'This will remove the role and all its assignments.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('$deleteUrl', {name: name}, function(res) {
                    if (res.success) {
                        Swal.fire('Deleted', res.message, 'success').then(() => {
                            $.pjax.reload({container: '#roles-pjax'});
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
JS;
$this->registerJs($js);
?>