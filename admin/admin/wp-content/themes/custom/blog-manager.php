<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-news',
        'Blog Manager',
        'Blog Manager',
        'manage_options',
        'blog-manager',
        'blog_manager_page'
    );
});


add_action('rest_api_init', function () {

    // Public endpoint used by the frontend blog listing & post pages
    register_rest_route('blogs/v1', '/public', [
        'methods'             => 'GET',
        'callback'            => 'get_all_blogs_public',
        'permission_callback' => '__return_true',
    ]);

    // Admin CRUD endpoints
    register_rest_route('blogs/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_blogs',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('blogs/v1', '/add', [
        'methods'             => 'POST',
        'callback'            => 'add_blog',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('blogs/v1', '/update/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'update_blog',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('blogs/v1', '/delete/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_blog',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('blogs/v1', '/upload-image', [
        'methods'             => 'POST',
        'callback'            => 'upload_blog_image_to_r2',
        'permission_callback' => '__return_true',
    ]);
});


// Image upload
function upload_blog_image_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);
    $publicUrlBase = 'https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/blogs';

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
            'Key'         => 'admin/blogs/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type'],
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


// REST callbacks
function get_all_blogs_public() {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
}

function get_all_blogs() {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_blog($request) {
    global $wpdb;
    $table  = $wpdb->prefix . 'blogs';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'title'        => sanitize_text_field($params['title']),
        'slug'         => sanitize_title($params['title']),
        'content'      => wp_kses_post($params['content']),
        'author'       => sanitize_text_field($params['author']),
        'image'        => esc_url_raw($params['image']),
        'banner_image' => esc_url_raw($params['banner_image'] ?? ''),
    ]);

    return ['status' => 'success'];
}

function update_blog($request) {
    global $wpdb;
    $table  = $wpdb->prefix . 'blogs';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'title'        => sanitize_text_field($params['title']),
        'slug'         => sanitize_title($params['title']),
        'content'      => wp_kses_post($params['content']),
        'author'       => sanitize_text_field($params['author']),
        'image'        => esc_url_raw($params['image']),
        'banner_image' => esc_url_raw($params['banner_image'] ?? ''),
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_blog($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}


// Admin UI
function blog_manager_page() { ?>

<div class="wrap">
<h1>Blog Manager</h1>

<style>
    .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
    .input-error { border-color: red !important; }
</style>

<div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">

<h2 id="blog-form-heading">Add / Edit Blog</h2>

<table class="form-table">

<tr>
<th>Title <span style="color:red;">*</span></th>
<td>
    <input type="text" id="blog_title" class="regular-text" placeholder="Enter blog title">
    <span id="blog_title_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>URL / Slug <span style="color:red;">*</span></th>
<td>
    <input type="text" id="blog_slug" class="regular-text" placeholder="e.g. global-health-research">
    <p class="description" style="margin-top:4px;">Blog ka URL path (sirf lowercase letters, numbers aur hyphens). Example: <code>my-blog-post</code></p>
    <span id="blog_slug_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Author</th>
<td>
    <input type="text" id="blog_author" class="regular-text" placeholder="Author name">
</td>
</tr>

<tr>
<th>Content <span style="color:red;">*</span></th>
<td>
    <?php wp_editor('', 'blog_content_editor'); ?>
    <span id="blog_content_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Card Image</th>
<td>
    <input type="file" id="blog_image_file" accept="image/*">
    <input type="hidden" id="blog_image">
    <br><br>
    <img id="blog_preview_image" style="max-width:200px;display:none;border-radius:5px;border:1px solid #ddd;padding:5px;">
    <span id="blog_image_status" style="font-size:12px;color:#666;margin-left:8px;"></span>
</td>
</tr>

</table>

<p>
<button class="button button-primary" onclick="saveBlog()">Save Blog</button>
<button class="button" onclick="resetBlogForm()" style="margin-left:10px;">Clear Form</button>
</p>

</div>

<div style="margin-top:30px;">
<h2>All Blogs</h2>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th style="width:28%;">Title</th>
<th style="width:12%;">Author</th>
<th style="width:8%;">Card Image</th>
<th style="width:18%;">URL Path</th>
<th style="width:10%;">Date</th>
<th style="width:10%;">Actions</th>
</tr>
</thead>
<tbody id="blogList"><tr><td colspan="6" style="text-align:center;color:#999;">Loading&hellip;</td></tr></tbody>
</table>
</div>

</div>


<script>
const BLOG_API = "<?php echo site_url('/wp-json/blogs/v1'); ?>";
// Frontend site root (e.g. http://localhost:3000 or https://inclentrust.org)
const FRONTEND_ROOT = "<?php
$h = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'localhost') !== false || strpos($_SERVER['HTTP_HOST'],'127.0.0.1') !== false))
    ? 'http://localhost:3000'
    : 'https://inclentrust.org';
