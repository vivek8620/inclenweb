<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Academic&nbsp;Collaborator',
        'Academic&nbsp;Collaborator',
        'manage_options',
        'academic-collaborators',
        'academic_collaborators_page'
    );
});

add_action('rest_api_init', function () {

    register_rest_route('academic/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_academic_collaborators',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('academic/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_academic_collaborator',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('academic/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_academic_collaborator',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('academic/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_academic_collaborator',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('academic/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_team_image_to_r2_acad',
        'permission_callback' => '__return_true',
    ]);
});


function academic_collaborators_page() {
    $nonce = wp_create_nonce('academic_collaborators_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Academic Collaborators</h1>
        <hr class="wp-header-end">

        <div id="pub-notice" style="display:none; margin:10px 0;"></div>

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Academic Collaborator</h2>
            
            <form id="collaborator-form" novalidate>
                <input type="hidden" id="collaborator_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="name">Name <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="name" class="regular-text" style="width:100%;" placeholder="e.g. Dr. Priya Sharma" required>
                            <p class="error-msg" id="err_name" style="color:red;display:none;">Name is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="designation">Designation <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="designation" class="regular-text" style="width:100%;" placeholder="e.g. Professor of Biotechnology" required>
                            <p class="error-msg" id="err_designation" style="color:red;display:none;">Designation is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Type <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="type" class="regular-text" style="width:100%;" required>
                                <option value="">Select Type</option>
                                <option value="national">National</option>
                                <option value="international">International</option>
                            </select>
                            <p class="error-msg" id="err_type" style="color:red;display:none;">Please select National or International.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="location">Institution / Location <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="location" class="regular-text" style="width:100%;" placeholder="e.g. AIIMS Delhi or University of Oxford, UK" required>
                            <p class="error-msg" id="err_location" style="color:red;display:none;">Institution / Location is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Profile Image</label></th>
                        <td>
                            <input type="file" id="image-upload" accept="image/jpeg,image/png,image/webp,image/gif">
                            <p class="description">JPG, PNG, WEBP, GIF (Max 5MB)</p>
                            <input type="hidden" id="image_url" value="">
                            <div id="image-preview" style="margin-top:10px;"></div>
                            <div id="upload-progress" style="display:none; margin-top:8px;">
                                <span class="spinner is-active" style="float:none;"></span> Uploading...
                            </div>
                            <p class="error-msg" id="err_image" style="color:red;display:none;"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bio">Short Bio / Description</label></th>
                        <td>
                            <textarea id="bio" class="large-text" rows="5" style="width:100%;" placeholder="Brief description or key achievements..."></textarea>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:30px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Collaborator</button>
                    <button type="button" id="cancel-btn" class="button button-large" style="display:none; margin-left:10px;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <h2>All Academic Collaborators</h2>
        <table id="collaborators-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:100px;">Image</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Type</th>
                    <th>Institution / Location</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" style="text-align:center;padding:60px;">
                    <span class="spinner is-active" style="float:none;"></span> Loading...
                </td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;
        const MAX_SIZE = 5 * 1024 * 1024;
        const ALLOWED_TYPES = ['image/jpeg','image/png','image/webp','image/gif'];

        function showNotice(msg, type = 'success') {
            const cls = type === 'error' ? 'notice-error' : 'notice-success';
            $('#pub-notice').removeClass('notice-error notice-success').addClass('notice ' + cls).html('<p>' + msg + '</p>').show();
            setTimeout(() => $('#pub-notice').fadeOut(), 4000);
        }

        function clearErrors() {
            $('.error-msg').hide();
        }

        function validateForm() {
            clearErrors();
            let valid = true;
            if (!$('#name').val().trim()) { $('#err_name').show(); valid = false; }
            if (!$('#designation').val().trim()) { $('#err_designation').show(); valid = false; }
            if (!$('#type').val()) { $('#err_type').show(); valid = false; }
            if (!$('#location').val().trim()) { $('#err_location').show(); valid = false; }
            return valid;
        }

        // Load All Collaborators
        function loadCollaborators() {
            $.get('<?php echo rest_url('academic/v1/all'); ?>', function(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="7" style="text-align:center;padding:50px;">No academic collaborators found.</td></tr>';
                } else {
                    data.forEach(function(m) {
                        const img = m.image_url 
                            ? `<img src="${m.image_url}" style="max-width:90px;max-height:90px;object-fit:cover;border-radius:6px;" alt="">` 
                            : '<em style="color:#999;">—</em>';

                        html += `
                            <tr>
                                <td>${m.id}</td>
                                <td>${img}</td>
                                <td><strong>${m.name}</strong></td>
                                <td>${m.designation}</td>
                                <td><strong>${m.type === 'national' ? 'National' : 'International'}</strong></td>
                                <td>${m.location}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${m.id}">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${m.id}" style="color:#b32d2e;">Delete</button>
                                </td>
                            </tr>`;
                    });
                }
                $('#collaborators-table tbody').html(html);
            }).fail(function() {
                $('#collaborators-table tbody').html('<tr><td colspan="7" style="color:red;text-align:center;">Failed to load data.</td></tr>');
            });
        }

        // Image Upload
        $('#image-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            if (!ALLOWED_TYPES.includes(file.type)) {
                $('#err_image').text('Only JPG, PNG, WEBP, GIF allowed.').show();
                return;
            }
            if (file.size > MAX_SIZE) {
                $('#err_image').text('Image must not exceed 5MB.').show();
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $('#upload-progress').show();
            $('#image-preview').html('');

            $.ajax({
                url: '<?php echo rest_url('academic/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#upload-progress').hide();
                    if (res && res.url) {
                        $('#image_url').val(res.url);
                        $('#image-preview').html(`<img src="${res.url}" style="max-height:200px;border-radius:8px;border:1px solid #ddd;">`);
                    }
                },
                error: function() {
                    $('#upload-progress').hide();
                    $('#err_image').text('Image upload failed.').show();
                }
            });
        });

        // Save / Update
        $('#save-btn').on('click', function() {
            if (!validateForm()) return;

            const btn = $(this);
            btn.prop('disabled', true).text(editId ? 'Updating...' : 'Adding...');

            const data = {
                name: $('#name').val().trim(),
                designation: $('#designation').val().trim(),
                type: $('#type').val(),
                location: $('#location').val().trim(),
                image_url: $('#image_url').val(),
                bio: $('#bio').val().trim(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('academic/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('academic/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    if (res && res.status) {
                        showNotice(editId ? 'Collaborator updated successfully!' : 'Collaborator added successfully!');
                        resetForm();
                        loadCollaborators();
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to save.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showNotice(msg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text(editId ? 'Update Collaborator' : 'Add Collaborator');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Academic Collaborator');
            $('#save-btn').text('Update Collaborator');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('academic/v1/all'); ?>', function(data) {
                const item = data.find(m => m.id == editId);
                if (!item) return;

                $('#name').val(item.name || '');
                $('#designation').val(item.designation || '');
                $('#type').val(item.type || '');
                $('#location').val(item.location || '');
                $('#image_url').val(item.image_url || '');
                $('#bio').val(item.bio || '');

                if (item.image_url) {
                    $('#image-preview').html(`<img src="${item.image_url}" style="max-height:200px;border-radius:8px;border:1px solid #ddd;">`);
                }
            });
        });

        // Cancel Edit
        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#collaborator-form')[0].reset();
            $('#image_url').val('');
            $('#image-preview').html('');
            $('#upload-progress').hide();
            $('#form-heading').text('Add New Academic Collaborator');
            $('#save-btn').text('Add Collaborator');
            $('#cancel-btn').hide();
            clearErrors();
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this academic collaborator?')) return;
            const id = $(this).data('id');

            $.ajax({
                url: '<?php echo rest_url('academic/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    showNotice('Collaborator deleted successfully!');
                    loadCollaborators();
                },
                error: function() {
                    showNotice('Failed to delete.', 'error');
                }
            });
        });

        // Initial Load
        loadCollaborators();
    });
    </script>
    <?php
}


