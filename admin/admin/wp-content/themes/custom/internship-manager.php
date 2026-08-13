<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-careers',
        'Internship Manager',
        'Internships',
        'manage_options',
        'internship-manager',
        'internship_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('internship/v1', '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_internships',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('internship/v1', '/add', [
        'methods' => 'POST',
        'callback' => 'add_internship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('internship/v1', '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_internship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('internship/v1', '/delete/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_internship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('internship/v1', '/upload-image', [
        'methods' => 'POST',
        'callback' => 'upload_image_to_r2_internship',
        'permission_callback' => '__return_true'
    ]);
});

// ======================
// INTERNSHIP MANAGER PAGE (Clean UI)
// ======================
function internship_manager_page() {
    $nonce = wp_create_nonce('internship_manager_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Internship Manager</h1>
        <hr class="wp-header-end">

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Internship Entry</h2>
           
            <form id="internship-form">
                <input type="hidden" id="internship_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="title">Title <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="title" class="regular-text" style="width:100%;" placeholder="e.g. Mid-Career Internship" required></td>
                    </tr>
                    <tr>
                        <th><label for="title">Short description<span style="color:red;">*</span></label></th>
                        <td><input type="text" id="short_description" class="regular-text" style="width:100%;" placeholder="Short description here ..." required></td>
                    </tr>
                    <tr>
                        <th><label for="subtitle">Subtitle / Tag</label></th>
                        <td><input type="text" id="subtitle" class="regular-text" style="width:100%;" placeholder="e.g. (Mid-Career Internship)"></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td>
                            <?php
                            wp_editor('', 'description', [
                                'textarea_name' => 'description',
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'teeny' => false
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="duration">Duration</label></th>
                        <td><input type="text" id="duration" class="regular-text" style="width:300px;" placeholder="Enter duration"></td>
                    </tr>
                    <tr>
                        <th><label for="who_apply">Who Should Apply</label></th>
                        <td><input type="text" id="who_apply" class="regular-text" style="width:100%;" placeholder="type here ..."></td>
                    </tr>
                    <tr>
                        <th><label>Image</label></th>
                        <td>
                            <input type="file" id="image-upload" accept="image/*">
                            <input type="hidden" id="image-url" value="">
                            <div id="image-preview" style="margin-top:10px;"></div>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:25px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Internship</button>
                    <button type="button" id="cancel-btn" class="button" style="display:none;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <!-- All Internships Table -->
        <h2>All Internship Entries</h2>
        <table id="internships-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Subtitle</th>
                    <th>Duration</th>
                    <th>Image</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;

        function loadInternships() {
            $.get('<?php echo rest_url('internship/v1/all'); ?>', function(data) {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="7" style="text-align:center;padding:50px;">No internship entries found.</td></tr>';
                } else {
                    data.forEach(i => {
                        const img = i.image ? `<img src="${i.image}" style="max-width:80px;max-height:60px;object-fit:cover;border-radius:4px;" alt="">` : '<em style="color:#999;">—</em>';
                        html += `
                            <tr>
                                <td>${i.id}</td>
                                <td>${i.title}</td>
                                td>${i.short_description}</td>
                                <td>${i.subtitle || '—'}</td>
                                <td>${i.duration || '—'}</td>
                                <td>${img}</td>
                                <td>${i.created_at}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${i.id}">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${i.id}" style="color:#b32d2e;">Delete</button>
                                </td>
                            </tr>`;
                    });
                }
                $('#internships-table tbody').html(html);
            });
        }

        // Image Upload
        $('#image-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '<?php echo rest_url('internship/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.url) {
                        $('#image-url').val(res.url);
                        $('#image-preview').html(`<img src="${res.url}" style="max-height:160px;border-radius:6px;">`);
                    } else if (res.error) {
                        alert('Upload error: ' + res.error);
                    }
                },
                error: function() {
                    alert('Image upload failed.');
                }
            });
        });

        // Save (Add or Update)
        $('#save-btn').on('click', function() {
            const title = $('#title').val().trim();
            if (!title) {
                alert('Title is required!');
                return;
            }

            const data = {
                title: title,
                short_description: $('#short_description').val().trim(),
                subtitle: $('#subtitle').val().trim(),
                description: tinymce.get('description') ? tinymce.get('description').getContent() : $('#description').val(),
                duration: $('#duration').val().trim(),
                who_apply: $('#who_apply').val().trim(),
                image: $('#image-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('internship/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('internship/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function() {
                    alert(editId ? 'Internship updated!' : 'Internship added successfully!');
                    resetForm();
                    loadInternships();
                },
                error: function() {
                    alert('Failed to save.');
                }
            });
        });

        // Edit Button
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Internship Entry');
            $('#save-btn').text('Update Internship');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('internship/v1/all'); ?>', function(internships) {
                const item = internships.find(i => i.id == editId);
                if (item) {
                    $('#title').val(item.title);
                    $('#short_description').val(item.short_description || '');
                    $('#subtitle').val(item.subtitle || '');
                    $('#duration').val(item.duration || '');
                    $('#who_apply').val(item.who_apply || '');
                    $('#image-url').val(item.image || '');
                    $('#image-preview').html(item.image ? `<img src="${item.image}" style="max-height:160px;border-radius:6px;">` : '');

                    if (tinymce.get('description')) {
                        tinymce.get('description').setContent(item.description || '');
                    }
                }
            });
        });

        // Cancel
        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#internship-form')[0].reset();
            $('#image-url').val('');
            $('#image-preview').html('');
            $('#form-heading').text('Add New Internship Entry');
            $('#save-btn').text('Add Internship');
            $('#cancel-btn').hide();
            if (tinymce.get('description')) tinymce.get('description').setContent('');
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this internship entry?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('internship/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadInternships();
                }
            });
        });

        loadInternships();
    });
    </script>
    <?php
}

// ======================
// REST CALLBACKS
// ======================
function get_all_internships() {
    global $wpdb;
    $table = $wpdb->prefix . 'internships';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_internship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'internships';
    $params = $request->get_json_params();

    $wpdb->insert($table, [
        'title'       => sanitize_text_field($params['title'] ?? ''),
        'short_description'    => sanitize_text_field($params['short_description'] ?? ''),
        'subtitle'    => sanitize_text_field($params['subtitle'] ?? ''),
        'description' => wp_kses_post($params['description'] ?? ''),
        'duration'    => sanitize_text_field($params['duration'] ?? ''),
        'who_apply'   => sanitize_text_field($params['who_apply'] ?? ''),
        'image'       => esc_url_raw($params['image'] ?? ''),
        'created_at'  => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_internship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'internships';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'title'       => sanitize_text_field($params['title'] ?? ''),
        'short_description'    => sanitize_text_field($params['short_description'] ?? ''),
        'subtitle'    => sanitize_text_field($params['subtitle'] ?? ''),
        'description' => wp_kses_post($params['description'] ?? ''),
        'duration'    => sanitize_text_field($params['duration'] ?? ''),
        'who_apply'   => sanitize_text_field($params['who_apply'] ?? ''),
        'image'       => esc_url_raw($params['image'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_internship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'internships';
    $id = absint($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}

function upload_image_to_r2_internship() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/internships";

    try {
        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/internships/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}