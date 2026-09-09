<?php
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

    register_rest_route('news/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_news',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('news/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_news',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('news/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_news',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('news/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_news',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('news/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});



function upload_image_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket, $public_url;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = !empty($public_url) ? $public_url . '/news' : 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/news';

    try {
        $client = new \Aws\S3\S3Client([
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
            'Key' => 'admin/news/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



function get_all_news() {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = json_decode($request->get_body(), true);

    $title = sanitize_text_field($params['title'] ?? '');
    $slug = sanitize_title($params['slug'] ?? ($params['title'] ?? ''));
    if (empty($slug)) {
        $slug = 'news-' . time();
    }

    $wpdb->insert($table, [
        'title'   => $title,
        'slug'    => $slug,
        'content' => wp_kses_post($params['content'] ?? ''),
        'author'  => sanitize_text_field($params['author'] ?? ''),
        'image'   => esc_url_raw($params['image'] ?? '')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = json_decode($request->get_body(), true);
    $id = intval($request['id']);

    if (!$id) {
        return new WP_Error('invalid_id', 'Invalid news ID', ['status' => 400]);
    }

    $title = sanitize_text_field($params['title'] ?? '');
    $slug = sanitize_title($params['slug'] ?? ($params['title'] ?? ''));
    if (empty($slug)) {
        $slug = 'news-' . $id;
    }

    $wpdb->update($table, [
        'title'   => $title,
        'slug'    => $slug,
        'content' => wp_kses_post($params['content'] ?? ''),
        'author'  => sanitize_text_field($params['author'] ?? ''),
        'image'   => esc_url_raw($params['image'] ?? '')
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $id = intval($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}



function news_manager_page() { ?>

<div class="wrap">
<h1>News Manager</h1>

<style>
    .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
    .input-error { border-color: red !important; }
</style>

<div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">

<h2 id="form-heading">Add / Edit News</h2>

<table class="form-table">

<tr>
<th>Title <span style="color:red;">*</span></th>
<td>
    <input type="text" id="title" class="regular-text" placeholder="Enter news title" required>
    <span id="title_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Content <span style="color:red;">*</span></th>
<td>
    <?php wp_editor('', 'content_editor', [
        'textarea_rows' => 10,
        'media_buttons' => true,
        'quicktags'     => true,
    ]); ?>
    <span id="content_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Author <span style="color:red;">*</span></th>
<td>
    <input type="text" id="author" class="regular-text" placeholder="Author name" required>
    <span id="author_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Image</th>
<td>
<input type="file" id="image_file" accept="image/*">
<input type="hidden" id="image">
<span id="image_upload_status" style="font-size:12px;color:#0073aa;margin-left:10px;"></span>
<br><br>
<img id="preview_image" style="max-width:150px;display:none;border-radius:6px;border:1px solid #ddd;padding:4px;">
</td>
</tr>

</table>

<p>
<button id="save-news-btn" class="button button-primary" onclick="addNews()">Save News</button>
<button id="cancel-edit-btn" class="button" onclick="resetNewsForm()" style="display:none;margin-left:10px;">Cancel Edit</button>
</p>

</div>

<div style="margin-top:30px;">
<h2>All News</h2>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th style="width:28%;">Title</th>
<th style="width:15%;">Author</th>
<th style="width:12%;">Image</th>
<th style="width:25%;">Page Link</th>
<th style="width:20%;">Action</th>
</tr>
</thead>
<tbody id="newsList">
<tr><td colspan="5" style="text-align:center;color:#888;">Loading...</td></tr>
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
    fetch(API_BASE + "/all")
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (!data || !data.length) {
            html = '<tr><td colspan="5" style="text-align:center;color:#888;">No news items found.</td></tr>';
        } else {
            data.forEach(item => {
                newsDataMap[item.id] = item;
                const imgHtml = item.image ? '<img src="' + item.image + '" width="60" style="border-radius:4px;border:1px solid #ddd;max-height:45px;object-fit:cover;">' : '<span style="color:#aaa;font-size:11px;">No image</span>';
                const itemSlug = item.slug || ('news-' + item.id);
                const pageUrl = '/news/post/?id=' + itemSlug;
                const linkHtml = `
                    <div style="display:flex;align-items:center;gap:6px;">
                        <a href="http://localhost:3000${pageUrl}" target="_blank" style="font-size:11px;font-family:monospace;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;text-decoration:none;color:#0073aa;" title="${pageUrl}">${pageUrl}</a>
                        <button type="button" class="button button-small" onclick="copyNewsUrl('http://localhost:3000${pageUrl}', this)" title="Copy URL" style="padding:0 5px;font-size:11px;line-height:20px;height:22px;">📋</button>
                    </div>`;
                html += `
                <tr>
                <td><strong>${escapeHtml(item.title)}</strong></td>
                <td>${escapeHtml(item.author || '-')}</td>
                <td>${imgHtml}</td>
                <td>${linkHtml}</td>
                <td>
                <button class="button button-small" onclick="editNews(${item.id})">Edit</button>
                <button class="button button-small" style="color:#b32d2e;margin-left:6px;" onclick="deleteNews(${item.id})">Delete</button>
                </td>
                </tr>`;
            });
        }
        document.getElementById("newsList").innerHTML = html;
    })
    .catch(err => {
        document.getElementById("newsList").innerHTML = '<tr><td colspan="4" style="color:red;">Error loading news: ' + err + '</td></tr>';
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
    document.getElementById("form-heading").innerText = "Add / Edit News";
    document.getElementById("save-news-btn").innerText = "Save News";
    document.getElementById("cancel-edit-btn").style.display = "none";
    document.getElementById("title").value = '';
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
    document.getElementById("form-heading").innerText = "Edit News (ID: " + item.id + ")";
    document.getElementById("save-news-btn").innerText = "Update News";
    document.getElementById("cancel-edit-btn").style.display = "inline-block";

    document.getElementById("title").value = item.title || '';
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
    const imageVal = document.getElementById("image").value;
    
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
    saveBtn.innerText = "Saving...";
    saveBtn.disabled = true;

    const data = {
        title: titleVal,
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
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => { 
        alert(editingId ? "News updated successfully!" : "News saved successfully!"); 
        resetNewsForm();
        loadNews(); 
    })
    .catch(err => {
        alert("Failed to save news: " + err);
    })
    .finally(() => {
        saveBtn.innerText = origBtnText;
        saveBtn.disabled = false;
    });
}

function deleteNews(id){
    if (!confirm("Are you sure you want to delete this news item?")) return;
    fetch(API_BASE + "/delete/" + id, { method: "DELETE" })
    .then(res => res.json())
    .then(() => {
        if (editingId === id) resetNewsForm();
        loadNews();
    })
    .catch(err => alert("Error deleting news: " + err));
}

document.getElementById("image_file").addEventListener("change", function(){
    let file = this.files[0];
    if (!file) return;

    let statusEl = document.getElementById("image_upload_status");
    statusEl.innerText = "Uploading...";
    
    let formData = new FormData();
    formData.append("file", file);

    fetch(API_BASE + "/upload-image", { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.url) {
            document.getElementById("image").value = data.url;
            document.getElementById("preview_image").src = data.url;
            document.getElementById("preview_image").style.display = "block";
            statusEl.innerText = "✓ Uploaded";
            statusEl.style.color = "green";
        } else {
            statusEl.innerText = "Upload Failed: " + (data.error || "Unknown error");
            statusEl.style.color = "red";
        }
    })
    .catch(err => {
        statusEl.innerText = "Upload Failed: " + err;
        statusEl.style.color = "red";
    });
});

document.addEventListener("DOMContentLoaded", loadNews);
</script>
<?php } ?>