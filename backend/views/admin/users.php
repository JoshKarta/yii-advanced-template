<?php
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\select2\Select2;

$this->title = 'Manage Users';
?>

<div class="users-index">
    <div class="card rounded-4">
        <div class="card-body">
            <h3><i class="fas fa-users"></i> Users</h3>
            <p class="text-muted">Create, edit, or delete users.</p>
            <hr>

            <div class="mb-3">
                <button class="btn btn-success" id="create-user-btn"><i class="fas fa-plus"></i> Create User</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-list">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="user-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id" value="">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="user-username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="user-email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <input type="password" id="user-password" class="form-control" placeholder="Password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="generate-password-btn"><i
                                        class="fas fa-dice"></i> Generate</button>
                                <button class="btn btn-outline-secondary" type="button" id="copy-password-btn"><i
                                        class="fas fa-copy"></i> Copy</button>
                            </div>
                        </div> <small class="text-muted">Leave blank to keep current password (when editing).</small>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <?= Select2::widget([
                            'name' => 'status',
                            'id' => 'user-status',
                            'value' => \common\models\User::STATUS_ACTIVE,
                            'data' => [
                                \common\models\User::STATUS_ACTIVE => 'Active',
                                \common\models\User::STATUS_INACTIVE => 'Inactive',
                            ],
                            'options' => ['placeholder' => 'Select status'],
                            'pluginOptions' => ['allowClear' => false, 'dropdownParent' => '#user-modal'],
                        ]); ?>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <?= Select2::widget([
                            'name' => 'role',
                            'id' => 'user-role',
                            'data' => $roleList,
                            'options' => ['placeholder' => 'Select a role'],
                            'pluginOptions' => ['allowClear' => true, 'dropdownParent' => '#user-modal'],
                        ]); ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-user-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('https://code.jquery.com/jquery-3.6.0.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

$listUrl = Url::to(['ajax-list-users']);
$createUrl = Url::to(['ajax-create-user']);
$updateUrl = Url::to(['ajax-update-user']);
$deleteUrl = Url::to(['ajax-delete-user']);

// Use a nowdoc to avoid escaping issues
$js = <<<'JS'
    let modal = $('#user-modal');
    let form = $('#user-form');
    let saveBtn = $('#save-user-btn');
    let userIdField = $('#user-id');
    let usernameField = $('#user-username');
    let emailField = $('#user-email');
    let passwordField = $('#user-password');
    let statusField = $('#user-status');
    let roleField = $('#user-role');

    // Load users
    function loadUsers() {
        $.get('LIST_URL_PLACEHOLDER', function(res) {
            let tbody = $('#user-list');
            tbody.empty();
            if (res.data && res.data.length > 0) {
                $.each(res.data, function(i, user) {
                    let row = `<tr>
                        <td>${user.id}</td>
                        <td>${escapeHtml(user.username)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>${user.status}</td>
                        <td>${escapeHtml(user.role || '')}</td>
                        <td>${escapeHtml(user.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-user-btn" data-id="${user.id}" data-username="${escapeHtml(user.username)}" data-email="${escapeHtml(user.email)}" data-status="${user.status.replace(/<.*?>/g, '')}" data-role="${escapeHtml(user.role || '')}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger delete-user-btn" data-id="${user.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });
            } else {
                tbody.html('<tr><td colspan="7" class="text-center">No users found.</td></tr>');
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Create
    $('#create-user-btn').click(function() {
        form[0].reset();
        userIdField.val('');
        passwordField.prop('required', true);
        passwordField.parent().find('small').text('Password is required for new user.');
        modal.find('.modal-title').text('Create User');
        modal.modal('show');
    });

    // Edit
    $(document).on('click', '.edit-user-btn', function() {
        let id = $(this).data('id');
        let username = $(this).data('username');
        let email = $(this).data('email');
        let status = $(this).data('status');
        let role = $(this).data('role');

        userIdField.val(id);
        usernameField.val(username);
        emailField.val(email);
        passwordField.val('').prop('required', false);
        passwordField.parent().find('small').text('Leave blank to keep current password.');
        statusField.val(status).trigger('change');
        if (role) {
            roleField.val(role).trigger('change');
        } else {
            roleField.val('').trigger('change');
        }
        modal.find('.modal-title').text('Edit User');
        modal.modal('show');
    });

        // Generate password
    $('#generate-password-btn').click(function() {
        let password = generateRandomPassword(12);
        passwordField.val(password);
        // Optionally show password as text for a moment? We'll keep as password type.
    });

    // Copy password
    $('#copy-password-btn').click(function() {
        let password = passwordField.val();
        if (!password) {
            Swal.fire('Info', 'No password to copy.', 'info');
            return;
        }
        navigator.clipboard.writeText(password).then(function() {
            Swal.fire('Success', 'Password copied to clipboard.', 'success');
        }).catch(function() {
            // Fallback
            passwordField.select();
            document.execCommand('copy');
            Swal.fire('Success', 'Password copied to clipboard.', 'success');
        });
    });

    function generateRandomPassword(length) {
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        let password = '';
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    // Save
    saveBtn.click(function() {
        let id = userIdField.val();
        let username = usernameField.val().trim();
        let email = emailField.val().trim();
        let password = passwordField.val();
        let status = statusField.val();
        let role = roleField.val();

        if (!username || !email) {
            Swal.fire('Error', 'Username and email are required.', 'error');
            return;
        }
        if (id === '' && !password) {
            Swal.fire('Error', 'Password is required for new user.', 'error');
            return;
        }

        let url = id ? 'UPDATE_URL_PLACEHOLDER' : 'CREATE_URL_PLACEHOLDER';
        let data = {
            id: id,
            username: username,
            email: email,
            password: password,
            status: status,
            role: role
        };

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $.post(url, data, function(res) {
            saveBtn.prop('disabled', false).html('Save');
            if (res.success) {
                Swal.fire('Success', res.message, 'success').then(() => {
                    modal.modal('hide');
                    loadUsers();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }).fail(() => {
            saveBtn.prop('disabled', false).html('Save');
            Swal.fire('Error', 'Could not save user.', 'error');
        });
    });

    // Delete
    $(document).on('click', '.delete-user-btn', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete User?',
            text: 'This will soft-delete the user. They will no longer be able to log in.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('DELETE_URL_PLACEHOLDER', {id: id}, function(res) {
                    if (res.success) {
                        Swal.fire('Deleted', res.message, 'success').then(() => {
                            loadUsers();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });

    // Initial load
    loadUsers();
JS;

// Replace placeholders with actual URLs
$js = str_replace(
    ['LIST_URL_PLACEHOLDER', 'CREATE_URL_PLACEHOLDER', 'UPDATE_URL_PLACEHOLDER', 'DELETE_URL_PLACEHOLDER'],
    [$listUrl, $createUrl, $updateUrl, $deleteUrl],
    $js
);

$this->registerJs($js);
?>