function get_all_academic_collaborators() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_collaborators';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    return $results ?: [];
}

function add_academic_collaborator($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_collaborators';
    $params = $request->get_json_params();

    if (empty($params['name']) || empty($params['designation']) || empty($params['type']) || empty($params['location'])) {
        return new WP_Error('missing_fields', 'Name, Designation, Type and Location are required.', ['status' => 400]);
    }

    $inserted = $wpdb->insert($table, [
        'name'        => sanitize_text_field($params['name']),
        'designation' => sanitize_text_field($params['designation']),
        'type'        => sanitize_text_field($params['type']),
        'location'    => sanitize_text_field($params['location']),
        'image_url'   => esc_url_raw($params['image_url'] ?? ''),
        'bio'         => sanitize_textarea_field($params['bio'] ?? ''),
        'created_at'  => current_time('mysql'),
    ]);

    if ($inserted === false) {
        return new WP_Error('db_error', 'Database insert failed.', ['status' => 500]);
    }

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_academic_collaborator($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_collaborators';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    if (!$id) {
        return new WP_Error('invalid_id', 'Invalid ID.', ['status' => 400]);
    }

    $updated = $wpdb->update($table, [
        'name'        => sanitize_text_field($params['name']),
        'designation' => sanitize_text_field($params['designation']),
        'type'        => sanitize_text_field($params['type']),
        'location'    => sanitize_text_field($params['location']),
        'image_url'   => esc_url_raw($params['image_url'] ?? ''),
        'bio'         => sanitize_textarea_field($params['bio'] ?? ''),
    ], ['id' => $id]);

    return $updated !== false ? ['status' => 'updated'] : new WP_Error('db_error', 'Update failed.', ['status' => 500]);
}

function delete_academic_collaborator($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_collaborators';
    $id = absint($request['id']);

    if (!$id) {
        return new WP_Error('invalid_id', 'Invalid ID.', ['status' => 400]);
    }

    $deleted = $wpdb->delete($table, ['id' => $id]);
    return $deleted !== false ? ['status' => 'deleted'] : new WP_Error('db_error', 'Delete failed.', ['status' => 500]);
}

// Image Upload Function (to Cloudflare R2)
function upload_team_image_to_r2_acad($request) {
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
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
        ]);

        $client->putObject([
            'Bucket' => $bucket,
            'Key' => 'admin/team/images/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $fileType,
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', 'R2 upload error: ' . $e->getMessage(), ['status' => 500]);
    }
}