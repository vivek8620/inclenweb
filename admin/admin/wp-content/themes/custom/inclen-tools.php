<?php
// Manager for INCLEN Tools

// Registration for REST API
add_action('rest_api_init', function () {
    // Migration: Ensure 'year' column exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'inclen_tools';
    $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'year'));
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE `$table_name` ADD `year` VARCHAR(50) AFTER `tool_name` ");
    }

    register_rest_route('inclen-tools/v1', '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_inclen_tools',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('inclen-tools/v1', '/add', [
        'methods' => 'POST',
        'callback' => 'add_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/delete/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'delete_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/upload-pdf', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_to_r2_tools',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

// PDF Upload to R2
function upload_pdf_to_r2_tools() {
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
            'Key' => 'admin/inclen-tools/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/inclen-tools/pdfs/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_inclen_tools() {
    global $wpdb;
    $table = $wpdb->prefix . 'inclen_tools';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return ['value' => $results];
}

function add_inclen_tool($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'inclen_tools';
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->insert($table, [
            'project_name' => sanitize_text_field($params['project_name'] ?? ''),
            'tool_name' => sanitize_text_field($params['tool_name'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'modules' => $params['modules'] ?? '[]',
            'cover_image' => esc_url_raw($params['cover_image'] ?? ''),
            'pdfs' => $params['pdfs'] ?? '[]'
        ]);

        return ['status' => 'success', 'id' => $wpdb->insert_id];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function update_inclen_tool($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'inclen_tools';
        $id = $request['id'];
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->update($table, [
            'project_name' => sanitize_text_field($params['project_name'] ?? ''),
            'tool_name' => sanitize_text_field($params['tool_name'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'modules' => $params['modules'] ?? '[]',
            'cover_image' => esc_url_raw($params['cover_image'] ?? ''),
            'pdfs' => $params['pdfs'] ?? '[]'
        ], ['id' => $id]);

        return ['status' => 'success'];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function delete_inclen_tool($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'inclen_tools';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// Admin Menu and UI
add_action('admin_menu', function () {
    add_submenu_page(
        'group-research',
        'INCLEN Tools Manager',
        'INCLEN Tools',
        'manage_options',
        'inclen-tools',
        'inclen_tools_admin_page'
    );
});

function inclen_tools_admin_page() {
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
        .inclen-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #111827;
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
        .module-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 20px;
        }
        .module-item {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #f3f4f6;
            position: relative;
        }
        .remove-module {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #9ca3af;
            cursor: pointer;
        }
        .remove-module:hover { color: #ef4444; }
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
        .btn-add-module {
            background: #fff !important;
            border: 1px solid #00558f !important;
            color: #00558f !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
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
        <h1>INCLEN Tools Manager</h1>

        <div class="inclen-card" id="tool-form-container">
            <h2 style="font-size: 18px; margin-bottom: 30px;">Add New INCLEN Tool</h2>
            
            <input type="hidden" id="tool-id" value="">

            <div class="form-row">
                <div class="form-label">Project Name <span>*</span></div>
                <input type="text" id="project-name" class="form-input" placeholder="Enter project name">
            </div>

            <div class="form-row">
                <div class="form-label">Tool Name</div>
                <input type="text" id="tool-name" class="form-input" placeholder="Enter tool name">
            </div>

            <div class="form-row">
                <div class="form-label">Year of Completion</div>
                <input type="text" id="tool-year" class="form-input" placeholder="Enter year (e.g. 2024)">
            </div>

            <div class="form-row">
                <div class="form-label">Tool PDF Documents <span>*</span></div>
                <div>
                    <button type="button" class="btn-upload" id="upload-main-pdf">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                    <div id="main-pdf-list"></div>
                </div>
            </div>

            <div class="module-section-header">
                <div style="font-size: 16px; font-weight: 500; color: #374151;">Modules / Sections</div>
                <button type="button" class="btn-add-module" id="add-module-btn">
                    <span class="dashicons dashicons-plus"></span> Add Module
                </button>
            </div>

            <div id="modules-container">
                <!-- Modules will be added here -->
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #f3f4f6; pt: 20px;">
                <button type="button" class="btn-save" id="save-tool-btn">Save Tool</button>
                <button type="button" class="btn-clear" id="clear-form-btn">Clear Form</button>
            </div>
        </div>

        <h2 style="margin-top: 40px;">All INCLEN Tools</h2>
        <table class="wp-list-table widefat fixed striped" style="border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <thead>
                <tr>
                    <th style="padding: 15px;">Project Name</th>
                    <th style="padding: 15px;">Tool Name</th>
                    <th style="padding: 15px;">Year</th>
                    <th style="padding: 15px; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tools-list">
                <!-- Data loaded via JS -->
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('inclen-tools/v1'); ?>';
        const WP_NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
        
        let mainPdfs = [];
        let modules = [];

        function renderMainPdfs() {
            const container = $('#main-pdf-list');
            container.empty();
            mainPdfs.forEach((pdf, index) => {
                container.append(`
                    <div class="pdf-preview-item">
                        <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                        <span>${pdf.name}</span>
                        <span class="remove-pdf dashicons dashicons-no-alt" data-index="${index}"></span>
                    </div>
                `);
            });
        }

        function renderModules() {
            const container = $('#modules-container');
            container.empty();
            modules.forEach((module, mIndex) => {
                let pdfHtml = '';
                if (module.pdfs) {
                    module.pdfs.forEach((pdf, pIndex) => {
                        pdfHtml += `
                            <div class="pdf-preview-item">
                                <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                                <span>${pdf.name}</span>
                                <span class="remove-module-pdf dashicons dashicons-no-alt" data-mindex="${mIndex}" data-pindex="${pIndex}"></span>
                            </div>
                        `;
                    });
                }

                container.append(`
                    <div class="module-item" data-index="${mIndex}">
                        <span class="remove-module dashicons dashicons-no-alt" data-index="${mIndex}"></span>
                        <div class="form-row">
                            <div class="form-label">Module Name</div>
                            <input type="text" class="form-input module-name" value="${module.name || ''}" placeholder="Enter module name" data-index="${mIndex}">
                        </div>
                        <div class="form-row" style="margin-bottom: 0;">
                            <div class="form-label">Module PDF</div>
                            <div>
                                <button type="button" class="btn-upload upload-module-pdf" data-index="${mIndex}">
                                    <span class="dashicons dashicons-upload"></span> Upload PDF
                                </button>
                                <div class="module-pdf-list">${pdfHtml}</div>
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        // Add Module
        $('#add-module-btn').click(function() {
            modules.push({ name: '', pdfs: [] });
            renderModules();
        });

        // Remove Module
        $(document).on('click', '.remove-module', function() {
            const index = $(this).data('index');
            modules.splice(index, 1);
            renderModules();
        });

        // Update Module Name
        $(document).on('input', '.module-name', function() {
            const index = $(this).data('index');
            modules[index].name = $(this).val();
        });

        // Main PDF Upload
        $('#upload-main-pdf').click(function() {
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                const btn = $('#upload-main-pdf');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        mainPdfs.push({ name: file.name, url: res.url });
                        renderMainPdfs();
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

        // Module PDF Upload
        $(document).on('click', '.upload-module-pdf', function() {
            const mIndex = $(this).data('index');
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                const btn = $(e.target).closest('.btn-upload');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        if (!modules[mIndex].pdfs) modules[mIndex].pdfs = [];
                        modules[mIndex].pdfs.push({ name: file.name, url: res.url });
                        renderModules();
                    },
                    error: function() {
                        alert('Upload failed');
                        renderModules();
                    }
                });
            });
            fileInput.click();
        });

        // Remove PDF
        $(document).on('click', '.remove-pdf', function() {
            const index = $(this).data('index');
            mainPdfs.splice(index, 1);
            renderMainPdfs();
        });

        $(document).on('click', '.remove-module-pdf', function() {
            const mIndex = $(this).data('mindex');
            const pIndex = $(this).data('pindex');
            modules[mIndex].pdfs.splice(pIndex, 1);
            renderModules();
        });

        // Load Tools
        function loadTools() {
            $.get(API_BASE + '/all', function(response) {
                const list = $('#tools-list');
                list.empty();
                if (response.value) {
                    response.value.forEach(tool => {
                        list.append(`
                            <tr>
                                <td style="padding: 15px;">${tool.project_name}</td>
                                <td style="padding: 15px;">${tool.tool_name}</td>
                                <td style="padding: 15px;">${tool.year || '-'}</td>
                                <td style="padding: 15px;">
                                    <button class="button edit-btn" data-tool='${JSON.stringify(tool)}'>Edit</button>
                                    <button class="button delete-btn" data-id="${tool.id}" style="color: red;">Delete</button>
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        }

        loadTools();

        // Save Tool
        $('#save-tool-btn').click(function() {
            const id = $('#tool-id').val();
            const data = {
                project_name: $('#project-name').val(),
                tool_name: $('#tool-name').val(),
                year: $('#tool-year').val(),
                pdfs: JSON.stringify(mainPdfs),
                modules: JSON.stringify(modules)
            };

            if (!data.project_name) {
                alert('Project Name is required');
                return;
            }

            const url = id ? API_BASE + '/update/' + id : API_BASE + '/add';
            const btn = $(this);
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    alert('Tool saved successfully');
                    clearForm();
                    loadTools();
                    btn.prop('disabled', false).text('Save Tool');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                    btn.prop('disabled', false).text('Save Tool');
                }
            });
        });

        function clearForm() {
            $('#tool-id').val('');
            $('#project-name').val('');
            $('#tool-name').val('');
            $('#tool-year').val('');
            mainPdfs = [];
            modules = [];
            renderMainPdfs();
            renderModules();
        }

        $('#clear-form-btn').click(clearForm);

        // Edit
        $(document).on('click', '.edit-btn', function() {
            const tool = $(this).data('tool');
            $('#tool-id').val(tool.id);
            $('#project-name').val(tool.project_name);
            $('#tool-name').val(tool.tool_name);
            $('#tool-year').val(tool.year || '');
            
            try {
                mainPdfs = JSON.parse(tool.pdfs || '[]');
                modules = JSON.parse(tool.modules || '[]');
            } catch(e) {
                mainPdfs = [];
                modules = [];
            }
            
            renderMainPdfs();
            renderModules();
            window.scrollTo(0, 0);
        });

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (confirm('Are you sure you want to delete this tool?')) {
                const id = $(this).data('id');
                $.ajax({
                    url: API_BASE + '/delete/' + id,
                    method: 'POST',
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function() {
                        loadTools();
                    }
                });
            }
        });
    });
    </script>
    <?php
}