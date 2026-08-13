<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-research',
        'Research Projects',
        'Research Projects',
        'manage_options',
        'research-projects',
        'research_projects_page'
    );
});


add_action('rest_api_init', function () {

    register_rest_route('research/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_research_projects',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('research/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_research_project',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('research/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_research_project',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('research/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_research_project',
        'permission_callback' => '__return_true',
    ]);

    // Uploads
    register_rest_route('research/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_research_image',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('research/v1', '/upload-pdf', [
        'methods'  => 'POST',
        'callback' => 'upload_research_pdf',
        'permission_callback' => '__return_true',
    ]);
});

// ====================== MAIN ADMIN PAGE ======================
function research_projects_page() {
    $nonce = wp_create_nonce('research_projects_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Research Projects</h1>
        <hr class="wp-header-end">

        <div id="pub-notice" style="display:none; margin:10px 0;"></div>

        <!-- Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Research Project</h2>
            
            <form id="research-form" novalidate>
                <input type="hidden" id="project_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="title">Project Title <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="title" class="regular-text" style="width:100%;" placeholder="Enter project title" required>
                            <p class="error-msg" id="err_title" style="color:red;display:none;">Project title is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="year">Year of Completion <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="year" class="regular-text" style="width:100%;" placeholder="e.g. 2023 or 2022-2024" required>
                            <p class="error-msg" id="err_year" style="color:red;display:none;">Year of completion is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pi">Principal Investigator <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="pi" class="regular-text" style="width:100%;" placeholder="e.g. Dr. Anil Kumar" required>
                            <p class="error-msg" id="err_pi" style="color:red;display:none;">Principal Investigator is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="funder">Funder <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="funder" class="regular-text" style="width:100%;" placeholder="e.g. ICMR, DBT, WHO" required>
                            <p class="error-msg" id="err_funder" style="color:red;display:none;">Funder is required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sites">Study Sites <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="sites" class="regular-text" style="width:100%;" placeholder="e.g. Delhi, Mumbai, Bangalore" required>
                            <p class="error-msg" id="err_sites" style="color:red;display:none;">Study sites are required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Project Image</label></th>
                        <td>
                            <input type="file" id="image-upload" accept="image/jpeg,image/png,image/webp,image/gif">
                            <p class="description">JPG, PNG, WEBP, GIF (Max 5MB)</p>
                            <input type="hidden" id="image_url" value="">
                            <div id="image-preview" style="margin-top:10px;"></div>
                            <div id="image-progress" style="display:none;">
                                <span class="spinner is-active" style="float:none;"></span> Uploading image...
                            </div>
                            <p class="error-msg" id="err_image" style="color:red;display:none;"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>PDF Report</label></th>
                        <td>
                            <input type="file" id="pdf-upload" accept="application/pdf">
                            <p class="description">PDF Report (Max 10MB)</p>
                            <input type="hidden" id="pdf_url" value="">
                            <div id="pdf-preview" style="margin-top:10px; font-weight:500;"></div>
                            <div id="pdf-progress" style="display:none;">
                                <span class="spinner is-active" style="float:none;"></span> Uploading PDF...
                            </div>
                            <p class="error-msg" id="err_pdf" style="color:red;display:none;"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="summary">Summary</label></th>
                        <td>
                            <textarea id="summary" class="large-text" rows="6" style="width:100%;" placeholder="Brief summary of the project..."></textarea>
                        </td>
                    </tr>
                </table>

                <p style="margin-top:30px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Project</button>
                    <button type="button" id="cancel-btn" class="button button-large" style="display:none; margin-left:10px;">Cancel Edit</button>
                </p>
            </form>
        </div>

        <h2>All Research Projects</h2>
        <table id="projects-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:80px;">Image</th>
                    <th>Project Title</th>
                    <th>Year</th>
                    <th>Principal Investigator</th>
                    <th>Funder</th>
                    <th>PDF</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" style="text-align:center;padding:60px;">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;
        const MAX_IMAGE = 5 * 1024 * 1024;
        const MAX_PDF   = 10 * 1024 * 1024;

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
            if (!$('#title').val().trim()) { $('#err_title').show(); valid = false; }
            if (!$('#year').val().trim()) { $('#err_year').show(); valid = false; }
            if (!$('#pi').val().trim()) { $('#err_pi').show(); valid = false; }
            if (!$('#funder').val().trim()) { $('#err_funder').show(); valid = false; }
            if (!$('#sites').val().trim()) { $('#err_sites').show(); valid = false; }
            return valid;
        }

        // Load Projects
        function loadProjects() {
            $.get('<?php echo rest_url('research/v1/all'); ?>', function(res) {
                let html = '';
                const data = res.value || [];
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="7" style="text-align:center;padding:50px;">No research projects found.</td></tr>';
                } else {
                    data.forEach(function(p) {
                        const img = p.image_url ? `<img src="${p.image_url}" style="max-width:80px;max-height:80px;object-fit:cover;border-radius:6px;" alt="">` : '<em style="color:#999;">—</em>';
                        html += `
                            <tr>
                                <td>${p.id}</td>
                                <td>${img}</td>
                                <td><strong>${p.title}</strong></td>
                                <td>${p.year}</td>
                                <td>${p.principal_investigator}</td>
                                <td>${p.funder}</td>
                                <td>${p.pdf_url ? `<a href="${p.pdf_url}" target="_blank" class="">View</a>` : ''}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${p.id}">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${p.id}" style="color:#b32d2e;margin-left:4px;">Delete</button>
                                    
                                </td>
                            </tr>`;
                    });
                }
                $('#projects-table tbody').html(html);
            });
        }

        // Image Upload
        $('#image-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            if (file.size > MAX_IMAGE) {
                $('#err_image').text('Image must not exceed 5MB.').show();
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $('#image-progress').show();
            $.ajax({
                url: '<?php echo rest_url('research/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#image-progress').hide();
                    if (res.url) {
                        $('#image_url').val(res.url);
                        $('#image-preview').html(`<img src="${res.url}" style="max-height:180px;border-radius:8px;border:1px solid #ddd;">`);
                    }
                },
                error: function() {
                    $('#image-progress').hide();
                    $('#err_image').text('Image upload failed.').show();
                }
            });
        });

        // PDF Upload
        $('#pdf-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            if (file.size > MAX_PDF) {
                $('#err_pdf').text('PDF must not exceed 10MB.').show();
                return;
            }
            if (file.type !== 'application/pdf') {
                $('#err_pdf').text('Only PDF files allowed.').show();
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            $('#pdf-progress').show();
            $.ajax({
                url: '<?php echo rest_url('research/v1/upload-pdf'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#pdf-progress').hide();
                    if (res.url) {
                        $('#pdf_url').val(res.url);
                        $('#pdf-preview').html(`📄 PDF Uploaded (${(file.size/(1024*1024)).toFixed(1)} MB)`);
                    }
                },
                error: function() {
                    $('#pdf-progress').hide();
                    $('#err_pdf').text('PDF upload failed.').show();
                }
            });
        });

        // Save Project
        $('#save-btn').on('click', function() {
            if (!validateForm()) return;

            const btn = $(this);
            btn.prop('disabled', true).text(editId ? 'Updating...' : 'Adding...');

            const data = {
                title: $('#title').val().trim(),
                year: $('#year').val().trim(),
                principal_investigator: $('#pi').val().trim(),
                funder: $('#funder').val().trim(),
                study_sites: $('#sites').val().trim(),
                summary: $('#summary').val().trim(),
                image_url: $('#image_url').val(),
                pdf_url: $('#pdf_url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('research/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('research/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    if (res.status) {
                        showNotice(editId ? 'Project updated successfully!' : 'Project added successfully!');
                        resetForm();
                        loadProjects();
                    }
                },
                error: function() {
                    showNotice('Failed to save project.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text(editId ? 'Update Project' : 'Add Project');
                }
            });
        });

        // Edit & Delete & Reset functions (same pattern as before)
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Research Project');
            $('#save-btn').text('Update Project');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('research/v1/all'); ?>', function(res) {
                const data = res.value || [];
                const p = data.find(item => item.id == editId);
                if (!p) return;
                $('#title').val(p.title);
                $('#year').val(p.year);
                $('#pi').val(p.principal_investigator);
                $('#funder').val(p.funder);
                $('#sites').val(p.study_sites);
                $('#summary').val(p.summary || '');
                $('#image_url').val(p.image_url || '');
                $('#pdf_url').val(p.pdf_url || '');

                if (p.image_url) $('#image-preview').html(`<img src="${p.image_url}" style="max-height:180px;border-radius:8px;border:1px solid #ddd;">`);
                if (p.pdf_url) $('#pdf-preview').html('📄 PDF Uploaded');
            });
        });

        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#research-form')[0].reset();
            $('#image_url').val(''); $('#pdf_url').val('');
            $('#pi').val(''); $('#funder').val('');
            $('#image-preview').html(''); $('#pdf-preview').html('');
            $('#image-progress').hide(); $('#pdf-progress').hide();
            $('#form-heading').text('Add New Research Project');
            $('#save-btn').text('Add Project');
            $('#cancel-btn').hide();
            clearErrors();
        }

        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this research project?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('research/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    showNotice('Project deleted successfully!');
                    loadProjects();
                }
            });
        });

        loadProjects();
    });
    </script>
    <?php
}


