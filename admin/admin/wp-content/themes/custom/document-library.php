<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Document Library',
        'Document Library',
        'manage_options',
        'document-library',
        'document_library_page'
    );
});

add_action('rest_api_init', function () {

    register_rest_route('document/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_documents',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('document/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_document',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('document/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_document',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('document/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_document',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('document/v1', '/upload-pdf', [
        'methods'  => 'POST',
        'callback' => 'upload_document_pdf',
        'permission_callback' => '__return_true',
    ]);
});

// ====================== MAIN ADMIN PAGE ======================
function document_library_page() {
    $nonce = wp_create_nonce('document_library_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Document Library</h1>
        <hr class="wp-header-end">

        <div id="pub-notice" style="display:none; margin:10px 0;"></div>

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Document</h2>
            
            <form id="document-form" novalidate>
                <input type="hidden" id="document_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="doc_name">Document Name <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="doc_name" class="regular-text" style="width:100%;" placeholder="e.g. INCLEN Report 2021-22" required>
                            <p class="error-msg" id="err_name" style="color:red;display:none;">Document name is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="doc_category">Category <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="doc_category" class="regular-text" style="width:100%;" required>
                                <option value="">Select Category</option>
                                <option value="Annual Report">Annual Report</option>
                                <option value="Technical Tool">Technical Tool</option>
                                <option value="Strategy">Strategy</option>
                            </select>
                            <p class="error-msg" id="err_category" style="color:red;display:none;">Please select a category.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="doc_duration">Duration / Year <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="doc_duration" class="regular-text" style="width:100%;" placeholder="e.g. 2021-22 or 2022" required>
                            <p class="error-msg" id="err_duration" style="color:red;display:none;">Duration/Year is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Upload PDF</label></th>
                        <td>
                            <input type="file" id="pdf-upload" accept="application/pdf">
                            <p class="description">Only PDF files allowed. Max size: <strong>10MB</strong></p>
                            <input type="hidden" id="pdf_url" value="">
                            <input type="hidden" id="pdf_size" value="">
                            <div id="pdf-preview" style="margin-top:10px; font-weight:500;"></div>
                            <div id="upload-progress" style="display:none; margin-top:8px;">
                                <span class="spinner is-active" style="float:none;"></span> Uploading PDF...
                            </div>
                            <p class="error-msg" id="err_pdf" style="color:red;display:none;"></p>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:30px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Document</button>
                    <button type="button" id="cancel-btn" class="button button-large" style="display:none; margin-left:10px;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <h2>All Documents</h2>
        <table id="documents-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Document Name</th>
                    <th>Category</th>
                    <th>Duration / Year</th>
                    <th>File Size</th>
                    <th>PDF</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" style="text-align:center;padding:60px;">
                    <span class="spinner is-active" style="float:none;"></span> Loading...
                </td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;
        const MAX_SIZE = 10 * 1024 * 1024; // 10MB

        function showNotice(msg, type = 'success') {
            const cls = type === 'error' ? 'notice-error' : 'notice-success';
            $('#pub-notice').html('<p>' + msg + '</p>').addClass('notice ' + cls).show();
            setTimeout(() => $('#pub-notice').fadeOut(), 4000);
        }

        function clearErrors() {
            $('.error-msg').hide();
        }

        function validateForm() {
            clearErrors();
            let valid = true;
            if (!$('#doc_name').val().trim()) { $('#err_name').show(); valid = false; }
            if (!$('#doc_category').val()) { $('#err_category').show(); valid = false; }
            if (!$('#doc_duration').val().trim()) { $('#err_duration').show(); valid = false; }
            if (!$('#pdf_url').val()) { $('#err_pdf').text('Please upload a PDF file.').show(); valid = false; }
            return valid;
        }

        // Load All Documents
        function loadDocuments() {
            $.get('<?php echo rest_url('document/v1/all'); ?>', function(res) {
                let html = '';
                const data = res.value || [];
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="6" style="text-align:center;padding:50px;">No documents found.</td></tr>';
                } else {
                    data.forEach(function(d) {
                        html += `
                            <tr>
                                <td>${d.id}</td>
                                <td><strong>${d.name}</strong></td>
                                <td>${d.category}</td>
                                <td>${d.duration}</td>
                                <td>${d.file_size}</td>
                                <td><a href="${d.file_url}" target="_blank" >View</a></td>
                                <td>
                                   
                                    <button class="button button-small edit-btn" data-id="${d.id}" style="margin-left:4px;">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${d.id}" style="color:#b32d2e;margin-left:4px;">Delete</button>
                                </td>
                            </tr>`;
                    });
                }
                $('#documents-table tbody').html(html);
            }).fail(function() {
                $('#documents-table tbody').html('<tr><td colspan="6" style="color:red;text-align:center;">Failed to load data.</td></tr>');
            });
        }

        // PDF Upload
        $('#pdf-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            if (file.type !== 'application/pdf') {
                $('#err_pdf').text('Only PDF files are allowed.').show();
                return;
            }
            if (file.size > MAX_SIZE) {
                $('#err_pdf').text('File size must not exceed 10MB.').show();
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $('#upload-progress').show();
            $('#pdf-preview').html('');

            $.ajax({
                url: '<?php echo rest_url('document/v1/upload-pdf'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#upload-progress').hide();
                    if (res && res.url) {
                        $('#pdf_url').val(res.url);
                        $('#pdf_size').val(res.size);
                        $('#pdf-preview').html(`
                            <span style="color:#d63638;">📄 ${file.name}</span><br>
                            <small>${(file.size / (1024*1024)).toFixed(1)} MB</small>
                        `);
                    }
                },
                error: function() {
                    $('#upload-progress').hide();
                    $('#err_pdf').text('PDF upload failed.').show();
                }
            });
        });

        // Save Document
        $('#save-btn').on('click', function() {
            if (!validateForm()) return;

            const btn = $(this);
            btn.prop('disabled', true).text(editId ? 'Updating...' : 'Adding...');

            const data = {
                name: $('#doc_name').val().trim(),
                category: $('#doc_category').val(),
                duration: $('#doc_duration').val().trim(),
                file_url: $('#pdf_url').val(),
                file_size: $('#pdf_size').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('document/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('document/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    if (res && res.status) {
                        showNotice(editId ? 'Document updated successfully!' : 'Document added successfully!');
                        resetForm();
                        loadDocuments();
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to save document.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showNotice(msg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text(editId ? 'Update Document' : 'Add Document');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Document');
            $('#save-btn').text('Update Document');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('document/v1/all'); ?>', function(res) {
                const data = res.value || [];
                const item = data.find(d => d.id == editId);
                if (!item) return;

                $('#doc_name').val(item.name);
                $('#doc_category').val(item.category);
                $('#doc_duration').val(item.duration);
                $('#pdf_url').val(item.file_url);
                $('#pdf_size').val(item.file_size);

                if (item.file_url) {
                    $('#pdf-preview').html(`<span style="color:#d63638;">📄 PDF Uploaded</span><br><small>${item.file_size}</small>`);
                }
            });
        });

        // Cancel
        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#document-form')[0].reset();
            $('#pdf_url').val('');
            $('#pdf_size').val('');
            $('#pdf-preview').html('');
            $('#upload-progress').hide();
            $('#form-heading').text('Add New Document');
            $('#save-btn').text('Add Document');
            $('#cancel-btn').hide();
            clearErrors();
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this document?')) return;
            const id = $(this).data('id');

            $.ajax({
                url: '<?php echo rest_url('document/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    showNotice('Document deleted successfully!');
                    loadDocuments();
                },
                error: function() {
                    showNotice('Failed to delete document.', 'error');
                }
            });
        });

        // Initial Load
        loadDocuments();
    });
    </script>
    <?php
}

