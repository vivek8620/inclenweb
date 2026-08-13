<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-team',
        'Publications Manager',
        'Publications',
        'manage_options',
        'publications-manager',
        'publications_manager_page'
    );
});
 
add_action('rest_api_init', function () {
 
    register_rest_route('publications/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_publications',
        'permission_callback' => '__return_true',
    ]);
 
    register_rest_route('publications/v1', '/add', [
        'methods'             => 'POST',
        'callback'            => 'add_publication',
        'permission_callback' => '__return_true',
    ]);
 
    register_rest_route('publications/v1', '/update/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'update_publication',
        'permission_callback' => '__return_true',
    ]);
 
    register_rest_route('publications/v1', '/delete/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_publication',
        'permission_callback' => '__return_true',
    ]);
 
    register_rest_route('publications/v1', '/upload-cover', [
        'methods'             => 'POST',
        'callback'            => 'upload_cover_image_to_r2',
        'permission_callback' => '__return_true',
    ]);
 
    register_rest_route('publications/v1', '/upload-pdf', [
        'methods'             => 'POST',
        'callback'            => 'upload_pdf_to_r2',
        'permission_callback' => '__return_true',
    ]);
});
 
function publications_manager_page() {
    $nonce = wp_create_nonce('publications_manager_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Publications Manager</h1>
        <hr class="wp-header-end">
 
        <!-- Notices -->
        <div id="pub-notice" style="display:none; margin:10px 0;"></div>
 
        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px;">
            <h2 id="form-heading">Add New Publication</h2>
 
            <form id="publication-form" novalidate>
                <input type="hidden" id="publication_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">
 
                <table class="form-table">
 
                    <!-- Title -->
                    <tr>
                        <th><label for="pub_title">Publication Title <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="pub_title" class="regular-text" style="width:100%;"
                                   placeholder="e.g. Advances in Machine Learning" required>
                            <p class="description error-msg" id="err_title" style="color:red;display:none;">Title is required.</p>
                        </td>
                    </tr>
 
                    <!-- Year -->
                    <tr>
                        <th><label for="pub_year">Year <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="pub_year" class="regular-text" style="width:150px;"
                                   placeholder="e.g. 2024" maxlength="4" required>
                            <p class="description error-msg" id="err_year" style="color:red;display:none;">Please enter a valid 4-digit year.</p>
                        </td>
                    </tr>
 
                    <!-- Journal -->
                    <tr>
                        <th><label for="pub_journal">Journal / Conference</label></th>
                        <td>
                            <input type="text" id="pub_journal" class="regular-text" style="width:100%;"
                                   placeholder="e.g. Nature Communications">
                        </td>
                    </tr>
 
                    <!-- Authors -->
                    <tr>
                        <th><label for="pub_authors">Authors <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="pub_authors" class="regular-text" style="width:100%;"
                                   placeholder="e.g. Smith J., Doe A., Kumar R." required>
                            <p class="description error-msg" id="err_authors" style="color:red;display:none;">Authors field is required.</p>
                        </td>
                    </tr>
 
                    <!-- Abstract -->
                    <tr>
                        <th><label for="pub_abstract">Abstract</label></th>
                        <td>
                            <textarea id="pub_abstract" class="large-text" rows="6" style="width:100%;"
                                      placeholder="Enter the abstract of the publication here..."></textarea>
                        </td>
                    </tr>
 
                    <!-- Suggested Citation -->
                    <tr>
                        <th><label for="pub_citation">Suggested Citation</label></th>
                        <td>
                            <textarea id="pub_citation" class="large-text" rows="3" style="width:100%;"
                                      placeholder="e.g. Smith, J. et al. (2024). Title. Journal, 10(2), 45–60."></textarea>
                        </td>
                    </tr>
 
                    <!-- Cover Image -->
                    <tr>
                        <th><label>Cover Image</label></th>
                        <td>
                            <input type="file" id="cover-image-upload" accept="image/jpeg,image/png,image/webp,image/gif">
                            <p class="description">Accepted formats: JPG, PNG, WEBP, GIF. Max size: <strong>5MB</strong>.</p>
                            <input type="hidden" id="cover-image-url" value="">
                            <div id="cover-image-preview" style="margin-top:10px;"></div>
                            <div id="cover-upload-progress" style="display:none; margin-top:6px;">
                                <span class="spinner is-active" style="float:none;"></span>
                                <em>Uploading cover image...</em>
                            </div>
                            <p class="error-msg" id="err_cover_image" style="color:red;display:none;"></p>
                        </td>
                    </tr>
 
                    <!-- PDF Upload -->
                    <tr>
                        <th><label>PDF File</label></th>
                        <td>
                            <input type="file" id="pdf-upload" accept="application/pdf">
                            <p class="description">Accepted format: PDF only. Max size: <strong>20MB</strong>.</p>
                            <input type="hidden" id="pdf-url" value="">
                            <div id="pdf-upload-progress" style="display:none; margin-top:6px;">
                                <span class="spinner is-active" style="float:none;"></span>
                                <em>Uploading PDF...</em>
                            </div>
                            <div id="pdf-preview" style="margin-top:10px;"></div>
                            <p class="error-msg" id="err_pdf" style="color:red;display:none;"></p>
                        </td>
                    </tr>
 
                </table>
 
                <p style="margin-top:25px;">
                    <button type="button" id="save-btn" class="button button-primary button-large">Add Publication</button>
                    <button type="button" id="cancel-btn" class="button button-large" style="display:none; margin-left:8px;">Cancel Edit</button>
                </p>
            </form>
        </div>
 

        <h2>All Publications</h2>
        <table id="publications-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Title</th>
                    <th style="width:70px;">Year</th>
                    <th>Journal</th>
                    <th>Authors</th>
                    <th style="width:90px;">Cover</th>
                    <th style="width:70px;">PDF</th>
                    <th style="width:130px;">Created</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="9" style="text-align:center;padding:40px;">
                    <span class="spinner is-active" style="float:none;"></span> Loading...
                </td></tr>
            </tbody>
        </table>
    </div>
 
    <script>
    jQuery(document).ready(function ($) {
 
        let editId = 0;
        const MAX_IMAGE_SIZE = 5 * 1024 * 1024;   // 5 MB
        const MAX_PDF_SIZE   = 20 * 1024 * 1024;  // 20 MB
        const ALLOWED_IMAGE_TYPES = ['image/jpeg','image/png','image/webp','image/gif'];
 
      
        function showNotice(msg, type) {
            const cls = type === 'error' ? 'notice-error' : 'notice-success';
            $('#pub-notice')
                .removeClass('notice-error notice-success notice')
                .addClass('notice ' + cls)
                .html('<p>' + msg + '</p>')
                .show();
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
 
            const title = $('#pub_title').val().trim();
            if (!title) {
                showFieldError('err_title', 'Publication Title is required.');
                valid = false;
            }
 
            const year = $('#pub_year').val().trim();
            if (!year) {
                showFieldError('err_year', 'Year is required.');
                valid = false;
            } else if (!/^\d{4}$/.test(year)) {
                showFieldError('err_year', 'Year must be a valid 4-digit number (e.g. 2024).');
                valid = false;
            } else {
                const y = parseInt(year);
                if (y < 1900 || y > new Date().getFullYear() + 1) {
                    showFieldError('err_year', 'Please enter a realistic year between 1900 and ' + (new Date().getFullYear() + 1) + '.');
                    valid = false;
                }
            }
 
            const authors = $('#pub_authors').val().trim();
            if (!authors) {
                showFieldError('err_authors', 'Authors field is required.');
                valid = false;
            }
 
            return valid;
        }
 

        function loadPublications() {
            $.get('<?php echo rest_url('publications/v1/all'); ?>', function (data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="9" style="text-align:center;padding:50px;">No publications found.</td></tr>';
                } else {
                    data.forEach(function (p) {
                        const cover = p.cover_image
                            ? `<img src="${p.cover_image}" style="max-width:70px;max-height:55px;object-fit:cover;border-radius:4px;" alt="">`
                            : '<em style="color:#999;">—</em>';
                        const pdf = p.pdf_url
                            ? `<a href="${p.pdf_url}" target="_blank" class="button button-small">View PDF</a>`
                            : '<em style="color:#999;">—</em>';
                        html += `
                            <tr>
                                <td>${p.id}</td>
                                <td><strong>${p.title}</strong></td>
                                <td>${p.year || '—'}</td>
                                <td>${p.journal || '—'}</td>
                                <td style="font-size:12px;">${p.authors || '—'}</td>
                                <td>${cover}</td>
                                <td>${pdf}</td>
                                <td style="font-size:12px;">${p.created_at}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${p.id}">Edit</button>
                                    <button class="button button-small delete-btn" data-id="${p.id}" style="color:#b32d2e;">Delete</button>
                                </td>
                            </tr>`;
                    });
                }
                $('#publications-table tbody').html(html);
            }).fail(function () {
                $('#publications-table tbody').html(
                    '<tr><td colspan="9" style="color:red;text-align:center;padding:30px;">Failed to load publications. Please refresh.</td></tr>'
                );
            });
        }
 
        
        $('#cover-image-upload').on('change', function () {
            $('#err_cover_image').hide().text('');
            const file = this.files[0];
            if (!file) return;
 
           
            if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
                showFieldError('err_cover_image', 'Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.');
                $(this).val('');
                return;
            }
 

            if (file.size > MAX_IMAGE_SIZE) {
                showFieldError('err_cover_image', 'Image exceeds 5MB limit. Please choose a smaller file.');
                $(this).val('');
                return;
            }
 
            const formData = new FormData();
            formData.append('file', file);
 
            $('#cover-upload-progress').show();
            $('#cover-image-preview').html('');
 
            $.ajax({
                url: '<?php echo rest_url('publications/v1/upload-cover'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#cover-upload-progress').hide();
                    if (res && res.url) {
                        $('#cover-image-url').val(res.url);
                        $('#cover-image-preview').html(
                            `<img src="${res.url}" style="max-height:160px;border-radius:6px;border:1px solid #ddd;margin-top:6px;">`
                        );
                    } else {
                        showFieldError('err_cover_image', 'Upload failed: ' + (res.error || 'Unknown error.'));
                    }
                },
                error: function (xhr) {
                    $('#cover-upload-progress').hide();
                    let msg = 'Cover image upload failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ' ' + xhr.responseJSON.message;
                    } else if (xhr.status === 413) {
                        msg = 'File too large. Server rejected the upload.';
                    } else if (xhr.status === 415) {
                        msg = 'Unsupported media type.';
                    } else if (xhr.status === 500) {
                        msg = 'Server error during upload. Please try again.';
                    }
                    showFieldError('err_cover_image', msg);
                }
            });
        });
 
        
        $('#pdf-upload').on('change', function () {
            $('#err_pdf').hide().text('');
            const file = this.files[0];
            if (!file) return;
 
    
            if (file.type !== 'application/pdf') {
                showFieldError('err_pdf', 'Invalid file type. Only PDF files are accepted.');
                $(this).val('');
                return;
            }
 
            
            if (file.size > MAX_PDF_SIZE) {
                showFieldError('err_pdf', 'PDF exceeds 20MB limit. Please upload a smaller file.');
                $(this).val('');
                return;
            }
 
            const formData = new FormData();
            formData.append('file', file);
 
            $('#pdf-upload-progress').show();
            $('#pdf-preview').html('');
 
            $.ajax({
                url: '<?php echo rest_url('publications/v1/upload-pdf'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#pdf-upload-progress').hide();
                    if (res && res.url) {
                        $('#pdf-url').val(res.url);
                        const fileName = file.name;
                        $('#pdf-preview').html(
                            `<div style="margin-top:6px;padding:8px 12px;background:#f0f6fc;border:1px solid #b3d4f0;border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
                                <span dashicons dashicons-pdf style="color:#c0392b;">📄</span>
                                <a href="${res.url}" target="_blank">${fileName}</a>
                                <em style="color:#888;font-size:12px;">(uploaded)</em>
                            </div>`
                        );
                    } else {
                        showFieldError('err_pdf', 'Upload failed: ' + (res.error || 'Unknown error.'));
                    }
                },
                error: function (xhr) {
                    $('#pdf-upload-progress').hide();
                    let msg = 'PDF upload failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ' ' + xhr.responseJSON.message;
                    } else if (xhr.status === 413) {
                        msg = 'File too large. Server rejected the upload.';
                    } else if (xhr.status === 415) {
                        msg = 'Unsupported file type. Only PDFs are allowed on the server.';
                    } else if (xhr.status === 500) {
                        msg = 'Server error during upload. Please try again later.';
                    } else if (xhr.status === 0) {
                        msg = 'Network error. Please check your connection.';
                    }
                    showFieldError('err_pdf', msg);
                }
            });
        });
 
        // ── Save (Add or Update) ──────────────────────────────────
        $('#save-btn').on('click', function () {
            if (!validateForm()) return;
 
            const btn = $(this);
            btn.prop('disabled', true).text(editId ? 'Updating...' : 'Saving...');
 
            const data = {
                title:              $('#pub_title').val().trim(),
                year:               $('#pub_year').val().trim(),
                journal:            $('#pub_journal').val().trim(),
                authors:            $('#pub_authors').val().trim(),
                abstract:           $('#pub_abstract').val().trim(),
                suggested_citation: $('#pub_citation').val().trim(),
                cover_image:        $('#cover-image-url').val(),
                pdf_url:            $('#pdf-url').val(),
                nonce:              $('#nonce').val()
            };
 
            const url = editId
                ? '<?php echo rest_url('publications/v1/update/'); ?>' + editId
                : '<?php echo rest_url('publications/v1/add'); ?>';
 
            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function (res) {
                    if (res && res.status) {
                        showNotice(editId ? 'Publication updated successfully!' : 'Publication added successfully!', 'success');
                        resetForm();
                        loadPublications();
                    } else {
                        showNotice('Unexpected response from server.', 'error');
                    }
                },
                error: function (xhr) {
                    let msg = 'Failed to save publication.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showNotice(msg, 'error');
                },
                complete: function () {
                    btn.prop('disabled', false).text(editId ? 'Update Publication' : 'Add Publication');
                }
            });
        });
 
        // ── Edit ──────────────────────────────────────────────────
        $(document).on('click', '.edit-btn', function () {
            editId = $(this).data('id');
            clearErrors();
            $('#form-heading').text('Edit Publication');
            $('#save-btn').text('Update Publication');
            $('#cancel-btn').show();
 
            $.get('<?php echo rest_url('publications/v1/all'); ?>', function (publications) {
                const item = publications.find(p => p.id == editId);
                if (!item) {
                    showNotice('Could not load publication data.', 'error');
                    return;
                }
 
                $('#pub_title').val(item.title || '');
                $('#pub_year').val(item.year || '');
                $('#pub_journal').val(item.journal || '');
                $('#pub_authors').val(item.authors || '');
                $('#pub_abstract').val(item.abstract || '');
                $('#pub_citation').val(item.suggested_citation || '');
 
                // Cover image
                $('#cover-image-url').val(item.cover_image || '');
                $('#cover-image-preview').html(
                    item.cover_image
                        ? `<img src="${item.cover_image}" style="max-height:160px;border-radius:6px;border:1px solid #ddd;margin-top:6px;">`
                        : ''
                );
 
                // PDF
                $('#pdf-url').val(item.pdf_url || '');
                $('#pdf-preview').html(
                    item.pdf_url
                        ? `<div style="margin-top:6px;padding:8px 12px;background:#f0f6fc;border:1px solid #b3d4f0;border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
                                📄 <a href="${item.pdf_url}" target="_blank">Current PDF</a>
                                <em style="color:#888;font-size:12px;">(existing)</em>
                           </div>`
                        : ''
                );
 
                $('html, body').animate({ scrollTop: $('#publication-form').offset().top - 50 }, 300);
            }).fail(function () {
                showNotice('Failed to load publication for editing.', 'error');
            });
        });
 
        // ── Cancel ────────────────────────────────────────────────
        $('#cancel-btn').on('click', resetForm);
 
        function resetForm() {
            editId = 0;
            clearErrors();
            $('#publication-form')[0].reset();
            $('#cover-image-url').val('');
            $('#pdf-url').val('');
            $('#cover-image-preview').html('');
            $('#pdf-preview').html('');
            $('#cover-upload-progress').hide();
            $('#pdf-upload-progress').hide();
            $('#form-heading').text('Add New Publication');
            $('#save-btn').text('Add Publication');
            $('#cancel-btn').hide();
        }
 
        // ── Delete ────────────────────────────────────────────────
        $(document).on('click', '.delete-btn', function () {
            if (!confirm('Are you sure you want to delete this publication? This cannot be undone.')) return;
 
            const id = $(this).data('id');
            const btn = $(this);
            btn.prop('disabled', true).text('Deleting...');
 
            $.ajax({
                url: '<?php echo rest_url('publications/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function () {
                    showNotice('Publication deleted.', 'success');
                    loadPublications();
                },
                error: function (xhr) {
                    let msg = 'Failed to delete publication.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg += ' ' + xhr.responseJSON.message;
                    showNotice(msg, 'error');
                    btn.prop('disabled', false).text('Delete');
                }
            });
        });
 
        // Initial load
        loadPublications();
    });
    </script>
    <?php
}
 

