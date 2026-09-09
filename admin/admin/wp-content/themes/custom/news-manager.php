<?php
/**
 * News Manager & REST API Controller
 */

// Include Cloud config if available
if (file_exists(__DIR__ . '/cloud_config.php')) {
    include_once(__DIR__ . '/cloud_config.php');
}
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_action('admin_menu', function () {
    add_submenu_page(
        'group-news',
        'News Manager',
        'News Manager',
        'manage_options',
        'news-manager',
        'news_manager_page'
    );
});

add_action('rest_api_init', function () {

    // 1. Get all news (Admin + Public)
    register_rest_route('news/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_news',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('news/v1', '/public', [
        'methods'             => 'GET',
        'callback'            => 'get_all_news',
        'permission_callback' => '__return_true'
    ]);

    // 2. Get single news item by ID or Slug
    register_rest_route('news/v1', '/single/(?P<id_or_slug>[a-zA-Z0-9_-]+)', [
        'methods'             => 'GET',
        'callback'            => 'get_single_news_item',
        'permission_callback' => '__return_true'
    ]);

    // 3. Add News
    register_rest_route('news/v1', '/add', [
        'methods'             => 'POST',
        'callback'            => 'add_news',
        'permission_callback' => '__return_true'
    ]);

    // 4. Update News
    register_rest_route('news/v1', '/update/(?P<id>\d+)', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_news',
        'permission_callback' => '__return_true'
    ]);

    // 5. Delete News
    register_rest_route('news/v1', '/delete/(?P<id>\d+)', [
        'methods'             => ['DELETE', 'POST'],
        'callback'            => 'delete_news',
        'permission_callback' => '__return_true'
    ]);

    // 6. Upload Image to R2 / Local Fallback
    register_rest_route('news/v1', '/upload-image', [
        'methods'             => 'POST',
        'callback'            => 'upload_image_to_r2_news',
        'permission_callback' => '__return_true'
    ]);
});

/**
 * Upload file to Cloudflare R2 with Local Uploads Fallback
 */
function inclen_upload_news_file_to_storage($fileTmp, $originalName) {
    $accId   = defined('R2_ACCOUNT_ID') ? R2_ACCOUNT_ID : ($GLOBALS['accountId'] ?? '');
    $accKey  = defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : ($GLOBALS['accessKey'] ?? '');
    $secKey  = defined('R2_SECRET_KEY') ? R2_SECRET_KEY : ($GLOBALS['secretKey'] ?? '');
    $bkt     = defined('R2_BUCKET') ? R2_BUCKET : ($GLOBALS['bucket'] ?? 'inclen');
    $pubBase = defined('R2_PUBLIC_URL') ? rtrim(R2_PUBLIC_URL, '/') : ($GLOBALS['public_url'] ?? 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin');

    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    if (empty($ext)) $ext = 'jpg';
    $fileName = time() . '-' . wp_generate_password(8, false) . '.' . $ext;
    $r2Key    = 'admin/news/' . $fileName;

    // 1. Try Cloudflare R2 Upload
    try {
        if (class_exists('\\Aws\\S3\\S3Client') && !empty($accId) && !empty($accKey) && !empty($secKey)) {
            $client = new \Aws\S3\S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => "https://$accId.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $accKey,
                    'secret' => $secKey,
                ],
            ]);

            $mime = function_exists('mime_content_type') && file_exists($fileTmp) ? mime_content_type($fileTmp) : 'image/jpeg';
            if (!$mime) $mime = 'image/jpeg';

            $client->putObject([
                'Bucket'      => $bkt,
                'Key'         => $r2Key,
                'SourceFile'  => $fileTmp,
                'ContentType' => $mime,
            ]);

            return $pubBase . '/news/' . $fileName;
        }
    } catch (Exception $e) {
        error_log('R2 Upload Error in news: ' . $e->getMessage());
    }

    // 2. Fallback: Save in WordPress Uploads folder
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/news';
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    $target_file = $target_dir . '/' . $fileName;
    if (copy($fileTmp, $target_file)) {
        return $upload_dir['baseurl'] . '/news/' . $fileName;
    }

    return false;
}