function get_all_research_projects() {
    global $wpdb;
    $table = $wpdb->prefix . 'research_projects';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return [
        'value' => $results,
        'Count' => count($results)
    ];
}

function add_research_project($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_projects';
    $params = $request->get_json_params();

    if (empty($params['title']) || empty($params['year']) || empty($params['principal_investigator']) || 
        empty($params['funder']) || empty($params['study_sites'])) {
        return new WP_Error('missing_fields', 'Required fields missing.', ['status' => 400]);
    }

    $wpdb->insert($table, [
        'title'                  => sanitize_text_field($params['title']),
        'year'                   => sanitize_text_field($params['year']),
        'principal_investigator' => sanitize_text_field($params['principal_investigator']),
        'funder'                 => sanitize_text_field($params['funder']),
        'study_sites'            => sanitize_text_field($params['study_sites']),
        'summary'                => sanitize_textarea_field($params['summary'] ?? ''),
        'image_url'              => esc_url_raw($params['image_url'] ?? ''),
        'pdf_url'                => esc_url_raw($params['pdf_url'] ?? ''),
        'created_at'             => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_research_project($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_projects';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'title'                  => sanitize_text_field($params['title']),
        'year'                   => sanitize_text_field($params['year']),
        'principal_investigator' => sanitize_text_field($params['principal_investigator']),
        'funder'                 => sanitize_text_field($params['funder']),
        'study_sites'            => sanitize_text_field($params['study_sites']),
        'summary'                => sanitize_textarea_field($params['summary'] ?? ''),
        'image_url'              => esc_url_raw($params['image_url'] ?? ''),
        'pdf_url'                => esc_url_raw($params['pdf_url'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_research_project($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_projects';
    $id = absint($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}


function upload_research_image($request) {

    if (empty($_FILES['file'])) return new WP_Error('no_file', 'No file.', ['status' => 400]);

    $file = $_FILES['file'];
    if (!in_array($file['type'], ['image/jpeg','image/png','image/webp','image/gif'])) {
        return new WP_Error('invalid_type', 'Invalid image type.', ['status' => 415]);
    }
    if ($file['size'] > 5*1024*1024) {
        return new WP_Error('too_large', 'Max 5MB allowed.', ['status' => 413]);
    }

    $fileName = time() . '-' . sanitize_file_name($file['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/research/images';

    global $accountId, $accessKey, $secretKey, $bucket;

    try {
        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
        ]);

        $client->putObject([
            'Bucket' => $bucket,
            'Key' => 'admin/research/images/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}

function upload_research_pdf($request) {
    if (empty($_FILES['file'])) return new WP_Error('no_file', 'No file.', ['status' => 400]);

    $file = $_FILES['file'];
    if ($file['type'] !== 'application/pdf') {
        return new WP_Error('invalid_type', 'Only PDF allowed.', ['status' => 415]);
    }
    if ($file['size'] > 10*1024*1024) {
        return new WP_Error('too_large', 'Max 10MB allowed.', ['status' => 413]);
    }

    $fileName = time() . '-' . sanitize_file_name($file['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/research/pdfs';

    global $accountId, $accessKey, $secretKey, $bucket;

    try {
        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
        ]);

        $client->putObject([
            'Bucket' => $bucket,
            'Key' => 'admin/research/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => 'application/pdf',
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}