<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Governance & Team Manager',
        'Governance & Team',
        'manage_options',
        'governance-team-manager',
        'governance_team_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('governance/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_team_members',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('governance/v1', '/add', [
        'methods'             => 'POST',
        'callback'            => 'add_team_member',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('governance/v1', '/update/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'update_team_member',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('governance/v1', '/delete/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_team_member',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('governance/v1', '/upload-image', [
        'methods'             => 'POST',
        'callback'            => 'upload_team_image_to_r2',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('governance/v1', '/reorder', [
        'methods'             => 'POST',
        'callback'            => 'reorder_team_members',
        'permission_callback' => '__return_true',
    ]);
});

function governance_team_manager_page() {
    wp_enqueue_script('jquery-ui-sortable');
    $nonce = wp_create_nonce('governance_team_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Governance & Team</h1>
        <hr class="wp-header-end">

        <!-- Tab Navigation (like your screenshot) -->
        <!--<div style="background:#fff; padding:10px 20px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:20px;">-->
        <!--    <nav class="nav-tab-wrapper">-->
        <!--        <a href="#" class="nav-tab nav-tab-active" data-group="board_of_trustees">BOARD OF TRUSTEES</a>-->
        <!--        <a href="#" class="nav-tab" data-group="leadership_management">LEADERSHIP & MANAGEMENT</a>-->
        <!--        <a href="#" class="nav-tab" data-group="research_implementation">RESEARCH & IMPLEMENTATION</a>-->
        <!--        <a href="#" class="nav-tab" data-group="ethics_committee">ETHICS COMMITTEE</a>-->
        <!--        <a href="#" class="nav-tab" data-group="scientific_advisory">SCIENTIFIC ADVISORY GROUP</a>-->
        <!--    </nav>-->
        <!--</div>-->

        <div id="pub-notice" style="display:none; margin:10px 0;"></div>

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Team Member</h2>
            <form id="team-form" novalidate>
                <input type="hidden" id="member_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="member_name">Name <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="member_name" class="regular-text" style="width:100%;" placeholder="e.g. Dr. Virander Singh Chauhan" required>
                            <p class="description error-msg" id="err_name" style="color:red;display:none;">Name is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="member_designation">Designation <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="member_designation" class="regular-text" style="width:100%;" placeholder="e.g. Chairperson / Emeritus Scientist" required>
                            <p class="description error-msg" id="err_designation" style="color:red;display:none;">Designation is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="member_qualification">Qualification</label></th>
                        <td>
                            <input type="text" id="member_qualification" class="regular-text" style="width:100%;" placeholder="e.g. PhD, MD, FRS, etc.">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="member_group">Group / Category <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="member_group" class="regular-text" style="width:100%;" required>
                                <option value="">Select Group</option>
                                <option value="board_of_trustees">Board of Trustees</option>
                                <option value="leadership_management">Leadership & Management</option>
                                <option value="research_implementation">Research & Implementation</option>
                                <option value="ethics_committee">Ethics Committee</option>
                                <option value="scientific_advisory">Scientific Advisory Group</option>
                            </select>
                            <p class="description error-msg" id="err_group" style="color:red;display:none;">Please select a group.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Profile Image</label></th>
                        <td>
                            <input type="file" id="team-image-upload" accept="image/jpeg,image/png,image/webp,image/gif">
                            <p class="description">Accepted formats: JPG, PNG, WEBP, GIF. Max size: <strong>5MB</strong>.</p>
                            <input type="hidden" id="team-image-url" value="">
                            <div id="team-image-preview" style="margin-top:10px;"></div>
                            <div id="team-upload-progress" style="display:none; margin-top:6px;">
                                <span class="spinner is-active" style="float:none;"></span>
                                <em>Uploading image...</em>
                            </div>
                            <p class="error-msg" id="err_image" style="color:red;display:none;"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="member_bio">Short Bio / Description</label></th>
                        <td>
                            <textarea id="member_bio" class="large-text" rows="5" style="width:100%;" placeholder="Brief description or key achievements..."></textarea>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:25px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Member</button>
                    <button type="button" id="cancel-btn" class="button button-large" style="display:none; margin-left:8px;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Team Members</h2>
            <div>
                <label for="filter-group"><strong>Filter by Group:</strong></label>
                <select id="filter-group">
                    <option value="all">All (Drag & Drop Disabled)</option>
                    <option value="board_of_trustees">Board of Trustees</option>
                    <option value="leadership_management">Leadership & Management</option>
                    <option value="research_implementation">Research & Implementation</option>
                    <option value="ethics_committee">Ethics Committee</option>
                    <option value="scientific_advisory">Scientific Advisory Group</option>
                </select>
            </div>
        </div>
        <table id="team-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:90px;">Image</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Qualification</th>
                    <th>Group</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" style="text-align:center;padding:40px;">
                    <span class="spinner is-active" style="float:none;"></span> Loading...
                </td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function ($) {
        let editId = 0;
        const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
        const ALLOWED_IMAGE_TYPES = ['image/jpeg','image/png','image/webp','image/gif'];

        function showNotice(msg, type) {
            const cls = type === 'error' ? 'notice-error' : 'notice-success';
            $('#pub-notice').removeClass('notice-error notice-success notice')
                .addClass('notice ' + cls).html('<p>' + msg + '</p>').show();
            setTimeout(() => $('#pub-notice').fadeOut(), 5000);
        }

        function showFieldError(id, msg) {
            $('#' + id).text(msg).show();
        }

        function clearErrors() {
            $('.error-msg').hide().text('');
        }

        function validateForm() {
            clearErrors();
            let valid = true;

            if (!$('#member_name').val().trim()) {
                showFieldError('err_name', 'Name is required.');
                valid = false;
            }
            if (!$('#member_designation').val().trim()) {
                showFieldError('err_designation', 'Designation is required.');
                valid = false;
            }
            if (!$('#member_group').val()) {
                showFieldError('err_group', 'Please select a group.');
                valid = false;
            }
            return valid;
        }

        let allMembers = [];

        function renderTable(groupFilter) {
            let html = '';
            let filtered = allMembers;
            
            if (groupFilter !== 'all') {
                filtered = allMembers.filter(m => m.group === groupFilter);
            }

            if (!filtered || filtered.length === 0) {
                html = '<tr><td colspan="7" style="text-align:center;padding:50px;">No team members found.</td></tr>';
            } else {
                filtered.forEach(function (m) {
                    const img = m.image_url 
                        ? `<img src="${m.image_url}" style="max-width:80px;max-height:80px;object-fit:cover;border-radius:6px;" alt="">`
                        : '<em style="color:#999;">—</em>';

                    const groupName = {
                        'board_of_trustees': 'Board of Trustees',
                        'leadership_management': 'Leadership & Management',
                        'research_implementation': 'Research & Implementation',
                        'ethics_committee': 'Ethics Committee',
                        'scientific_advisory': 'Scientific Advisory Group'
                    }[m.group] || m.group;

                    const dragHandle = groupFilter !== 'all' 
                        ? `<span class="drag-handle" style="cursor:move; font-size:20px; color:#999; margin-right:10px;">☰</span>`
                        : '';

                    html += `
                        <tr data-id="${m.id}" class="team-row">
                            <td>${dragHandle}${m.id}</td>
                            <td>${img}</td>
                            <td><strong>${m.name}</strong></td>
                            <td>${m.designation}</td>
                            <td>${m.qualification || '—'}</td>
                            <td>${groupName}</td>
                            <td>
                                <button class="button button-small edit-btn" data-id="${m.id}">Edit</button>
                                <button class="button button-small delete-btn" data-id="${m.id}" style="color:#b32d2e;">Delete</button>
                            </td>
                        </tr>`;
                });
            }
            $('#team-table tbody').html(html);

            if (groupFilter !== 'all') {
                $('#team-table tbody').sortable({
                    handle: '.drag-handle',
                    update: function(event, ui) {
                        let orderData = [];
                        $('#team-table tbody tr.team-row').each(function(index) {
                            orderData.push({
                                id: $(this).data('id'),
                                display_order: index + 1
                            });
                        });
                        
                        $.ajax({
                            url: '<?php echo rest_url('governance/v1/reorder'); ?>',
                            method: 'POST',
                            data: JSON.stringify({ orders: orderData }),
                            contentType: 'application/json',
                            success: function (res) {
                                showNotice('Order updated successfully.', 'success');
                                orderData.forEach(od => {
                                    let m = allMembers.find(x => x.id == od.id);
                                    if(m) m.display_order = od.display_order;
                                });
                                allMembers.sort((a, b) => {
                                    if (a.display_order === b.display_order) return a.id - b.id;
                                    return a.display_order - b.display_order;
                                });
                            },
                            error: function (xhr) {
                                showNotice('Failed to update order.', 'error');
                            }
                        });
                    }
                });
            } else {
                if ($('#team-table tbody').hasClass('ui-sortable')) {
                    $('#team-table tbody').sortable('destroy');
                }
            }
        }

        $('#filter-group').on('change', function() {
            renderTable($(this).val());
        });

        function loadTeamMembers() {
            $.get('<?php echo rest_url('governance/v1/all'); ?>', function (data) {
                allMembers = data || [];
                renderTable($('#filter-group').val() || 'all');
            }).fail(function () {
                $('#team-table tbody').html('<tr><td colspan="7" style="color:red;text-align:center;padding:30px;">Failed to load data.</td></tr>');
            });
        }

        // Image Upload
        $('#team-image-upload').on('change', function () {
            $('#err_image').hide();
            const file = this.files[0];
            if (!file) return;

            if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
                showFieldError('err_image', 'Only JPG, PNG, WEBP, GIF allowed.');
                $(this).val('');
                return;
            }
            if (file.size > MAX_IMAGE_SIZE) {
                showFieldError('err_image', 'Image must not exceed 5MB.');
                $(this).val('');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $('#team-upload-progress').show();
            $('#team-image-preview').html('');

            $.ajax({
                url: '<?php echo rest_url('governance/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#team-upload-progress').hide();
                    if (res && res.url) {
                        $('#team-image-url').val(res.url);
                        $('#team-image-preview').html(`<img src="${res.url}" style="max-height:180px;border-radius:8px;border:1px solid #ddd;">`);
                    } else {
                        showFieldError('err_image', 'Upload failed.');
                    }
                },
                error: function (xhr) {
                    $('#team-upload-progress').hide();
                    let msg = 'Image upload failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showFieldError('err_image', msg);
                }
            });
        });

        // Save
        $('#save-btn').on('click', function () {
            if (!validateForm()) return;

            const btn = $(this);
            btn.prop('disabled', true).text(editId ? 'Updating...' : 'Saving...');

            const data = {
                name: $('#member_name').val().trim(),
                designation: $('#member_designation').val().trim(),
                qualification: $('#member_qualification').val().trim(),
                group: $('#member_group').val(),
                image_url: $('#team-image-url').val(),
                bio: $('#member_bio').val().trim(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('governance/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('governance/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function (res) {
                    if (res && res.status) {
                        showNotice(editId ? 'Member updated successfully!' : 'Member added successfully!', 'success');
                        resetForm();
                        loadTeamMembers();
                    }
                },
                error: function (xhr) {
                    let msg = 'Failed to save.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showNotice(msg, 'error');
                },
                complete: function () {
                    btn.prop('disabled', false).text(editId ? 'Update Member' : 'Add Member');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-btn', function () {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Team Member');
            $('#save-btn').text('Update Member');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('governance/v1/all'); ?>', function (members) {
                const item = members.find(m => m.id == editId);
                if (!item) return;

                $('#member_name').val(item.name || '');
                $('#member_designation').val(item.designation || '');
                $('#member_qualification').val(item.qualification || '');
                $('#member_group').val(item.group || '');
                $('#team-image-url').val(item.image_url || '');
                $('#member_bio').val(item.bio || '');

                $('#team-image-preview').html(
                    item.image_url 
                    ? `<img src="${item.image_url}" style="max-height:180px;border-radius:8px;border:1px solid #ddd;">`
                    : ''
                );

                $('html, body').animate({ scrollTop: $('#team-form').offset().top - 50 }, 300);
            });
        });

        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            clearErrors();
            $('#team-form')[0].reset();
            $('#team-image-url').val('');
            $('#team-image-preview').html('');
            $('#team-upload-progress').hide();
            $('#form-heading').text('Add New Team Member');
            $('#save-btn').text('Add Member');
            $('#cancel-btn').hide();
        }

        // Delete
        $(document).on('click', '.delete-btn', function () {
            if (!confirm('Delete this team member?')) return;
            const id = $(this).data('id');

            $.ajax({
                url: '<?php echo rest_url('governance/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function () {
                    showNotice('Member deleted successfully.', 'success');
                    loadTeamMembers();
                },
                error: function () {
                    showNotice('Failed to delete member.', 'error');
                }
            });
        });

        loadTeamMembers();
    });
    </script>
    <?php
}