/**
 * Automatically convert any embedded base64 images inside HTML to clean URLs
 */
function inclen_clean_base64_images_in_html($html_content) {
    if (empty($html_content) || strpos($html_content, 'data:image/') === false) {
        return $html_content;
    }

    $pattern = '/src=["\'](data:image\/([a-zA-Z0-9]+);base64,([^"\']+))["\']/i';

    return preg_replace_callback($pattern, function ($matches) {
        $imageExt   = strtolower($matches[2]);
        $base64Data = $matches[3];

        if ($imageExt === 'jpeg') $imageExt = 'jpg';

        $decoded = base64_decode($base64Data);
        if (!$decoded) {
            return $matches[0];
        }

        $tmpPath = wp_tempnam('news_embed');
        file_put_contents($tmpPath, $decoded);

        $uploadedUrl = inclen_upload_news_file_to_storage($tmpPath, 'pasted-image.' . $imageExt);
        @unlink($tmpPath);

        if ($uploadedUrl) {
            return 'src="' . esc_url($uploadedUrl) . '"';
        }

        return $matches[0];
    }, $html_content);
}

/**
 * Handle base64 in featured image field if user pasted data URI
 */
function inclen_process_featured_image($image_field) {
    $img = trim($image_field ?? '');
    if (empty($img)) return '';

    if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,(.+)$/i', $img, $m)) {
        $ext = strtolower($m[1]);
        if ($ext === 'jpeg') $ext = 'jpg';
        $decoded = base64_decode($m[2]);
        if ($decoded) {
            $tmpPath = wp_tempnam('news_cover');
            file_put_contents($tmpPath, $decoded);
            $url = inclen_upload_news_file_to_storage($tmpPath, 'cover-image.' . $ext);
            @unlink($tmpPath);
            if ($url) return $url;
        }
    }

    return esc_url_raw($img);
}

/**
 * REST: Upload file endpoint
 */
function upload_image_to_r2_news() {
    if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
        return new WP_REST_Response(['error' => 'No file uploaded'], 400);
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = sanitize_file_name($_FILES['file']['name']);

    $url = inclen_upload_news_file_to_storage($fileTmp, $fileName);

    if ($url) {
        return new WP_REST_Response(['url' => $url, 'status' => 'success'], 200);
    } else {
        return new WP_REST_Response(['error' => 'Failed to upload image'], 500);
    }
}

/**
 * Helper to parse request params safely
 */
function inclen_get_request_data($request) {
    $body = $request->get_body();
    $data = null;
    if (!empty($body)) {
        $data = json_decode($body, true);
    }
    if (!is_array($data) || empty($data)) {
        $data = $request->get_json_params();
    }
    if (!is_array($data) || empty($data)) {
        $data = $request->get_params();
    }
    return is_array($data) ? $data : [];
}

/**
 * REST: Get All News
 */
function get_all_news() {
    global $wpdb;
    $table = $wpdb->prefix . 'news';

    // One-time sanitization of any existing large base64 strings in database
    static $cleaned_db = false;
    if (!$cleaned_db) {
        $cleaned_db = true;
        $items_with_base64 = $wpdb->get_results("SELECT id, content, image FROM $table WHERE content LIKE '%data:image/%' OR image LIKE 'data:image/%' LIMIT 10");
        if (!empty($items_with_base64)) {
            foreach ($items_with_base64 as $row) {
                $cleaned_content = inclen_clean_base64_images_in_html($row->content);
                $cleaned_image = inclen_process_featured_image($row->image);
                $wpdb->update($table, [
                    'content' => $cleaned_content,
                    'image'   => $cleaned_image
                ], ['id' => $row->id]);
            }
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    if (!is_array($results)) {
        $results = [];
    }
    return new WP_REST_Response($results, 200);
}

/**
 * REST: Get Single News by ID or Slug
 */
function get_single_news_item($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $id_or_slug = sanitize_text_field($request['id_or_slug'] ?? '');

    if (empty($id_or_slug)) {
        return new WP_REST_Response(['error' => 'Missing ID or slug'], 400);
    }

    if (is_numeric($id_or_slug)) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id_or_slug)));
    } else {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $id_or_slug));
    }

    if (!$item) {
        return new WP_REST_Response(['error' => 'News not found'], 404);
    }

    return new WP_REST_Response($item, 200);
}