function get_all_publications() {
    global $wpdb;
    $table = $wpdb->prefix . 'publications';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    if ($wpdb->last_error) {
        return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
    }
    return $results;
}
 
function add_publication($request) {
    global $wpdb;
    $table  = $wpdb->prefix . 'publications';
    $params = $request->get_json_params();
 
    if (empty($params['title'])) {
        return new WP_Error('missing_title', 'Publication title is required.', ['status' => 400]);
    }
    if (empty($params['year']) || !preg_match('/^\d{4}$/', $params['year'])) {
        return new WP_Error('invalid_year', 'A valid 4-digit year is required.', ['status' => 400]);
    }
    if (empty($params['authors'])) {
        return new WP_Error('missing_authors', 'Authors field is required.', ['status' => 400]);
    }
 
    $inserted = $wpdb->insert($table, [
        'title'              => sanitize_text_field($params['title']),
        'year'               => sanitize_text_field($params['year']),
        'journal'            => sanitize_text_field($params['journal'] ?? ''),
        'authors'            => sanitize_textarea_field($params['authors'] ?? ''),
        'abstract'           => sanitize_textarea_field($params['abstract'] ?? ''),
        'suggested_citation' => sanitize_textarea_field($params['suggested_citation'] ?? ''),
        'cover_image'        => esc_url_raw($params['cover_image'] ?? ''),
        'pdf_url'            => esc_url_raw($params['pdf_url'] ?? ''),
        'created_at'         => current_time('mysql'),
    ]);
 
    if ($inserted === false) {
        return new WP_Error('db_insert_error', 'Database insert failed: ' . $wpdb->last_error, ['status' => 500]);
    }
 
    return ['status' => 'success', 'id' => $wpdb->insert_id];
}
 
