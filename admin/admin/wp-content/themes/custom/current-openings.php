<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-careers',
        'Current Openings',
        'Current Openings',
        'manage_options',
        'current-opening-manager',
        'opening_manager_page'
    );
});

add_action('rest_api_init', function () {
    // Get all openings
    register_rest_route('currentopening/v1', '/all-openings', [
        'methods' => 'GET',
        'callback' => 'get_all_openings',
        'permission_callback' => '__return_true'
    ]);

    // Add opening
    register_rest_route('currentopening/v1', '/add-opening', [
        'methods' => 'POST',
        'callback' => 'add_opening',
        'permission_callback' => '__return_true'
    ]);

    // Update opening
    register_rest_route('currentopening/v1', '/update-opening/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_opening',
        'permission_callback' => '__return_true'
    ]);

    // Delete opening
    register_rest_route('currentopening/v1', '/delete-opening/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_opening',
        'permission_callback' => '__return_true'
    ]);

    // Upload Image
    register_rest_route('currentopening/v1', '/upload-image-opening', [
        'methods' => 'POST',
        'callback' => 'upload_image_opening',
        'permission_callback' => '__return_true'
    ]);

    // Upload PDF
    register_rest_route('currentopening/v1', '/upload-pdf-opening', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_opening',
        'permission_callback' => '__return_true'
    ]);
});