/**
 * REST: Add News
 */
function add_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = inclen_get_request_data($request);

    $title   = sanitize_text_field($params['title'] ?? '');
    $author  = sanitize_text_field($params['author'] ?? '');
    $rawSlug = sanitize_title($params['slug'] ?? ($params['title'] ?? ''));
    if (empty($rawSlug)) {
        $rawSlug = 'news-' . time();
    }

    $slug = $rawSlug;
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));
    if ($existing) {
        $slug = $rawSlug . '-' . time();
    }

    // Clean base64 from content and featured image
    $rawContent = $params['content'] ?? '';
    $cleanContent = inclen_clean_base64_images_in_html($rawContent);
    $cleanImage = inclen_process_featured_image($params['image'] ?? '');

    $inserted = $wpdb->insert($table, [
        'title'      => $title,
        'slug'       => $slug,
        'content'    => $cleanContent,
        'author'     => $author,
        'image'      => $cleanImage,
        'created_at' => current_time('mysql')
    ]);

    if ($inserted === false) {
        return new WP_REST_Response([
            'status' => 'error',
            'error'  => $wpdb->last_error ?: 'Failed to insert into database'
        ], 500);
    }

    $newId = $wpdb->insert_id;
    $newItem = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $newId));

    return new WP_REST_Response([
        'status' => 'success',
        'id'     => $newId,
        'data'   => $newItem
    ], 200);
}

/**
 * REST: Update News
 */
function update_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = inclen_get_request_data($request);
    $id = intval($request['id'] ?? ($params['id'] ?? 0));

    if (!$id) {
        return new WP_REST_Response(['status' => 'error', 'error' => 'Invalid news ID'], 400);
    }

    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    if (!$existing) {
        return new WP_REST_Response(['status' => 'error', 'error' => 'News item not found'], 404);
    }

    $title  = isset($params['title']) ? sanitize_text_field($params['title']) : $existing->title;
    $author = isset($params['author']) ? sanitize_text_field($params['author']) : $existing->author;
    
    $slug = $existing->slug;
    if (!empty($params['slug'])) {
        $slug = sanitize_title($params['slug']);
    }

    $cleanContent = $existing->content;
    if (isset($params['content'])) {
        $cleanContent = inclen_clean_base64_images_in_html($params['content']);
    }

    $cleanImage = $existing->image;
    if (isset($params['image'])) {
        $cleanImage = inclen_process_featured_image($params['image']);
    }

    $updated = $wpdb->update($table, [
        'title'   => $title,
        'slug'    => $slug,
        'content' => $cleanContent,
        'author'  => $author,
        'image'   => $cleanImage,
    ], ['id' => $id]);

    if ($updated === false) {
        return new WP_REST_Response([
            'status' => 'error',
            'error'  => $wpdb->last_error ?: 'Failed to update database'
        ], 500);
    }

    $updatedItem = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    return new WP_REST_Response([
        'status' => 'updated',
        'id'     => $id,
        'data'   => $updatedItem
    ], 200);
}

/**
 * REST: Delete News
 */
function delete_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = inclen_get_request_data($request);
    $id = intval($request['id'] ?? ($params['id'] ?? 0));

    if (!$id) {
        return new WP_REST_Response(['status' => 'error', 'error' => 'Invalid ID'], 400);
    }

    $deleted = $wpdb->delete($table, ['id' => $id]);

    return new WP_REST_Response(['status' => 'deleted', 'id' => $id, 'success' => (bool)$deleted], 200);
}

/**
 * Admin Panel UI Page
 */