function update_publication($request) {
    global $wpdb;
    $table  = $wpdb->prefix . 'publications';
    $params = $request->get_json_params();
    $id     = absint($request['id']);
 
    if (!$id) {
        return new WP_Error('invalid_id', 'Invalid publication ID.', ['status' => 400]);
    }
    if (empty($params['title'])) {
        return new WP_Error('missing_title', 'Publication title is required.', ['status' => 400]);
    }
    if (empty($params['year']) || !preg_match('/^\d{4}$/', $params['year'])) {
        return new WP_Error('invalid_year', 'A valid 4-digit year is required.', ['status' => 400]);
    }
    if (empty($params['authors'])) {
        return new WP_Error('missing_authors', 'Authors field is required.', ['status' => 400]);
    }
 
    $updated = $wpdb->update($table, [
        'title'              => sanitize_text_field($params['title']),
        'year'               => sanitize_text_field($params['year']),
        'journal'            => sanitize_text_field($params['journal'] ?? ''),
        'authors'            => sanitize_textarea_field($params['authors'] ?? ''),
        'abstract'           => sanitize_textarea_field($params['abstract'] ?? ''),
        'suggested_citation' => sanitize_textarea_field($params['suggested_citation'] ?? ''),
        'cover_image'        => esc_url_raw($params['cover_image'] ?? ''),
        'pdf_url'            => esc_url_raw($params['pdf_url'] ?? ''),
    ], ['id' => $id]);
 
    if ($updated === false) {
        return new WP_Error('db_update_error', 'Database update failed: ' . $wpdb->last_error, ['status' => 500]);
    }
 
    return ['status' => 'updated', 'rows_affected' => $updated];
}
 
