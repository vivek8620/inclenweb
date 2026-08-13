<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-news',
        'Heading Manager',
        'Headings',
        'manage_options',
        'heading-manager',
        'heading_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('heading/v1', '/all-headings', [
        'methods'  => 'GET',
        'callback' => 'get_all_headings',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('heading/v1', '/add-heading', [
        'methods'  => 'POST',
        'callback' => 'add_heading',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('heading/v1', '/update-heading/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_heading',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('heading/v1', '/delete-heading/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_heading',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('heading/v1', '/upload-image-heading', [
        'methods'  => 'POST',
        'callback' => 'upload_image_to_r2_heading',
        'permission_callback' => '__return_true'
    ]);
});


function heading_manager_page() {
    $nonce = wp_create_nonce('heading_manager_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Heading Manager</h1>
        <hr class="wp-header-end">

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Heading</h2>
            
            <form id="heading-form">
                <input type="hidden" id="heading_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="title">Title <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="title" class="regular-text" style="width:100%;" required></td>
                    </tr>
                    <tr>
                        <th><label for="heading_type">Heading Type</label></th>
                        <td>
                            <select id="heading_type" style="width:300px;">
                                <option value="" selected>-- Select --</option>
                                <option value="Policy Dialogue">Policy Dialogue</option>
                                <option value="Research Study">Research Study</option>
                                <option value="Milestone">Milestone</option>
                                <option value="Advocacy" >Advocacy</option>
                                <option value="Surveillance">Surveillance</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="content">Content</label></th>
                        <td>
                            <?php
                            wp_editor('', 'content', [
                                'textarea_name' => 'content',
                                'textarea_rows' => 12,
                                'media_buttons' => true,
                                'teeny'         => false
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Image (Optional)</label></th>
                        <td>
                            <input type="file" id="image-upload" accept="image/*">
                            <input type="hidden" id="image-url" value="">
                            <div id="image-preview" style="margin-top:10px;"></div>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:25px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Heading</button>
                    <button type="button" id="cancel-btn" class="button" style="display:none;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <!-- All Headings Table -->
        <h2>All Headings</h2>
        <table id="headings-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
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

        // Load all headings
        function loadHeadings() {
            $.get('<?php echo rest_url('heading/v1/all-headings'); ?>', function(data) {
                let html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="6" style="text-align:center;padding:50px;">No headings found.</td></tr>';
                } else {
                    data.forEach(h => {
                        const img = h.image ? `<img src="${h.image}" style="max-width:80px;max-height:60px;object-fit:cover;border-radius:4px;" alt="">` : '<em style="color:#999;">—</em>';
                        html += `
                            <tr>
                                <td>${h.id}</td>
                                <td>${h.title}</td>
                                <td>${h.heading_type.charAt(0).toUpperCase() + h.heading_type.slice(1)}</td>
                                <td>${img}</td>
                                <td>${h.created_at}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${h.id}">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${h.id}" style="color:#b32d2e;">Delete</button>
                                </td>
                            </tr>`;
                    });
                }
                $('#headings-table tbody').html(html);
            });
        }

        // Image Upload
        $('#image-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '<?php echo rest_url('heading/v1/upload-image-heading'); ?>',
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
                heading_type: $('#heading_type').val(),
                content: tinymce.get('content') ? tinymce.get('content').getContent() : $('#content').val(),
                image: $('#image-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('heading/v1/update-heading/'); ?>' + editId 
                : '<?php echo rest_url('heading/v1/add-heading'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    alert(editId ? 'Heading updated!' : 'Heading added successfully!');
                    resetForm();
                    loadHeadings();
                },
                error: function() {
                    alert('Failed to save heading.');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Heading');
            $('#save-btn').text('Update Heading');
            $('#cancel-btn').show();

            // Fetch single heading (you can improve this by adding a GET single route if needed)
            // For simplicity, reload all and find (or add a new route)
            $.get('<?php echo rest_url('heading/v1/all-headings'); ?>', function(headings) {
                const h = headings.find(item => item.id == editId);
                if (h) {
                    $('#heading_id').val(h.id);
                    $('#title').val(h.title);
                    $('#heading_type').val(h.heading_type);
                    $('#image-url').val(h.image || '');
                    $('#image-preview').html(h.image ? `<img src="${h.image}" style="max-height:160px;border-radius:6px;">` : '');

                    // Set editor content
                    if (tinymce.get('content')) {
                        tinymce.get('content').setContent(h.content || '');
                    }
                }
            });
        });

        // Cancel Edit
        $('#cancel-btn').on('click', function() {
            resetForm();
        });

        function resetForm() {
            editId = 0;
            $('#heading-form')[0].reset();
            $('#image-url').val('');
            $('#image-preview').html('');
            $('#form-heading').text('Add New Heading');
            $('#save-btn').text('Add Heading');
            $('#cancel-btn').hide();
            if (tinymce.get('content')) tinymce.get('content').setContent('');
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this heading?')) return;

            const id = $(this).data('id');

            $.ajax({
                url: '<?php echo rest_url('heading/v1/delete-heading/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadHeadings();
                },
                error: function() {
                    alert('Delete failed.');
                }
            });
        });

        // Initial load
        loadHeadings();
    });
    </script>
    <?php
}

function get_all_headings() {
    global $wpdb;
    $table = $wpdb->prefix . 'headings';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_heading($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'headings';
    $params = $request->get_json_params();

    $wpdb->insert($table, [
        'title'        => sanitize_text_field($params['title'] ?? ''),
        'heading_type' => sanitize_text_field($params['heading_type'] ?? 'other'),
        'content'      => wp_kses_post($params['content'] ?? ''),
        'image'        => esc_url_raw($params['image'] ?? ''),
        'created_at'   => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_heading($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'headings';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'title'        => sanitize_text_field($params['title'] ?? ''),
        'heading_type' => sanitize_text_field($params['heading_type'] ?? ''),
        'content'      => wp_kses_post($params['content'] ?? ''),
        'image'        => esc_url_raw($params['image'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_heading($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'headings';
    $id = absint($request['id']);

    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}

function upload_image_to_r2_heading() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/headings";

    try {
        $client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => 'auto',
            'endpoint'    => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/headings/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}