function upload_image_opening() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/openings";

    try {
        $client = new \Aws\S3\S3Client([
            'version'   => 'latest',
            'region'    => 'auto',
            'endpoint'  => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/openings/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function upload_pdf_opening() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/openings/pdf";

    try {
        $client = new \Aws\S3\S3Client([
            'version'   => 'latest',
            'region'    => 'auto',
            'endpoint'  => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/openings/pdf/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function get_all_openings() {
    global $wpdb;
    $table = $wpdb->prefix . 'openings';   // Make sure your table name is 'wp_openings' or adjust accordingly
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_opening($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'openings';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'title'           => sanitize_text_field($params['title']),
        'slug'            => sanitize_title($params['title']),
        'content'         => wp_kses_post($params['content']),
        'location'        => sanitize_text_field($params['location']),
        'employment_type' => sanitize_text_field($params['employment_type']),
        'image'           => esc_url_raw($params['image'] ?? ''),
        'pdf'             => esc_url_raw($params['pdf'] ?? ''),
        'created_at'      => current_time('mysql')
    ]);

    return ['status' => 'success'];
}

function update_opening($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'openings';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'title'           => sanitize_text_field($params['title']),
        'slug'            => sanitize_title($params['title']),
        'content'         => wp_kses_post($params['content']),
        'location'        => sanitize_text_field($params['location']),
        'employment_type' => sanitize_text_field($params['employment_type']),
        'image'           => esc_url_raw($params['image'] ?? ''),
        'pdf'             => esc_url_raw($params['pdf'] ?? ''),
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_opening($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'openings';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function opening_manager_page() { ?>
<div class="wrap">
    <h1>Current Openings Manager</h1>
    
    <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
        <h2>Add / Edit Opening</h2>
        <table class="form-table">
            <tr>
                <th>Title <span style="color:red;">*</span></th>
                <td><input type="text" id="title" class="regular-text" placeholder="Job title / Position"></td>
            </tr>
            <tr>
                <th>Content</th>
                <td><?php wp_editor('', 'content_editor'); ?></td>
            </tr>
            <tr>
                <th>Location</th>
                <td><input type="text" id="location" class="regular-text" placeholder="e.g. Delhi, Remote"></td>
            </tr>
            <tr>
                <th>Employment Type</th>
                <td>
                    <select id="employment_type" class="regular-text">
                        <option value="">-- Select Type --</option>
                        <option value="Freelance">Freelance</option>
                        <option value="Full Time">Full Time</option>
                        <option value="Internship">Internship</option>
                        <option value="Part Time">Part Time</option>
                        <option value="Temporary">Temporary</option>
                        <option value="Contractual">Contractual</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Image</th>
                <td>
                    <input type="file" id="image_file" accept="image/*">
                    <input type="hidden" id="image">
                    <br><br>
                    <img id="preview_image" style="max-width:150px;display:none;border-radius:5px;border:1px solid #ddd;">
                </td>
            </tr>
            <tr>
                <th>PDF (Job Description)</th>
                <td>
                    <input type="file" id="pdf_file" accept="application/pdf">
                    <input type="hidden" id="pdf">
                    <span id="pdf_name" style="display:none; color:#0073aa;"></span>
                </td>
            </tr>
        </table>
        <p>
            <button class="button button-primary" onclick="addOpening()">Save Opening</button>
            <button class="button" onclick="resetForm()" style="margin-left:10px;">Clear Form</button>
        </p>
    </div>

    <div style="margin-top:30px;">
        <h2>All Openings</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Image</th>
                    <th>PDF</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="openingList"></tbody>
        </table>
    </div>
</div>

<script>
const API_BASE = "<?php echo site_url('/wp-json/currentopening/v1'); ?>";
let editingId = null;

function loadOpenings() {
    fetch(API_BASE + "/all-openings")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td><strong>${item.title}</strong></td>
                    <td>${item.location || '-'}</td>
                    <td>${item.employment_type || '-'}</td>
                    <td>${item.created_at ? item.created_at.substring(0,10) : '-'}</td>
                    <td>${item.image ? `<img src="${item.image}" width="60" style="border-radius:3px;border:1px solid #ddd;">` : '-'}</td>
                    <td>${item.pdf ? `<a href="${item.pdf}" target="_blank">View PDF</a>` : '-'}</td>
                    <td>
                        <button onclick='editOpening(${JSON.stringify(item)})' class="button">Edit</button>
                        <button onclick='deleteOpening(${item.id})' class="button button-link-delete">Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("openingList").innerHTML = html;
        })
        .catch(err => console.error("Error loading openings:", err));
}

function addOpening() {
    if (!document.getElementById("title").value.trim()) {
        alert("Title is required");
        return;
    }

    let content = tinymce.get("content_editor").getContent();

    const data = {
        title: document.getElementById("title").value,
        content: content,
        location: document.getElementById("location").value,
        employment_type: document.getElementById("employment_type").value,
        image: document.getElementById("image").value,
        pdf: document.getElementById("pdf").value
    };

    let url = API_BASE + "/add-opening";
    if (editingId) url = API_BASE + "/update-opening/" + editingId;

    fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(() => {
        alert(editingId ? "Updated Successfully ✓" : "Saved Successfully ✓");
        editingId = null;
        resetForm();
        loadOpenings();
    })
    .catch(err => alert("Error: " + err));
}

function editOpening(item) {
    editingId = item.id;
    document.getElementById("title").value = item.title || "";
    document.getElementById("location").value = item.location || "";
    document.getElementById("employment_type").value = item.employment_type || "";
    document.getElementById("image").value = item.image || "";
    document.getElementById("pdf").value = item.pdf || "";

    // Preview image
    if (item.image) {
        document.getElementById("preview_image").src = item.image;
        document.getElementById("preview_image").style.display = "block";
    } else {
        document.getElementById("preview_image").style.display = "none";
    }

    // PDF name hint
    if (item.pdf) {
        document.getElementById("pdf_name").innerText = "PDF Uploaded";
        document.getElementById("pdf_name").style.display = "inline";
    } else {
        document.getElementById("pdf_name").style.display = "none";
    }

    tinymce.get("content_editor").setContent(item.content || "");
    window.scrollTo(0, 0);
}

function deleteOpening(id) {
    if (!confirm("Are you sure you want to delete this opening?")) return;

    fetch(API_BASE + "/delete-opening/" + id, { method: "DELETE" })
        .then(() => {
            alert("Deleted Successfully");
            loadOpenings();
        })
        .catch(err => alert("Error: " + err));
}

function resetForm() {
    editingId = null;
    document.getElementById("title").value = "";
    document.getElementById("location").value = "";
    document.getElementById("employment_type").value = "";
    document.getElementById("image").value = "";
    document.getElementById("pdf").value = "";
    document.getElementById("preview_image").style.display = "none";
    document.getElementById("pdf_name").style.display = "none";
    tinymce.get("content_editor").setContent("");
}

// Image Upload
document.getElementById("image_file").addEventListener("change", function() {
    let file = this.files[0];
    if (!file) return;

    let formData = new FormData();
    formData.append("file", file);

    fetch(API_BASE + "/upload-image-opening", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.url) {
            document.getElementById("image").value = data.url;
            document.getElementById("preview_image").src = data.url;
            document.getElementById("preview_image").style.display = "block";
        } else {
            alert("Image Upload Failed: " + (data.error || ""));
        }
    })
    .catch(err => alert("Upload Error: " + err));
});

// PDF Upload
document.getElementById("pdf_file").addEventListener("change", function() {
    let file = this.files[0];
    if (!file) return;

    let formData = new FormData();
    formData.append("file", file);

    fetch(API_BASE + "/upload-pdf-opening", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.url) {
            document.getElementById("pdf").value = data.url;
            document.getElementById("pdf_name").innerText = file.name;
            document.getElementById("pdf_name").style.display = "inline";
        } else {
            alert("PDF Upload Failed: " + (data.error || ""));
        }
    })
    .catch(err => alert("PDF Upload Error: " + err));
});

document.addEventListener("DOMContentLoaded", loadOpenings);
</script>
<?php } ?>