function delete_publication($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'publications';
    $id    = absint($request['id']);
 
    if (!$id) {
        return new WP_Error('invalid_id', 'Invalid publication ID.', ['status' => 400]);
    }
 
    $deleted = $wpdb->delete($table, ['id' => $id]);
 
    if ($deleted === false) {
        return new WP_Error('db_delete_error', 'Database delete failed: ' . $wpdb->last_error, ['status' => 500]);
    }
 
    return ['status' => 'deleted'];
}
 

function upload_cover_image_to_r2($request) {
    global $accountId, $accessKey, $secretKey, $bucket;
 
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file was uploaded.', ['status' => 400]);
    }
 
    $file     = $_FILES['file'];
    $fileTmp  = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];
 
    // Validate MIME type (server-side)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($fileType, $allowedTypes)) {
        return new WP_Error('invalid_type', 'Only JPG, PNG, WEBP, and GIF images are allowed.', ['status' => 415]);
    }
 
    // Validate file size: 5 MB
    if ($fileSize > 5 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'Cover image must not exceed 5MB.', ['status' => 413]);
    }
 
    // Validate it is a real image
    $imageInfo = @getimagesize($fileTmp);
    if ($imageInfo === false) {
        return new WP_Error('invalid_image', 'Uploaded file is not a valid image.', ['status' => 422]);
    }
 
    $fileName      = time() . '-' . sanitize_file_name($file['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/publications/covers';
 
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
            'Key'         => 'admin/publications/covers/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $fileType,
        ]);
 
        return ['url' => $publicUrlBase . '/' . $fileName];
 
    } catch (\Aws\Exception\AwsException $e) {
        return new WP_Error('r2_aws_error', 'R2 storage error: ' . $e->getAwsErrorMessage(), ['status' => 500]);
    } catch (Exception $e) {
        return new WP_Error('upload_failed', 'Upload exception: ' . $e->getMessage(), ['status' => 500]);
    }
}