function get_all_team_members() {
    global $wpdb;
    $table = $wpdb->prefix . 'team_members';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY display_order ASC, id ASC");
    return $results ?: [];
}

function add_team_member($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'team_members';
    $params = $request->get_json_params();

    if (empty($params['name']) || empty($params['designation']) || empty($params['group'])) {
        return new WP_Error('missing_fields', 'Name, Designation and Group are required.', ['status' => 400]);
    }

    $max_order = $wpdb->get_var($wpdb->prepare("SELECT MAX(display_order) FROM $table WHERE `group` = %s", $params['group']));
    $display_order = $max_order !== null ? (int)$max_order + 1 : 1;

    $inserted = $wpdb->insert($table, [
        'name'         => sanitize_text_field($params['name']),
        'designation'  => sanitize_text_field($params['designation']),
        'qualification'=> sanitize_text_field($params['qualification'] ?? ''),
        'group'        => sanitize_text_field($params['group']),
        'image_url'    => esc_url_raw($params['image_url'] ?? ''),
        'bio'          => sanitize_textarea_field($params['bio'] ?? ''),
        'display_order'=> $display_order,
        'created_at'   => current_time('mysql'),
    ]);

    if ($inserted === false) {
        return new WP_Error('db_error', 'Database insert failed.', ['status' => 500]);
    }

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_team_member($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'team_members';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    if (!$id || empty($params['name']) || empty($params['designation']) || empty($params['group'])) {
        return new WP_Error('missing_fields', 'Required fields missing.', ['status' => 400]);
    }

    $current_member = $wpdb->get_row($wpdb->prepare("SELECT `group` FROM $table WHERE id = %d", $id));
    $new_group = sanitize_text_field($params['group']);

    $update_data = [
        'name'         => sanitize_text_field($params['name']),
        'designation'  => sanitize_text_field($params['designation']),
        'qualification'=> sanitize_text_field($params['qualification'] ?? ''),
        'group'        => $new_group,
        'image_url'    => esc_url_raw($params['image_url'] ?? ''),
        'bio'          => sanitize_textarea_field($params['bio'] ?? ''),
    ];

    if ($current_member && $current_member->group !== $new_group) {
        $max_order = $wpdb->get_var($wpdb->prepare("SELECT MAX(display_order) FROM $table WHERE `group` = %s", $new_group));
        $update_data['display_order'] = $max_order !== null ? (int)$max_order + 1 : 1;
    }

    $updated = $wpdb->update($table, $update_data, ['id' => $id]);

    if ($updated !== false && $current_member && $current_member->group !== $new_group) {
        $old_group_members = $wpdb->get_results($wpdb->prepare("SELECT id FROM $table WHERE `group` = %s ORDER BY display_order ASC, id ASC", $current_member->group));
        $seq = 1;
        foreach ($old_group_members as $m) {
            $wpdb->update($table, ['display_order' => $seq], ['id' => $m->id]);
            $seq++;
        }
    }

    return $updated !== false ? ['status' => 'updated'] : new WP_Error('db_error', 'Update failed.', ['status' => 500]);
}

function delete_team_member($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'team_members';
    $id = absint($request['id']);

    if (!$id) return new WP_Error('invalid_id', 'Invalid ID.', ['status' => 400]);

    $current_member = $wpdb->get_row($wpdb->prepare("SELECT `group` FROM $table WHERE id = %d", $id));

    $deleted = $wpdb->delete($table, ['id' => $id]);
    
    if ($deleted !== false && $current_member) {
        $old_group_members = $wpdb->get_results($wpdb->prepare("SELECT id FROM $table WHERE `group` = %s ORDER BY display_order ASC, id ASC", $current_member->group));
        $seq = 1;
        foreach ($old_group_members as $m) {
            $wpdb->update($table, ['display_order' => $seq], ['id' => $m->id]);
            $seq++;
        }
    }

    return $deleted !== false ? ['status' => 'deleted'] : new WP_Error('db_error', 'Delete failed.', ['status' => 500]);
}

function reorder_team_members($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'team_members';
    $params = $request->get_json_params();

    if (empty($params['orders']) || !is_array($params['orders'])) {
        return new WP_Error('invalid_data', 'Invalid order data.', ['status' => 400]);
    }

    foreach ($params['orders'] as $order) {
        if (!empty($order['id']) && isset($order['display_order'])) {
            $wpdb->update($table, ['display_order' => absint($order['display_order'])], ['id' => absint($order['id'])]);
        }
    }

    return ['status' => 'reordered'];
}

function upload_team_image_to_r2($request) {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded.', ['status' => 400]);
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($fileType, $allowedTypes)) {
        return new WP_Error('invalid_type', 'Only JPG, PNG, WEBP, GIF allowed.', ['status' => 415]);
    }
    if ($fileSize > 5 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'Image must not exceed 5MB.', ['status' => 413]);
    }

    $imageInfo = @getimagesize($fileTmp);
    if ($imageInfo === false) {
        return new WP_Error('invalid_image', 'Not a valid image.', ['status' => 422]);
    }

    $fileName = time() . '-' . sanitize_file_name($file['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/team/images';
    try {
        $client = new \Aws\S3\S3Client([
            'version'   => 'latest',
            'region'    => 'auto',
            'endpoint'  => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/team/images/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $fileType,
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', 'R2 upload error: ' . $e->getMessage(), ['status' => 500]);
    }
}