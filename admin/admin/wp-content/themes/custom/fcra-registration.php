<?php
add_action('admin_menu', function () {
    add_menu_page(
        'FCRA & Registration',
        'FCRA & Registration',
        'manage_options',
        'fcra-registration',
        'fcra_registration_page',
        'dashicons-clipboard',
        34
    );
});

// REST API setup for FCRA & Registration
add_action('rest_api_init', function () {
    register_rest_route('fcra-registration/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_fcra_registrations',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fcra-registration/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_fcra_registration',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fcra-registration/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_fcra_registration',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fcra-registration/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_fcra_registration',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fcra-registration/v1', '/upload-file', [
        'methods'  => 'POST',
        'callback' => 'upload_fcra_file_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_fcra_file_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-fcra-' . basename($_FILES['file']['name']);
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

function get_all_fcra_registrations() {
    global $wpdb;
    $table = $wpdb->prefix . 'fcra_registration';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_fcra_registration($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fcra_registration';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'tag'           => sanitize_text_field($params['tag']),
        'heading'       => sanitize_text_field($params['heading']),
        'declared_year' => sanitize_text_field($params['declared_year']),
        'pdf_url'       => esc_url_raw($params['pdf_url']),
        'pdf_size'      => sanitize_text_field($params['pdf_size'])
    ]);

    return ['status' => 'success'];
}

function update_fcra_registration($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fcra_registration';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'tag'           => sanitize_text_field($params['tag']),
        'heading'       => sanitize_text_field($params['heading']),
        'declared_year' => sanitize_text_field($params['declared_year']),
        'pdf_url'       => esc_url_raw($params['pdf_url']),
        'pdf_size'      => sanitize_text_field($params['pdf_size'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_fcra_registration($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fcra_registration';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function fcra_registration_page() { ?>
    <div class="wrap">
        <h1>FCRA & Registration Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Document</h2>

            <table class="form-table">
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="fcra_heading" class="regular-text" required placeholder="e.g. FCRA Receipt">
                        <span id="fcra_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Tag</th>
                    <td>
                        <input type="text" id="fcra_tag" class="regular-text" placeholder="e.g. Important">
                    </td>
                </tr>
                <tr>
                    <th>Declared Year</th>
                    <td>
                        <input type="text" id="fcra_declared_year" class="regular-text" placeholder="e.g. 2024-25">
                    </td>
                </tr>
                <tr>
                    <th>PDF Document</th>
                    <td>
                        <input type="file" id="fcra_pdf_file" accept=".pdf">
                        <input type="hidden" id="fcra_pdf_url">
                        <div style="margin-top:10px;">
                            <label>PDF Size (auto-calculated or manual): </label>
                            <input type="text" id="fcra_pdf_size" class="small-text" placeholder="e.g. 1.2 MB">
                        </div>
                        <br>
                        <a id="fcra_preview_pdf" href="#" target="_blank" style="display:none; color:#2271b1; text-decoration:none; font-weight:bold;">📄 View Uploaded PDF</a>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveFcraRegistration()">Save Document</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Documents</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Heading</th>
                        <th>Tag</th>
                        <th>Declared Year</th>
                        <th>PDF</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="fcraList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const FCRA_API_BASE = "<?php echo site_url('/wp-json/fcra-registration/v1'); ?>";
    let fcraEditingId = null;

    function loadFcraRegistrations() {
        fetch(FCRA_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td><strong>${item.heading}</strong></td>
                    <td>${item.tag ? '<span style="background:#e0f0ff;color:#005cbf;padding:3px 8px;border-radius:12px;font-size:12px;">' + item.tag + '</span>' : ''}</td>
                    <td>${item.declared_year}</td>
                    <td>${item.pdf_url ? '<a href="' + item.pdf_url + '" target="_blank">View PDF</a> (' + item.pdf_size + ')' : ''}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editFcraRegistration(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteFcraRegistration(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("fcraList").innerHTML = html;
        });
    }

    function clearFcraErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveFcraRegistration() {
        clearFcraErrors();
        
        const headingVal = document.getElementById("fcra_heading").value.trim();
        const tagVal = document.getElementById("fcra_tag").value.trim();
        const declaredYearVal = document.getElementById("fcra_declared_year").value.trim();
        const pdfUrlVal = document.getElementById("fcra_pdf_url").value;
        const pdfSizeVal = document.getElementById("fcra_pdf_size").value.trim();
        
        let isValid = true;
        if (!headingVal) {
            document.getElementById("fcra_heading_error").innerText = "Heading is required.";
            document.getElementById("fcra_heading").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            heading: headingVal,
            tag: tagVal,
            declared_year: declaredYearVal,
            pdf_url: pdfUrlVal,
            pdf_size: pdfSizeVal
        };
        
        let url = FCRA_API_BASE + "/add";
        if (fcraEditingId) url = FCRA_API_BASE + "/update/" + fcraEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            fcraEditingId = null; 
            document.getElementById("fcra_heading").value = '';
            document.getElementById("fcra_tag").value = '';
            document.getElementById("fcra_declared_year").value = '';
            document.getElementById("fcra_pdf_url").value = '';
            document.getElementById("fcra_pdf_size").value = '';
            document.getElementById("fcra_preview_pdf").style.display = "none";
            loadFcraRegistrations(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editFcraRegistration(item) {
        clearFcraErrors();
        fcraEditingId = item.id;
        document.getElementById("fcra_heading").value = item.heading;
        document.getElementById("fcra_tag").value = item.tag;
        document.getElementById("fcra_declared_year").value = item.declared_year;
        
        document.getElementById("fcra_pdf_url").value = item.pdf_url;
        document.getElementById("fcra_pdf_size").value = item.pdf_size;
        if(item.pdf_url) {
            document.getElementById("fcra_preview_pdf").href = item.pdf_url;
            document.getElementById("fcra_preview_pdf").style.display = "inline-block";
        } else {
            document.getElementById("fcra_preview_pdf").style.display = "none";
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteFcraRegistration(id) {
        if (!confirm("Are you sure you want to delete this Document?")) return;
        fetch(FCRA_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadFcraRegistrations());
    }

    // PDF Upload
    document.getElementById("fcra_pdf_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(FCRA_API_BASE + "/upload-file", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("fcra_pdf_url").value = data.url;
                document.getElementById("fcra_pdf_size").value = data.size; // Automatically set size
                document.getElementById("fcra_preview_pdf").href = data.url;
                document.getElementById("fcra_preview_pdf").style.display = "inline-block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadFcraRegistrations);
    </script>
<?php }