function upload_pdf_to_r2($request) {
    global $accountId, $accessKey, $secretKey, $bucket;
 
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file was uploaded.', ['status' => 400]);
    }
 
    $file     = $_FILES['file'];
    $fileTmp  = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];
 
    // Validate MIME type
    if ($fileType !== 'application/pdf') {
        return new WP_Error('invalid_type', 'Only PDF files are allowed.', ['status' => 415]);
    }
 
    // Validate file size: 20 MB
    if ($fileSize > 20 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'PDF must not exceed 20MB.', ['status' => 413]);
    }
 
    // Validate PDF magic bytes (%PDF-)
    $handle = fopen($fileTmp, 'rb');
    if ($handle === false) {
        return new WP_Error('file_read_error', 'Could not read uploaded file.', ['status' => 500]);
    }
    $header = fread($handle, 5);
    fclose($handle);
 
    if ($header !== '%PDF-') {
        return new WP_Error('invalid_pdf', 'Uploaded file does not appear to be a valid PDF.', ['status' => 422]);
    }
 
    $fileName      = time() . '-' . sanitize_file_name($file['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/publications/pdfs';
 
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
            'Key'         => 'admin/publications/pdfs/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => 'application/pdf',
        ]);
 
        return ['url' => $publicUrlBase . '/' . $fileName];
 
    } catch (\Aws\Exception\AwsException $e) {
        return new WP_Error('r2_aws_error', 'R2 storage error: ' . $e->getAwsErrorMessage(), ['status' => 500]);
    } catch (Exception $e) {
        return new WP_Error('upload_failed', 'Upload exception: ' . $e->getMessage(), ['status' => 500]);
    }
}