echo $h;
?>";
function resolveImgUrl(url) {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;   // already absolute
    return FRONTEND_ROOT + (url.startsWith('/') ? url : '/' + url);
}
let editingBlogId = null;
const blogDataMap = {}; // stores blog objects keyed by id

function loadBlogs() {
    fetch(BLOG_API + '/all')
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (!data || !data.length) {
                html = '<tr><td colspan="6" style="text-align:center;color:#999;">No blog posts yet.</td></tr>';
            } else {
                data.forEach(item => {
                    blogDataMap[item.id] = item; // store in map
                    const date = item.created_at ? new Date(item.created_at).toLocaleDateString('en-IN', {year:'numeric',month:'short',day:'numeric'}) : '-';
                    const resolvedImg = resolveImgUrl(item.image);
                    const imgHtml = resolvedImg
                        ? `<img src="${resolvedImg}" width="60" height="45" style="border-radius:5px;border:1px solid #ddd;object-fit:cover;flex-shrink:0;">`
                        : `<span style="color:#ccc;font-size:11px;">No image</span>`;
                    const blogUrl = item.slug ? '/about/blog/post/?id=' + item.slug : '';
                    const urlCell = blogUrl
                        ? `<div style="display:flex;align-items:center;gap:6px;">
                              <span style="
                                  font-size:10.5px;font-family:monospace;
                                  color:#555;white-space:nowrap;
                                  max-width:140px;overflow:hidden;text-overflow:ellipsis;
                                  display:inline-block;
                              " title="${blogUrl}">${blogUrl}</span>
                              <button onclick="copyBlogUrl('${blogUrl}', this)" title="Copy URL" style="
                                  background:#fff;border:1px solid #ddd;border-radius:6px;
                                  padding:3px 6px;cursor:pointer;font-size:12px;
                                  color:#555;line-height:1;flex-shrink:0;
                                  transition:all 0.2s;
                              " onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">📋</button>
                           </div>`
                        : '';
                    html += `
                    <tr>
                        <td><strong>${item.title}</strong></td>
                        <td>${item.author || '-'}</td>
                        <td style="vertical-align:middle;">${imgHtml}</td>
                        <td style="vertical-align:middle;">${urlCell}</td>
                        <td>${date}</td>
                        <td>
                            <button onclick="editBlog(${item.id})" class="button button-small">Edit</button>
                            <button onclick="deleteBlog(${item.id})" class="button button-small" style="color:#b32d2e;margin-left:4px;">Delete</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('blogList').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('blogList').innerHTML = '<tr><td colspan="5" style="color:red;">Error loading blogs: ' + err + '</td></tr>';
        });
}

function clearBlogErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}

function copyBlogUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✓';
        btn.style.color = '#27ae60';
        btn.style.borderColor = '#27ae60';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = '#555'; btn.style.borderColor = '#ddd'; }, 1500);
    }).catch(() => alert('Copy failed. URL: ' + url));
}

function saveBlog() {

    clearBlogErrors();
    const titleVal   = document.getElementById('blog_title').value.trim();
    const authorVal  = document.getElementById('blog_author').value.trim();
    const editor     = tinymce.get('blog_content_editor');
    const contentVal = editor ? editor.getContent().trim() : '';
    const imageVal   = document.getElementById('blog_image').value;

    let isValid = true;

    const slugVal = document.getElementById('blog_slug').value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');

    if (!titleVal) {
        document.getElementById('blog_title_error').innerText = 'Title is required.';
        document.getElementById('blog_title').classList.add('input-error');
        isValid = false;
    }
    if (!slugVal) {
        document.getElementById('blog_slug_error').innerText = 'URL/Slug is required.';
        document.getElementById('blog_slug').classList.add('input-error');
        isValid = false;
    }
    if (!contentVal) {
        document.getElementById('blog_content_error').innerText = 'Content is required.';
        const wrap = document.getElementById('wp-blog_content_editor-wrap');
        if (wrap) wrap.classList.add('input-error');
        isValid = false;
    }
    if (!isValid) return;

    const payload = { title: titleVal, content: contentVal, author: authorVal, image: imageVal, slug: slugVal };
    const url = editingBlogId ? BLOG_API + '/update/' + editingBlogId : BLOG_API + '/add';

    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(res => res.json())
        .then(() => {
            alert(editingBlogId ? 'Blog updated successfully \u2713' : 'Blog saved successfully \u2713');
            resetBlogForm();
            loadBlogs();
        })
        .catch(err => alert('Error saving blog: ' + err));
}

function editBlog(id) {
    const item = blogDataMap[id];
    if (!item) { alert('Blog data not found, please refresh.'); return; }
    clearBlogErrors();
    editingBlogId = item.id;
    document.getElementById('blog-form-heading').innerText = 'Edit Blog';
    document.getElementById('blog_title').value  = item.title || '';
    document.getElementById('blog_slug').value   = item.slug  || '';
    document.getElementById('blog_author').value = item.author || '';
    document.getElementById('blog_image').value  = item.image || '';

    const prevImg = document.getElementById('blog_preview_image');
    if (item.image) { prevImg.src = resolveImgUrl(item.image); prevImg.style.display = 'block'; }
    else { prevImg.style.display = 'none'; }

    const editor = tinymce.get('blog_content_editor');
    if (editor) editor.setContent(item.content || '');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteBlog(id) {
    if (!confirm('Are you sure you want to delete this blog post?')) return;
    fetch(BLOG_API + '/delete/' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(() => { alert('Deleted successfully \u2713'); loadBlogs(); })
        .catch(err => alert('Error: ' + err));
}

function resetBlogForm() {
    editingBlogId = null;
    document.getElementById('blog-form-heading').innerText = 'Add / Edit Blog';
    document.getElementById('blog_title').value  = '';
    document.getElementById('blog_slug').value   = '';
    document.getElementById('blog_author').value = '';
    document.getElementById('blog_image').value  = '';
    document.getElementById('blog_preview_image').style.display = 'none';
    document.getElementById('blog_image_status').innerText  = '';
    const editor = tinymce.get('blog_content_editor');
    if (editor) editor.setContent('');
    clearBlogErrors();
}

function setupBlogImageUpload(inputId, hiddenId, previewId, statusId) {
    document.getElementById(inputId).addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        document.getElementById(statusId).innerText = 'Uploading\u2026';
        const formData = new FormData();
        formData.append('file', file);
        fetch(BLOG_API + '/upload-image', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.url) {
                    document.getElementById(hiddenId).value = data.url;
                    const p = document.getElementById(previewId);
                    p.src = data.url; p.style.display = 'block';
                    document.getElementById(statusId).innerText = 'Uploaded \u2713';
                } else {
                    document.getElementById(statusId).innerText = 'Upload failed: ' + (data.error || 'Unknown error');
                }
            })
            .catch(err => { document.getElementById(statusId).innerText = 'Upload error: ' + err; });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setupBlogImageUpload('blog_image_file', 'blog_image', 'blog_preview_image', 'blog_image_status');
    loadBlogs();
});
</script>
<?php } ?>
