<?php
add_action('admin_menu', function () {
    add_menu_page(
        'Newsletters',
        'Newsletters',
        'manage_options',
        'newsletters',
        'newsletters_page',
        'dashicons-media-document',
        32
    );
});

// REST API setup for Newsletters
add_action('rest_api_init', function () {
    register_rest_route('newsletters/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_newsletters',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('newsletters/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_newsletter',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('newsletters/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_newsletter',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('newsletters/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_newsletter',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('newsletters/v1', '/upload-file', [
        'methods'  => 'POST',
        'callback' => 'upload_newsletter_file_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_newsletter_file_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-newsletter-' . basename($_FILES['file']['name']);
    $fileSize = $_FILES['file']['size']; // in bytes
    
    // Calculate human-readable size
    $sizeMB = round($fileSize / 1048576, 1);
    $sizeStr = $sizeMB > 0 ? $sizeMB . ' MB' : round($fileSize / 1024, 1) . ' KB';

    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin";
    
    try {
        $client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket' => $bucket,
            'Key' => 'admin/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return [
            'url' => $publicUrlBase . '/' . $fileName,
            'size' => $sizeStr
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function get_all_newsletters() {
    global $wpdb;
    $table = $wpdb->prefix . 'newsletters';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_newsletter($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'newsletters';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'heading'     => sanitize_text_field($params['heading']),
        'cover_image' => esc_url_raw($params['cover_image']),
        'highlights'  => wp_kses_post($params['highlights']),
        'pdf_url'     => esc_url_raw($params['pdf_url']),
        'pdf_size'    => sanitize_text_field($params['pdf_size']),
        'year'        => sanitize_text_field($params['year']),
        'is_featured' => !empty($params['is_featured']) ? 1 : 0
    ]);

    return ['status' => 'success'];
}

function update_newsletter($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'newsletters';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'heading'     => sanitize_text_field($params['heading']),
        'cover_image' => esc_url_raw($params['cover_image']),
        'highlights'  => wp_kses_post($params['highlights']),
        'pdf_url'     => esc_url_raw($params['pdf_url']),
        'pdf_size'    => sanitize_text_field($params['pdf_size']),
        'year'        => sanitize_text_field($params['year']),
        'is_featured' => !empty($params['is_featured']) ? 1 : 0
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_newsletter($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'newsletters';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function newsletters_page() { ?>
    <div class="wrap">
        <h1>Newsletters Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Newsletter</h2>

            <table class="form-table">
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="nl_heading" class="regular-text" required placeholder="e.g. INCLEN Newsletter 2024-25">
                        <span id="nl_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Financial Year / Period</th>
                    <td>
                        <input type="text" id="nl_year" class="regular-text" placeholder="e.g. 2024-25">
                    </td>
                </tr>
                <tr>
                    <th>Featured Document</th>
                    <td>
                        <label>
                            <input type="checkbox" id="nl_is_featured"> Show on top as Featured
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Cover Image</th>
                    <td>
                        <input type="file" id="nl_cover_image_file" accept="image/*">
                        <input type="hidden" id="nl_cover_image">
                        <br><br>
                        <img id="nl_preview_image" style="max-width:150px;display:none;border:1px solid #ddd;padding:3px;border-radius:4px;">
                    </td>
                </tr>
                <tr>
                    <th>PDF Document</th>
                    <td>
                        <input type="file" id="nl_pdf_file" accept=".pdf">
                        <input type="hidden" id="nl_pdf_url">
                        <div style="margin-top:10px;">
                            <label>PDF Size (auto-calculated or manual): </label>
                            <input type="text" id="nl_pdf_size" class="small-text" placeholder="e.g. 3.2 MB">
                        </div>
                        <br>
                        <a id="nl_preview_pdf" href="#" target="_blank" style="display:none; color:#2271b1; text-decoration:none; font-weight:bold;">📄 View Uploaded PDF</a>
                    </td>
                </tr>
                <tr>
                    <th>Highlights (Bullet Points)</th>
                    <td>
                        <p class="description">Use the bullet point list tool to create highlights.</p>
                        <?php wp_editor('', 'nl_highlights_editor', array('media_buttons' => false)); ?>
                        <span id="nl_highlights_error" class="error-msg"></span>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveNewsletter()">Save Newsletter</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Newsletters</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Heading</th>
                        <th>Year</th>
                        <th>Featured</th>
                        <th>PDF</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="newslettersList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const NL_API_BASE = "<?php echo site_url('/wp-json/newsletters/v1'); ?>";
    let nlEditingId = null;

    function loadNewsletters() {
        fetch(NL_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.cover_image ? '<img src="' + item.cover_image + '" width="60" style="border-radius:4px;">' : ''}</td>
                    <td><strong>${item.heading}</strong></td>
                    <td>${item.year}</td>
                    <td>${item.is_featured == 1 ? '<span style="background:#e6f4ea;color:#1e8e3e;padding:3px 8px;border-radius:12px;font-size:12px;">Featured</span>' : ''}</td>
                    <td>${item.pdf_url ? '<a href="' + item.pdf_url + '" target="_blank">View PDF</a> (' + item.pdf_size + ')' : ''}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editNewsletter(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteNewsletter(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("newslettersList").innerHTML = html;
        });
    }

    function clearNlErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveNewsletter() {
        clearNlErrors();
        
        const headingVal = document.getElementById("nl_heading").value.trim();
        const yearVal = document.getElementById("nl_year").value.trim();
        const isFeaturedVal = document.getElementById("nl_is_featured").checked;
        const coverImageVal = document.getElementById("nl_cover_image").value;
        const pdfUrlVal = document.getElementById("nl_pdf_url").value;
        const pdfSizeVal = document.getElementById("nl_pdf_size").value.trim();
        const highlightsVal = tinymce.get("nl_highlights_editor").getContent().trim();
        
        let isValid = true;
        if (!headingVal) {
            document.getElementById("nl_heading_error").innerText = "Heading is required.";
            document.getElementById("nl_heading").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            heading: headingVal,
            year: yearVal,
            is_featured: isFeaturedVal,
            cover_image: coverImageVal,
            pdf_url: pdfUrlVal,
            pdf_size: pdfSizeVal,
            highlights: highlightsVal
        };
        
        let url = NL_API_BASE + "/add";
        if (nlEditingId) url = NL_API_BASE + "/update/" + nlEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            nlEditingId = null; 
            document.getElementById("nl_heading").value = '';
            document.getElementById("nl_year").value = '';
            document.getElementById("nl_is_featured").checked = false;
            document.getElementById("nl_cover_image").value = '';
            document.getElementById("nl_preview_image").style.display = "none";
            document.getElementById("nl_pdf_url").value = '';
            document.getElementById("nl_pdf_size").value = '';
            document.getElementById("nl_preview_pdf").style.display = "none";
            tinymce.get("nl_highlights_editor").setContent('');
            loadNewsletters(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editNewsletter(item) {
        clearNlErrors();
        nlEditingId = item.id;
        document.getElementById("nl_heading").value = item.heading;
        document.getElementById("nl_year").value = item.year;
        document.getElementById("nl_is_featured").checked = (item.is_featured == 1);
        
        document.getElementById("nl_cover_image").value = item.cover_image;
        if(item.cover_image) {
            document.getElementById("nl_preview_image").src = item.cover_image;
            document.getElementById("nl_preview_image").style.display = "block";
        } else {
            document.getElementById("nl_preview_image").style.display = "none";
        }

        document.getElementById("nl_pdf_url").value = item.pdf_url;
        document.getElementById("nl_pdf_size").value = item.pdf_size;
        if(item.pdf_url) {
            document.getElementById("nl_preview_pdf").href = item.pdf_url;
            document.getElementById("nl_preview_pdf").style.display = "inline-block";
        } else {
            document.getElementById("nl_preview_pdf").style.display = "none";
        }
        
        tinymce.get("nl_highlights_editor").setContent(item.highlights);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteNewsletter(id) {
        if (!confirm("Are you sure you want to delete this Newsletter?")) return;
        fetch(NL_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadNewsletters());
    }

    // Cover Image Upload
    document.getElementById("nl_cover_image_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(NL_API_BASE + "/upload-file", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("nl_cover_image").value = data.url;
                document.getElementById("nl_preview_image").src = data.url;
                document.getElementById("nl_preview_image").style.display = "block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    // PDF Upload
    document.getElementById("nl_pdf_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(NL_API_BASE + "/upload-file", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("nl_pdf_url").value = data.url;
                document.getElementById("nl_pdf_size").value = data.size; // Automatically set size
                document.getElementById("nl_preview_pdf").href = data.url;
                document.getElementById("nl_preview_pdf").style.display = "inline-block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadNewsletters);
    </script>
<?php }