function get_all_documents() {
    global $wpdb;
    $table = $wpdb->prefix . 'document_library';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return [
        'value' => $results,
        'Count' => count($results)
    ];
}


function add_document($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'document_library';
    $params = $request->get_json_params();

    if (empty($params['name']) || empty($params['category']) || empty($params['duration']) || empty($params['file_url'])) {
        return new WP_Error('missing_fields', 'All fields are required.', ['status' => 400]);
    }

    $inserted = $wpdb->insert($table, [
        'name'      => sanitize_text_field($params['name']),
        'category'  => sanitize_text_field($params['category']),
        'duration'  => sanitize_text_field($params['duration']),
        'file_url'  => esc_url_raw($params['file_url']),
        'file_size' => sanitize_text_field($params['file_size'] ?? '—'),
        'created_at'=> current_time('mysql'),
    ]);

    return $inserted ? ['status' => 'success', 'id' => $wpdb->insert_id] : new WP_Error('db_error', 'Insert failed.', ['status' => 500]);
}

function update_document($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'document_library';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $updated = $wpdb->update($table, [
        'name'      => sanitize_text_field($params['name']),
        'category'  => sanitize_text_field($params['category']),
        'duration'  => sanitize_text_field($params['duration']),
        'file_url'  => esc_url_raw($params['file_url']),
        'file_size' => sanitize_text_field($params['file_size'] ?? '—'),
    ], ['id' => $id]);

    return $updated !== false ? ['status' => 'updated'] : new WP_Error('db_error', 'Update failed.', ['status' => 500]);
}

function delete_document($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'document_library';
    $id = absint($request['id']);
    $deleted = $wpdb->delete($table, ['id' => $id]);
    return $deleted !== false ? ['status' => 'deleted'] : new WP_Error('db_error', 'Delete failed.', ['status' => 500]);
}

function upload_document_pdf($request) {
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded.', ['status' => 400]);
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];

    // Validation
    if ($fileType !== 'application/pdf') {
        return new WP_Error('invalid_type', 'Only PDF files are allowed.', ['status' => 415]);
    }

    if ($fileSize > 10 * 1024 * 1024) {   // 10 MB
        return new WP_Error('too_large', 'File size must not exceed 10MB.', ['status' => 413]);
    }

    // Generate unique filename
    $fileName = time() . '-' . sanitize_file_name($file['name']);

    // Change this folder path as per your requirement
    $r2_key = 'admin/documents/' . $fileName;

    // Public URL base (update with your own R2 public bucket URL)
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/documents';

    global $accountId, $accessKey, $secretKey, $bucket;

    // Check if R2 credentials are set
    if (empty($accountId) || empty($accessKey) || empty($secretKey) || empty($bucket)) {
        return new WP_Error('r2_config_error', 'R2 credentials are not configured.', ['status' => 500]);
    }

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
            'Key'         => $r2_key,
            'SourceFile'  => $fileTmp,
            'ContentType' => $fileType,
            'ACL'         => 'public-read',        // Make file publicly accessible
        ]);

        $file_url = $publicUrlBase . '/' . $fileName;
        $size_mb  = round($fileSize / (1024 * 1024), 1) . ' MB';

        return [
            'url'  => $file_url,
            'size' => $size_mb
        ];

    } catch (Exception $e) {
        return new WP_Error('upload_failed', 'R2 upload error: ' . $e->getMessage(), ['status' => 500]);
    }
}