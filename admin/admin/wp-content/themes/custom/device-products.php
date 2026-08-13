<?php
add_action('admin_menu', function () {
    add_menu_page(
        'Device Products',
        'Device Products',
        'manage_options',
        'device-products',
        'device_products_page',
        'dashicons-smartphone',
        33
    );
});

// REST API setup for Device Products
add_action('rest_api_init', function () {
    register_rest_route('device-products/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_device_products',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('device-products/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_device_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('device-products/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_device_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('device-products/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_device_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('device-products/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_device_product_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_device_product_image_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-device-' . basename($_FILES['file']['name']);
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

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function get_all_device_products() {
    global $wpdb;
    $table = $wpdb->prefix . 'device_products';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_device_product($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'device_products';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'cover_image'       => esc_url_raw($params['cover_image']),
        'tag'               => sanitize_text_field($params['tag']),
        'heading'           => sanitize_text_field($params['heading']),
        'short_description' => sanitize_textarea_field($params['short_description']),
        'para'              => wp_kses_post($params['para'])
    ]);

    return ['status' => 'success'];
}

function update_device_product($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'device_products';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'cover_image'       => esc_url_raw($params['cover_image']),
        'tag'               => sanitize_text_field($params['tag']),
        'heading'           => sanitize_text_field($params['heading']),
        'short_description' => sanitize_textarea_field($params['short_description']),
        'para'              => wp_kses_post($params['para'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_device_product($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'device_products';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function device_products_page() { ?>
    <div class="wrap">
        <h1>Device Products Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Device Product</h2>

            <table class="form-table">
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="dp_heading" class="regular-text" required placeholder="e.g. Smart Watch">
                        <span id="dp_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Tag</th>
                    <td>
                        <input type="text" id="dp_tag" class="regular-text" placeholder="e.g. New Release">
                    </td>
                </tr>
                <tr>
                    <th>Cover Image</th>
                    <td>
                        <input type="file" id="dp_cover_image_file" accept="image/*">
                        <input type="hidden" id="dp_cover_image">
                        <br><br>
                        <img id="dp_preview_image" style="max-width:150px;display:none;border:1px solid #ddd;padding:3px;border-radius:4px;">
                    </td>
                </tr>
                <tr>
                    <th>Short Description (Optional)</th>
                    <td>
                        <textarea id="dp_short_description" class="large-text" rows="2" placeholder="A brief summary..."></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Para (Description)</th>
                    <td>
                        <p class="description">Detailed description of the product.</p>
                        <?php wp_editor('', 'dp_para_editor', array('media_buttons' => false)); ?>
                        <span id="dp_para_error" class="error-msg"></span>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveDeviceProduct()">Save Device Product</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Device Products</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Heading</th>
                        <th>Tag</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="deviceProductsList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const DP_API_BASE = "<?php echo site_url('/wp-json/device-products/v1'); ?>";
    let dpEditingId = null;

    function loadDeviceProducts() {
        fetch(DP_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.cover_image ? '<img src="' + item.cover_image + '" width="60" style="border-radius:4px;">' : ''}</td>
                    <td><strong>${item.heading}</strong></td>
                    <td>${item.tag ? '<span style="background:#e0f0ff;color:#005cbf;padding:3px 8px;border-radius:12px;font-size:12px;">' + item.tag + '</span>' : ''}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editDeviceProduct(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteDeviceProduct(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("deviceProductsList").innerHTML = html;
        });
    }

    function clearDpErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveDeviceProduct() {
        clearDpErrors();
        
        const headingVal = document.getElementById("dp_heading").value.trim();
        const tagVal = document.getElementById("dp_tag").value.trim();
        const coverImageVal = document.getElementById("dp_cover_image").value;
        const shortDescVal = document.getElementById("dp_short_description").value.trim();
        const paraVal = tinymce.get("dp_para_editor").getContent().trim();
        
        let isValid = true;
        if (!headingVal) {
            document.getElementById("dp_heading_error").innerText = "Heading is required.";
            document.getElementById("dp_heading").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            heading: headingVal,
            tag: tagVal,
            cover_image: coverImageVal,
            short_description: shortDescVal,
            para: paraVal
        };
        
        let url = DP_API_BASE + "/add";
        if (dpEditingId) url = DP_API_BASE + "/update/" + dpEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            dpEditingId = null; 
            document.getElementById("dp_heading").value = '';
            document.getElementById("dp_tag").value = '';
            document.getElementById("dp_short_description").value = '';
            document.getElementById("dp_cover_image").value = '';
            document.getElementById("dp_preview_image").style.display = "none";
            tinymce.get("dp_para_editor").setContent('');
            loadDeviceProducts(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editDeviceProduct(item) {
        clearDpErrors();
        dpEditingId = item.id;
        document.getElementById("dp_heading").value = item.heading;
        document.getElementById("dp_tag").value = item.tag;
        document.getElementById("dp_short_description").value = item.short_description || '';
        
        document.getElementById("dp_cover_image").value = item.cover_image;
        if(item.cover_image) {
            document.getElementById("dp_preview_image").src = item.cover_image;
            document.getElementById("dp_preview_image").style.display = "block";
        } else {
            document.getElementById("dp_preview_image").style.display = "none";
        }
        
        tinymce.get("dp_para_editor").setContent(item.para);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteDeviceProduct(id) {
        if (!confirm("Are you sure you want to delete this Device Product?")) return;
        fetch(DP_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadDeviceProducts());
    }

    // Cover Image Upload
    document.getElementById("dp_cover_image_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(DP_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("dp_cover_image").value = data.url;
                document.getElementById("dp_preview_image").src = data.url;
                document.getElementById("dp_preview_image").style.display = "block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadDeviceProducts);
    </script>
<?php }