function news_manager_page() { ?>

<div class="wrap">
<h1 style="display:flex;align-items:center;gap:10px;">
    <span>News & Articles Manager</span>
</h1>

<style>
    .error-msg { color: #d63638; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
    .input-error { border-color: #d63638 !important; box-shadow: 0 0 2px rgba(214, 54, 56, 0.8) !important; }
    .news-box { background:#fff; padding:22px 25px; margin-top:20px; border:1px solid #ccd0d4; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
    .btn-action { margin-right: 6px !important; }
    #image_upload_status { font-size: 13px; font-weight: 500; }
</style>

<div class="news-box">

<h2 id="form-heading" style="margin-top:0;font-size:18px;color:#1d2327;">Add New Article</h2>

<table class="form-table" role="presentation">

<tr>
<th scope="row"><label for="title">Title <span style="color:red;">*</span></label></th>
<td>
    <input type="text" id="title" class="large-text" placeholder="Enter news headline / title" required style="max-width:600px;">
    <span id="title_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th scope="row"><label for="slug">Custom URL Slug (Optional)</label></th>
<td>
    <input type="text" id="slug" class="regular-text" placeholder="e.g. agricultural-summit-2026" style="max-width:600px;">
    <p class="description">Leave empty to auto-generate from title.</p>
</td>
</tr>

<tr>
<th scope="row"><label for="author">Author <span style="color:red;">*</span></label></th>
<td>
    <input type="text" id="author" class="regular-text" placeholder="Author name (e.g. INCLEN Media Desk)" required>
    <span id="author_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th scope="row"><label for="content_editor">Content <span style="color:red;">*</span></label></th>
<td>
    <?php wp_editor('', 'content_editor', [
        'textarea_rows' => 12,
        'media_buttons' => true,
        'quicktags'     => true,
    ]); ?>
    <span id="content_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th scope="row"><label for="image_file">Featured Image</label></th>
<td>
    <input type="file" id="image_file" accept="image/*">
    <input type="hidden" id="image">
    <span id="image_upload_status"></span>
    <br><br>
    <img id="preview_image" style="max-width:180px;max-height:130px;display:none;border-radius:6px;border:1px solid #ccd0d4;padding:3px;object-fit:cover;">
</td>
</tr>

</table>

<p class="submit" style="margin-top:20px;">
    <button id="save-news-btn" type="button" class="button button-primary button-large" onclick="addNews()">Save News</button>
    <button id="cancel-edit-btn" type="button" class="button button-large" onclick="resetNewsForm()" style="display:none;margin-left:10px;">Cancel Edit</button>
</p>

</div>

<div class="news-box" style="margin-top:30px;">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
    <h2 style="margin:0;font-size:18px;color:#1d2327;">All Published News</h2>
    <button type="button" class="button" onclick="loadNews()">↻ Refresh List</button>
</div>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th style="width:25%;">Title</th>
<th style="width:14%;">Author</th>
<th style="width:12%;">Featured Image</th>
<th style="width:28%;">Live Post Link</th>
<th style="width:21%;">Actions</th>
</tr>
</thead>
<tbody id="newsList">
<tr><td colspan="5" style="text-align:center;color:#666;padding:20px;">Loading news articles...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
const API_BASE = "<?php echo site_url('/index.php?rest_route=/news/v1'); ?>";
let editingId = null;
const newsDataMap = {};

function copyNewsUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✓ Copied';
        btn.style.color = '#27ae60';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 1500);
    }).catch(() => alert('URL: ' + url));
}

