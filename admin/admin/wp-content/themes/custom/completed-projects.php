<?php
/**
 * Completed Projects Manager
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-research',
        'Completed Projects',
        'Completed Projects',
        'manage_options',
        'completed-projects',
        'completed_projects_page'
    );
});

add_action('rest_api_init', function () {
    $namespace = 'inclen-completed/v1';

    register_rest_route($namespace, '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_completed_projects',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route($namespace, '/add', [
        'methods' => 'POST',
        'callback' => 'add_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/delete/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/upload-pdf', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_to_r2_completed',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

function upload_pdf_to_r2_completed() {
    try {
        require_once __DIR__ . '/cloud_config.php';
        
        if (!isset($_FILES['file'])) {
            return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        if (!class_exists('Aws\S3\S3Client')) {
            return new WP_Error('sdk_missing', 'AWS SDK not loaded', ['status' => 500]);
        }

        $client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://" . R2_ACCOUNT_ID . ".r2.cloudflarestorage.com",
            'credentials' => [
                'key' => R2_ACCESS_KEY,
                'secret' => R2_SECRET_KEY,
            ],
        ]);

        $file = $_FILES['file'];
        $fileName = time() . '-' . sanitize_file_name($file['name']);

        $result = $client->putObject([
            'Bucket' => R2_BUCKET,
            'Key' => 'admin/completed-projects/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/completed-projects/pdfs/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_completed_projects() {
    global $wpdb;
    $table = $wpdb->prefix . 'completed_projects';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC") ?: [];
    return ['value' => $results];
}

function add_completed_project($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'completed_projects';
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->insert($table, [
            'title' => sanitize_text_field($params['title'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'principal_investigator' => sanitize_text_field($params['principal_investigator'] ?? ''),
            'co_investigator' => sanitize_text_field($params['co_investigator'] ?? ''),
            'funder' => sanitize_text_field($params['funder'] ?? ''),
            'study_sites' => sanitize_text_field($params['study_sites'] ?? ''),
            'pdf_url' => esc_url_raw($params['pdf_url'] ?? ''),
            'summary' => sanitize_textarea_field($params['summary'] ?? '')
        ]);

        return ['status' => 'success', 'id' => $wpdb->insert_id];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function update_completed_project($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'completed_projects';
        $id = $request['id'];
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->update($table, [
            'title' => sanitize_text_field($params['title'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'principal_investigator' => sanitize_text_field($params['principal_investigator'] ?? ''),
            'co_investigator' => sanitize_text_field($params['co_investigator'] ?? ''),
            'funder' => sanitize_text_field($params['funder'] ?? ''),
            'pdf_url' => esc_url_raw($params['pdf_url'] ?? ''),
            'summary' => sanitize_textarea_field($params['summary'] ?? '')
        ], ['id' => $id]);

        return ['status' => 'success'];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function delete_completed_project($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'completed_projects';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'success'];
}

function completed_projects_page() {
    ?>
    <style>
        .inclen-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 40px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .form-label {
            width: 200px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        .form-label span { color: #ef4444; }
        .form-input {
            flex: 1;
            max-width: 400px;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }
        .btn-upload {
            background: #fff !important;
            border: 1px solid #3b82f6 !important;
            color: #3b82f6 !important;
            border-radius: 6px !important;
            padding: 6px 15px !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-save {
            background: #00558f !important;
            color: #fff !important;
            border: none !important;
            padding: 10px 24px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            cursor: pointer;
        }
        .btn-clear {
            background: #fff !important;
            border: 1px solid #d1d5db !important;
            color: #374151 !important;
            padding: 10px 24px !important;
            border-radius: 6px !important;
            margin-left: 10px !important;
            cursor: pointer;
        }
        .pdf-preview-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            font-size: 13px;
            color: #4b5563;
        }
        .remove-pdf { color: #ef4444; cursor: pointer; }
    </style>

    <div class="wrap">
        <h1>Completed Projects Manager</h1>

        <div class="inclen-card">
            <h2 id="form-heading" style="font-size: 18px; margin-bottom: 30px;">Add New Completed Project</h2>
            <input type="hidden" id="project-id" value="">

            <div class="form-row">
                <div class="form-label">Project Title <span>*</span></div>
                <input type="text" id="title" class="form-input" placeholder="Enter project title">
            </div>

            <div class="form-row">
                <div class="form-label">Year of Completion</div>
                <input type="text" id="year" class="form-input" placeholder="Enter year">
            </div>

            <div class="form-row">
                <div class="form-label">Principal Investigator</div>
                <input type="text" id="pi" class="form-input" placeholder="Enter principal investigator">
            </div>

            <div class="form-row">
                <div class="form-label">Co-Investigator</div>
                <input type="text" id="co_pi" class="form-input" placeholder="Enter co-investigator">
            </div>

            <div class="form-row">
                <div class="form-label">Funder</div>
                <input type="text" id="funder" class="form-input" placeholder="Enter funder">
            </div>

            <div class="form-row">
                <div class="form-label">Study Sites</div>
                <input type="text" id="study_sites" class="form-input" placeholder="Enter study sites">
            </div>

            <div class="form-row">
                <div class="form-label">Report (PDF) <span>*</span></div>
                <div>
                    <button type="button" class="btn-upload" id="upload-pdf-btn">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                    <input type="hidden" id="pdf_url">
                    <div id="pdf-preview" style="display:none;" class="pdf-preview-item">
                        <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                        <span id="pdf-name"></span>
                        <span class="remove-pdf dashicons dashicons-no-alt" onclick="jQuery('#pdf_url').val(''); jQuery('#pdf-preview').hide();"></span>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Summary</div>
                <textarea id="summary" class="form-input" style="max-width: 600px; height: 100px;" placeholder="Enter project summary"></textarea>
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #f3f4f6; pt: 20px;">
                <button type="button" class="btn-save" id="save-btn">Save Project</button>
                <button type="button" class="btn-clear" id="clear-btn">Clear Form</button>
            </div>
        </div>

        <h2 style="margin-top: 40px;">All Completed Projects</h2>
        <table class="wp-list-table widefat fixed striped" style="border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <thead>
                <tr>
                    <th style="padding: 15px; width: 50px;">Sr No</th>
                    <th style="padding: 15px;">Project Title</th>
                    <th style="padding: 15px;">Year</th>
                    <th style="padding: 15px;">PI</th>
                    <th style="padding: 15px;">Co-PI</th>
                    <th style="padding: 15px;">Report</th>
                    <th style="padding: 15px; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="projects-list"></tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('inclen-completed/v1'); ?>';
        const WP_NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
        let editId = 0;

        function loadProjects() {
            $.get(API_BASE + '/all', function(res) {
                let html = '';
                (res.value || []).forEach((p, index) => {
                    html += `<tr>
                        <td style="padding: 15px;">${index + 1}</td>
                        <td style="padding: 15px;">${p.title}</td>
                        <td style="padding: 15px;">${p.year}</td>
                        <td style="padding: 15px;">${p.principal_investigator}</td>
                        <td style="padding: 15px;">${p.co_investigator}</td>
                        <td style="padding: 15px;">
                            ${p.pdf_url ? `<a href="${p.pdf_url}" target="_blank" style="color: #ef4444;"><span class="dashicons dashicons-pdf"></span></a>` : '-'}
                        </td>
                        <td style="padding: 15px;">
                            <button class="button edit-btn" data-json='${JSON.stringify(p)}'>Edit</button>
                            <button class="button delete-btn" data-id="${p.id}" style="color: red;">Delete</button>
                        </td>
                    </tr>`;
                });
                $('#projects-list').html(html);
            });
        }

        $('#upload-pdf-btn').click(function() {
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                const btn = $('#upload-pdf-btn');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        $('#pdf_url').val(res.url);
                        $('#pdf-name').text(file.name);
                        $('#pdf-preview').show();
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Upload PDF');
                    },
                    error: function() {
                        alert('Upload failed');
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Upload PDF');
                    }
                });
            });
            fileInput.click();
        });

        $('#save-btn').click(function() {
            const data = {
                title: $('#title').val(),
                year: $('#year').val(),
                principal_investigator: $('#pi').val(),
                co_investigator: $('#co_pi').val(),
                funder: $('#funder').val(),
                study_sites: $('#study_sites').val(),
                summary: $('#summary').val(),
                pdf_url: $('#pdf_url').val()
            };

            if (!data.title) return alert('Title is required');

            const url = editId ? API_BASE + '/update/' + editId : API_BASE + '/add';
            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    alert('Project saved successfully');
                    clearForm();
                    loadProjects();
                }
            });
        });

        function clearForm() {
            editId = 0;
            $('#project-id').val('');
            $('#title').val('');
            $('#year').val('');
            $('#pi').val('');
            $('#co_pi').val('');
            $('#funder').val('');
            $('#study_sites').val('');
            $('#summary').val('');
            $('#pdf_url').val('');
            $('#pdf-preview').hide();
            $('#form-heading').text('Add New Completed Project');
            $('#save-btn').text('Save Project');
        }

        $('#clear-btn').click(clearForm);

        $(document).on('click', '.edit-btn', function() {
            const p = $(this).data('json');
            editId = p.id;
            $('#title').val(p.title);
            $('#year').val(p.year);
            $('#pi').val(p.principal_investigator);
            $('#co_pi').val(p.co_investigator);
            $('#funder').val(p.funder);
            $('#study_sites').val(p.study_sites);
            $('#summary').val(p.summary);
            $('#pdf_url').val(p.pdf_url);
            
            if (p.pdf_url) {
                $('#pdf-name').text('Current PDF');
                $('#pdf-preview').show();
            }

            $('#form-heading').text('Edit Completed Project');
            $('#save-btn').text('Update Project');
            window.scrollTo(0, 0);
        });

        $(document).on('click', '.delete-btn', function() {
            if(!confirm('Delete this project?')) return;
            $.ajax({
                url: API_BASE + '/delete/' + $(this).data('id'),
                method: 'DELETE',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: loadProjects
            });
        });

        loadProjects();
    });
    </script>
    <?php
}
