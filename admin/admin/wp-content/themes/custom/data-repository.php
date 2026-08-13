<?php
add_action('admin_menu', function () {
    add_menu_page(
        'Data Repository',
        'Data Repository',
        'manage_options',
        'data-repository',
        'data_repository_page',
        'dashicons-analytics',
        36
    );
});

// REST API setup for Data Repository
add_action('rest_api_init', function () {
    register_rest_route('data-repository/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_data_repositories',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('data-repository/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_data_repository',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('data-repository/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_data_repository',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('data-repository/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_data_repository',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('data-repository/v1', '/upload-file', [
        'methods'  => 'POST',
        'callback' => 'upload_data_repo_file_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_data_repo_file_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-data-repo-' . basename($_FILES['file']['name']);
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

function get_all_data_repositories() {
    global $wpdb;
    $table = $wpdb->prefix . 'data_repository';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_data_repository($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'data_repository';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'category' => sanitize_text_field($params['category']),
        'title'    => sanitize_text_field($params['title']),
        'pdf_url'  => esc_url_raw($params['pdf_url']),
        'pdf_size' => sanitize_text_field($params['pdf_size'])
    ]);

    return ['status' => 'success'];
}

function update_data_repository($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'data_repository';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'category' => sanitize_text_field($params['category']),
        'title'    => sanitize_text_field($params['title']),
        'pdf_url'  => esc_url_raw($params['pdf_url']),
        'pdf_size' => sanitize_text_field($params['pdf_size'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_data_repository($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'data_repository';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function data_repository_page() { ?>
    <div class="wrap">
        <h1>Data Repository Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Data Repository Item</h2>

            <table class="form-table">
                <tr>
                    <th>Category</th>
                    <td>
                        <input type="text" id="dr_category" class="regular-text" placeholder="e.g. Health Data">
                    </td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td>
                        <input type="text" id="dr_title" class="regular-text" required placeholder="e.g. COVID-19 Surveillance Dataset">
                        <span id="dr_title_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>PDF Document</th>
                    <td>
                        <input type="file" id="dr_pdf_file" accept=".pdf">
                        <input type="hidden" id="dr_pdf_url">
                        <div style="margin-top:10px;">
                            <label>PDF Size (auto-calculated or manual): </label>
                            <input type="text" id="dr_pdf_size" class="small-text" placeholder="e.g. 4.5 MB">
                        </div>
                        <br>
                        <a id="dr_preview_pdf" href="#" target="_blank" style="display:none; color:#2271b1; text-decoration:none; font-weight:bold;">📄 View Uploaded PDF</a>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveDataRepository()">Save Item</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Data Repository Items</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Title</th>
                        <th>PDF</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="dataRepoList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const DR_API_BASE = "<?php echo site_url('/wp-json/data-repository/v1'); ?>";
    let drEditingId = null;

    function loadDataRepositories() {
        fetch(DR_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.category ? '<span style="background:#e0f0ff;color:#005cbf;padding:3px 8px;border-radius:12px;font-size:12px;">' + item.category + '</span>' : ''}</td>
                    <td><strong>${item.title}</strong></td>
                    <td>${item.pdf_url ? '<a href="' + item.pdf_url + '" target="_blank">View PDF</a> (' + item.pdf_size + ')' : ''}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editDataRepository(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteDataRepository(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("dataRepoList").innerHTML = html;
        });
    }

    function clearDrErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveDataRepository() {
        clearDrErrors();
        
        const categoryVal = document.getElementById("dr_category").value.trim();
        const titleVal = document.getElementById("dr_title").value.trim();
        const pdfUrlVal = document.getElementById("dr_pdf_url").value;
        const pdfSizeVal = document.getElementById("dr_pdf_size").value.trim();
        
        let isValid = true;
        if (!titleVal) {
            document.getElementById("dr_title_error").innerText = "Title is required.";
            document.getElementById("dr_title").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            category: categoryVal,
            title: titleVal,
            pdf_url: pdfUrlVal,
            pdf_size: pdfSizeVal
        };
        
        let url = DR_API_BASE + "/add";
        if (drEditingId) url = DR_API_BASE + "/update/" + drEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            drEditingId = null; 
            document.getElementById("dr_category").value = '';
            document.getElementById("dr_title").value = '';
            document.getElementById("dr_pdf_url").value = '';
            document.getElementById("dr_pdf_size").value = '';
            document.getElementById("dr_preview_pdf").style.display = "none";
            loadDataRepositories(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editDataRepository(item) {
        clearDrErrors();
        drEditingId = item.id;
        document.getElementById("dr_category").value = item.category;
        document.getElementById("dr_title").value = item.title;
        
        document.getElementById("dr_pdf_url").value = item.pdf_url;
        document.getElementById("dr_pdf_size").value = item.pdf_size;
        if(item.pdf_url) {
            document.getElementById("dr_preview_pdf").href = item.pdf_url;
            document.getElementById("dr_preview_pdf").style.display = "inline-block";
        } else {
            document.getElementById("dr_preview_pdf").style.display = "none";
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteDataRepository(id) {
        if (!confirm("Are you sure you want to delete this Data Repository item?")) return;
        fetch(DR_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadDataRepositories());
    }

    // PDF Upload
    document.getElementById("dr_pdf_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(DR_API_BASE + "/upload-file", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("dr_pdf_url").value = data.url;
                document.getElementById("dr_pdf_size").value = data.size; // Automatically set size
                document.getElementById("dr_preview_pdf").href = data.url;
                document.getElementById("dr_preview_pdf").style.display = "inline-block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadDataRepositories);
    </script>
<?php }