function loadNews(){
    const listEl = document.getElementById("newsList");
    listEl.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#666;padding:20px;">Loading news articles...</td></tr>';

    fetch(API_BASE + "/all")
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (!data || !Array.isArray(data) || !data.length) {
            html = '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">No news items found. Add your first news article above!</td></tr>';
        } else {
            data.forEach(item => {
                newsDataMap[item.id] = item;
                const imgHtml = item.image ? '<img src="' + item.image + '" width="60" height="42" style="border-radius:4px;border:1px solid #ddd;object-fit:cover;">' : '<span style="color:#aaa;font-size:11px;">No image</span>';
                const itemSlug = item.slug || ('news-' + item.id);
                const pageUrl = '/news/post/?id=' + encodeURIComponent(itemSlug);
                const fullFrontUrl = 'http://localhost:3000' + pageUrl;
                
                const linkHtml = `
                    <div style="display:flex;align-items:center;gap:6px;">
                        <a href="${fullFrontUrl}" target="_blank" style="font-size:11px;font-family:monospace;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;text-decoration:none;color:#0073aa;" title="${pageUrl}">${pageUrl}</a>
                        <button type="button" class="button button-small" onclick="copyNewsUrl('${fullFrontUrl}', this)" title="Copy Live Link" style="padding:0 5px;font-size:11px;line-height:20px;height:22px;">📋 Copy</button>
                    </div>`;
                
                html += `
                <tr id="news-row-${item.id}">
                <td><strong>${escapeHtml(item.title)}</strong></td>
                <td>${escapeHtml(item.author || '-')}</td>
                <td>${imgHtml}</td>
                <td>${linkHtml}</td>
                <td>
                    <button type="button" class="button button-small btn-action" onclick="editNews(${item.id})">✏️ Edit</button>
                    <button type="button" class="button button-small btn-action" style="color:#b32d2e;" onclick="deleteNews(${item.id})">🗑️ Delete</button>
                </td>
                </tr>`;
            });
        }
        listEl.innerHTML = html;
    })
    .catch(err => {
        listEl.innerHTML = '<tr><td colspan="5" style="color:red;padding:20px;">Error loading news: ' + err + '</td></tr>';
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}

function resetNewsForm() {
    clearErrors();
    editingId = null;
    document.getElementById("form-heading").innerText = "Add New Article";
    document.getElementById("save-news-btn").innerText = "Save News";
    document.getElementById("cancel-edit-btn").style.display = "none";
    document.getElementById("title").value = '';
    document.getElementById("slug").value = '';
    document.getElementById("author").value = '';
    document.getElementById("image").value = '';
    document.getElementById("image_file").value = '';
    document.getElementById("preview_image").src = '';
    document.getElementById("preview_image").style.display = "none";
    document.getElementById("image_upload_status").innerText = '';

    if (typeof tinymce !== 'undefined' && tinymce.get("content_editor")) {
        tinymce.get("content_editor").setContent('');
    } else if (document.getElementById("content_editor")) {
        document.getElementById("content_editor").value = '';
    }
}

function editNews(id) {
    clearErrors();
    const item = newsDataMap[id];
    if (!item) return;

    editingId = item.id;
    document.getElementById("form-heading").innerText = "Edit Article (ID: " + item.id + ")";
    document.getElementById("save-news-btn").innerText = "Update News";
    document.getElementById("cancel-edit-btn").style.display = "inline-block";

    document.getElementById("title").value = item.title || '';
    document.getElementById("slug").value = item.slug || '';
    document.getElementById("author").value = item.author || '';
    document.getElementById("image").value = item.image || '';

    if (item.image) {
        document.getElementById("preview_image").src = item.image;
        document.getElementById("preview_image").style.display = "block";
    } else {
        document.getElementById("preview_image").src = '';
        document.getElementById("preview_image").style.display = "none";
    }

    if (document.getElementById("content_editor")) {
        document.getElementById("content_editor").value = item.content || '';
    }
    if (typeof tinymce !== 'undefined' && tinymce.get("content_editor")) {
        try {
            tinymce.get("content_editor").setContent(item.content || '');
        } catch (e) {}
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function addNews(){
    clearErrors();
    
    const titleVal = document.getElementById("title").value.trim();
    const slugVal = document.getElementById("slug").value.trim();
    const authorVal = document.getElementById("author").value.trim();
    
    let contentVal = '';
    if (typeof tinymce !== 'undefined' && tinymce.get("content_editor")) {
        try {
            contentVal = tinymce.get("content_editor").getContent().trim();
        } catch(e) {}
    }
    if (!contentVal && document.getElementById("content_editor")) {
        contentVal = document.getElementById("content_editor").value.trim();
    }
    
    const imageVal = document.getElementById("image").value.trim();
    
    let isValid = true;
    
    if (!titleVal) {
        document.getElementById("title_error").innerText = "Title is required.";
        document.getElementById("title").classList.add("input-error");
        isValid = false;
    }
    
    if (!contentVal) {
        document.getElementById("content_error").innerText = "Content is required.";
        const wrap = document.getElementById("wp-content_editor-wrap");
        if (wrap) wrap.classList.add("input-error");
        isValid = false;
    }
    
    if (!authorVal) {
        document.getElementById("author_error").innerText = "Author is required.";
        document.getElementById("author").classList.add("input-error");
        isValid = false;
    }
    
    if (!isValid) return;

    const saveBtn = document.getElementById("save-news-btn");
    const origBtnText = saveBtn.innerText;
    saveBtn.innerText = editingId ? "Updating..." : "Saving...";
    saveBtn.disabled = true;

    const payload = {
        title: titleVal,
        slug: slugVal,
        content: contentVal,
        author: authorVal,
        image: imageVal
    };
    
    let url = API_BASE + "/add";
    if (editingId) {
        url = API_BASE + "/update/" + editingId;
    }

    fetch(url, {
        method: "POST",
        headers: { 
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(payload)
    })
    .then(async (res) => {
        const json = await res.json();
        if (!res.ok || json.status === 'error') {
            throw new Error(json.error || 'Server error (' + res.status + ')');
        }
        return json;
    })
    .then(res => { 
        alert(editingId ? "News updated successfully!" : "News published successfully!"); 
        resetNewsForm();
        loadNews(); 
    })
    .catch(err => {
        alert("Failed to save news: " + err.message);
    })
    .finally(() => {
        saveBtn.innerText = origBtnText;
        saveBtn.disabled = false;
    });
}

function deleteNews(id){
    if (!confirm("Are you sure you want to delete this news article?")) return;
    
    fetch(API_BASE + "/delete/" + id, { 
        method: "DELETE",
        headers: { "Accept": "application/json" }
    })
    .then(res => res.json())
    .then((res) => {
        if (editingId === id) resetNewsForm();
        loadNews();
    })
    .catch(err => alert("Error deleting news: " + err));
}

// Helper to compress/resize high-resolution images in the browser before upload
function compressImageForUpload(file, maxWidth = 1600, maxHeight = 1200, quality = 0.85) {
    return new Promise((resolve) => {
        // If file is already small (under 400KB), no need to compress
        if (file.size < 400 * 1024) {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width / height > maxWidth / maxHeight) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    if (blob && blob.size < file.size) {
                        const newName = file.name.replace(/\.[^.]+$/, '.jpg');
                        const compressedFile = new File([blob], newName, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    } else {
                        resolve(file);
                    }
                }, 'image/jpeg', quality);
            };
            img.onerror = function() { resolve(file); };
        };
        reader.onerror = function() { resolve(file); };
    });
}

document.getElementById("image_file").addEventListener("change", async function(){
    let file = this.files[0];
    if (!file) return;

    let statusEl = document.getElementById("image_upload_status");
    statusEl.innerText = "⏳ Optimizing & Uploading...";
    statusEl.style.color = "#0073aa";

    // Show immediate local preview
    try {
        const previewUrl = URL.createObjectURL(file);
        document.getElementById("preview_image").src = previewUrl;
        document.getElementById("preview_image").style.display = "block";
    } catch(e) {}

    try {
        // Compress image in browser if larger than 400KB
        const optimizedFile = await compressImageForUpload(file);
        
        let formData = new FormData();
        formData.append("file", optimizedFile);

        const res = await fetch(API_BASE + "/upload-image", { 
            method: "POST", 
            body: formData 
        });

        if (res.status === 413) {
            throw new Error("File is too large for the server. Please choose a smaller image.");
        }

        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            throw new Error("Server error (" + res.status + "): " + text.replace(/<[^>]*>/g, '').substring(0, 100));
        }

        if (data.url) {
            document.getElementById("image").value = data.url;
            document.getElementById("preview_image").src = data.url;
            document.getElementById("preview_image").style.display = "block";
            statusEl.innerText = "✓ Uploaded Successfully";
            statusEl.style.color = "#27ae60";
        } else {
            statusEl.innerText = "✗ Upload Failed: " + (data.error || "Unknown error");
            statusEl.style.color = "#d63638";
        }
    } catch(err) {
        statusEl.innerText = "✗ Upload Error: " + err.message;
        statusEl.style.color = "#d63638";
    }
});

document.addEventListener("DOMContentLoaded", loadNews);
</script>
<?php } ?>