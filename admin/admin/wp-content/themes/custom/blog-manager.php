<?php
/**
 * Blog Manager for INCLEN Admin Panel
 */

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
    register_rest_route('blogs/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_blogs',
        'permission_callback' => 'is_user_admin_check'
    ]);

    // Public website listing. Blog management remains restricted to admins.
    register_rest_route('blogs/v1', '/public', [
        'methods'             => 'GET',
        'callback'            => 'get_public_blogs',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('blogs/v1', '/add', [
        'methods'             => 'POST',
        'callback'            => 'add_blog',
        'permission_callback' => 'is_user_admin_check'
    ]);

    register_rest_route('blogs/v1', '/update/(?P<id>\d+)', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_blog',
        'permission_callback' => 'is_user_admin_check'
    ]);

    register_rest_route('blogs/v1', '/delete/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_blog',
        'permission_callback' => 'is_user_admin_check'
    ]);

    register_rest_route('blogs/v1', '/upload-image', [
        'methods'             => 'POST',
        'callback'            => 'upload_image_to_r2_blog',
        'permission_callback' => 'is_user_admin_check'
    ]);
});

function upload_image_to_r2_blog() {
    if (!isset($_FILES['file'])) {
        return new WP_Error('blog_file_missing', 'No file uploaded.', ['status' => 400]);
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);

    try {
        $client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => 'auto',
            'endpoint'    => "https://" . R2_ACCOUNT_ID . ".r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => R2_ACCESS_KEY,
                'secret' => R2_SECRET_KEY,
            ],
        ]);

        $client->putObject([
            'Bucket'      => R2_BUCKET,
            'Key'         => 'admin/blogs/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => R2_PUBLIC_URL . '/blogs/' . $fileName];
    } catch (\Exception $e) {
        return new WP_Error('blog_upload_failed', 'Image upload failed.', ['status' => 500]);
    }
}

function get_all_blogs() {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function get_public_blogs() {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';

    return $wpdb->get_results("SELECT id, title, slug, content, author, image, banner_image, created_at FROM $table ORDER BY id DESC");
}

function blog_request_data($request) {
    $params = $request->get_json_params();
    if (!is_array($params)) {
        return new WP_Error('blog_invalid_data', 'A JSON request body is required.', ['status' => 400]);
    }

    $required_fields = ['title', 'author', 'content'];
    foreach ($required_fields as $field) {
        if (empty(trim((string) ($params[$field] ?? '')))) {
            return new WP_Error('blog_required_field', ucfirst($field) . ' is required.', ['status' => 400]);
        }
    }

    return $params;
}

function add_blog($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    $params = blog_request_data($request);
    if (is_wp_error($params)) {
        return $params;
    }

    $inserted = $wpdb->insert($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'image'   => esc_url_raw($params['image'] ?? ''),
        'banner_image' => esc_url_raw($params['banner_image'] ?? '')
    ]);

    if ($inserted === false) {
        return new WP_Error('blog_create_failed', 'Blog could not be created.', ['status' => 500]);
    }

    return new WP_REST_Response(['status' => 'created', 'id' => (int) $wpdb->insert_id], 201);
}

function update_blog($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    $params = blog_request_data($request);
    if (is_wp_error($params)) {
        return $params;
    }

    $id = absint($request['id']);
    if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $id))) {
        return new WP_Error('blog_not_found', 'Blog not found.', ['status' => 404]);
    }

    $updated = $wpdb->update($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'image'   => esc_url_raw($params['image'] ?? ''),
        'banner_image' => esc_url_raw($params['banner_image'] ?? '')
    ], ['id' => $id]);

    if ($updated === false) {
        return new WP_Error('blog_update_failed', 'Blog could not be updated.', ['status' => 500]);
    }

    return ['status' => 'updated', 'id' => $id];
}

function delete_blog($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'blogs';
    $deleted = $wpdb->delete($table, ['id' => absint($request['id'])]);
    if ($deleted === false) {
        return new WP_Error('blog_delete_failed', 'Blog could not be deleted.', ['status' => 500]);
    }
    if ($deleted === 0) {
        return new WP_Error('blog_not_found', 'Blog not found.', ['status' => 404]);
    }

    return ['status' => 'deleted'];
}

function blog_manager_page() { ?>
<div class="wrap">
    <h1>Blog Manager</h1>

    <style>
        .error-msg {
            color: #d63638;
            font-size: 13px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
        .input-error {
            border-color: #d63638 !important;
            box-shadow: 0 0 2px rgba(214, 54, 56, 0.8) !important;
        }
        .blog-form-container {
            background: #fff;
            padding: 24px;
            margin-top: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .preview-img-container {
            margin-top: 15px;
        }
        .preview-img {
            max-width: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            background: #f9f9f9;
        }
    </style>

    <div class="blog-form-container">
        <h2 id="form-title-heading">Add New Blog Post</h2>
        <table class="form-table">
            <tr>
                <th><label for="title">Title <span style="color:#d63638;">*</span></label></th>
                <td>
                    <input type="text" id="title" class="regular-text" placeholder="Enter blog title" required>
                    <span id="title_error" class="error-msg"></span>
                </td>
            </tr>

            <tr>
                <th><label for="author">Author <span style="color:#d63638;">*</span></label></th>
                <td>
                    <input type="text" id="author" class="regular-text" placeholder="Author name" required>
                    <span id="author_error" class="error-msg"></span>
                </td>
            </tr>

            <tr>
                <th><label for="content_editor">Content <span style="color:#d63638;">*</span></label></th>
                <td>
                    <?php wp_editor('', 'content_editor', [
                        'media_buttons' => false,
                        'textarea_rows' => 12,
                        'tinymce'       => [
                            'wpautop' => true,
                            'remove_linebreaks' => false,
                        ]
                    ]); ?>
                    <span id="content_error" class="error-msg"></span>
                </td>
            </tr>

            <tr>
                <th><label for="image_file">Cover Image</label></th>
                <td>
                    <input type="file" id="image_file" accept="image/*">
                    <input type="hidden" id="image">
                    <div class="preview-img-container">
                        <img id="preview_image" class="preview-img" style="display:none;" alt="Preview image">
                    </div>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button class="button button-primary" id="save-blog-btn" onclick="saveBlog()">Save Blog</button>
            <button class="button" onclick="resetBlogForm()" style="margin-left: 10px;">Clear Form</button>
        </p>
    </div>

    <div style="margin-top: 40px;">
        <h2>All Blog Posts</h2>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th style="width: 25%;">Title</th>
                    <th style="width: 12%;">Author</th>
                    <th style="width: 15%;">Cover Image</th>
                    <th style="width: 15%;">Published Date</th>
                    <th style="width: 18%;">Blog URL (Link)</th>
                    <th style="width: 15%;">Actions</th>
                </tr>
            </thead>
            <tbody id="blogList">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">Loading blogs...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// The project does not use pretty-permalink REST routes locally, so use the
// WordPress query-route format. This works both with and without rewrite rules.
const API_BASE_BLOG = "<?php echo esc_js(site_url('/index.php?rest_route=/blogs/v1')); ?>";
const BLOG_REST_NONCE = "<?php echo esc_js(wp_create_nonce('wp_rest')); ?>";
const WP_SITE_URL = "<?php echo esc_js(site_url()); ?>";
const SITE_ROOT_URL = (function() {
    const path = window.location.pathname;
    const adminIdx = path.indexOf('/admin/admin/');
    const basePath = adminIdx !== -1 ? path.substring(0, adminIdx) : '';
    return window.location.origin + basePath + '/';
})();
let editingId = null;
let blogsById = {};

function escapeBlogHtml(value) {
    const element = document.createElement('div');
    element.textContent = value || '';
    return element.innerHTML;
}

function copyBlogLink(btn, linkText) {
    navigator.clipboard.writeText(linkText).then(() => {
        const originalText = btn.innerText;
        btn.innerText = "Copied!";
        btn.style.backgroundColor = "#46b450";
        btn.style.color = "#fff";
        btn.style.borderColor = "#46b450";
        setTimeout(() => {
            btn.innerText = originalText;
            btn.style.backgroundColor = "";
            btn.style.color = "";
            btn.style.borderColor = "";
        }, 1500);
    }).catch(err => {
        console.error("Could not copy text: ", err);
    });
}

async function blogApiRequest(path, options = {}) {
    const headers = { 'X-WP-Nonce': BLOG_REST_NONCE, ...(options.headers || {}) };
    const response = await fetch(API_BASE_BLOG + path, {
        credentials: 'same-origin',
        ...options,
        headers
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'The blog request failed.');
    }

    return data;
}

function loadBlogs() {
    blogApiRequest("/all")
        .then(data => {
            let html = '';
            blogsById = {};
            if (data.length === 0) {
                html = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #666;">No blogs found. Add your first blog above!</td></tr>`;
            } else {
                data.forEach(item => {
                    blogsById[item.id] = item;
                    const formattedDate = new Date(item.created_at).toLocaleDateString(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const blogPath = `/about/blog/post/?id=${item.slug || item.id}`;
                    const fullBlogUrl = `${SITE_ROOT_URL}about/blog/post/?id=${item.slug || item.id}`;
 
                    html += `
                    <tr>
                        <td><strong>${escapeBlogHtml(item.title)}</strong></td>
                        <td>${escapeBlogHtml(item.author)}</td>
                        <td>${item.image ? `<img src="${escapeBlogHtml(item.image)}" class="preview-img" style="max-height: 60px; max-width: 100px;" alt="Blog cover image">` : '<span style="color:#aaa; font-style:italic;">No image</span>'}</td>
                        <td>${formattedDate}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <input type="text" class="regular-text" style="width: 120px; margin: 0; padding: 3px 8px; font-size: 12px; height: 28px; min-height: 28px;" readonly value="${blogPath}">
                                <button class="button button-small" onclick="copyBlogLink(this, '${blogPath}')" title="Copy Link to Clipboard">Copy</button>
                            </div>
                        </td>
                        <td>
                            <button class="button button-small" onclick="editBlogById(${Number(item.id)})">Edit</button>
                            <button class="button button-small button-link-delete" style="color: #d63638; margin-left: 5px;" onclick='deleteBlog(${item.id})'>Delete</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById("blogList").innerHTML = html;
        })
        .catch(err => {
            console.error("Error loading blogs:", err);
            document.getElementById("blogList").innerHTML = `<tr><td colspan="6" style="text-align: center; color: red;">Failed to load blogs.</td></tr>`;
        });
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    document.getElementById("wp-content_editor-wrap")?.classList.remove("input-error");
}

function saveBlog() {
    clearErrors();

    const titleVal = document.getElementById("title").value.trim();
    const authorVal = document.getElementById("author").value.trim();
    const contentVal = tinymce.get("content_editor").getContent().trim();
    const imageVal = document.getElementById("image").value;

    let isValid = true;

    if (!titleVal) {
        document.getElementById("title_error").innerText = "Title is required.";
        document.getElementById("title").classList.add("input-error");
        isValid = false;
    }

    if (!authorVal) {
        document.getElementById("author_error").innerText = "Author is required.";
        document.getElementById("author").classList.add("input-error");
        isValid = false;
    }

    if (!contentVal) {
        document.getElementById("content_error").innerText = "Content is required.";
        document.getElementById("wp-content_editor-wrap")?.classList.add("input-error");
        isValid = false;
    }

    if (!isValid) return;

    const data = {
        title: titleVal,
        author: authorVal,
        content: contentVal,
        image: imageVal,
        banner_image: document.getElementById("banner_image") ? document.getElementById("banner_image").value : "",
        banner_text: document.getElementById("banner_text") ? document.getElementById("banner_text").value : ""
    };

    let url = API_BASE_BLOG + "/add";
    if (editingId) url = API_BASE_BLOG + "/update/" + editingId;

    const saveBtn = document.getElementById("save-blog-btn");
    saveBtn.disabled = true;
    saveBtn.innerText = "Saving...";

    blogApiRequest(url.replace(API_BASE_BLOG, ''), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(() => {
        alert(editingId ? "Blog updated successfully!" : "Blog created successfully!");
        resetBlogForm();
        loadBlogs();
    })
    .catch(err => {
        console.error("Error saving blog:", err);
        alert(err.message || "An error occurred while saving the blog.");
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerText = "Save Blog";
    });
}

function editBlog(item) {
    clearErrors();
    editingId = item.id;
    document.getElementById("form-title-heading").innerText = "Edit Blog Post";
    document.getElementById("title").value = item.title;
    document.getElementById("author").value = item.author;
    document.getElementById("image").value = item.image || '';
    if (document.getElementById("banner_image")) {
        document.getElementById("banner_image").value = item.banner_image || '';
    }
    if (document.getElementById("banner_text")) {
        document.getElementById("banner_text").value = item.banner_text || '';
    }
    
    if (item.image) {
        document.getElementById("preview_image").src = item.image;
        document.getElementById("preview_image").style.display = "block";
    } else {
        document.getElementById("preview_image").style.display = "none";
    }

    if (item.banner_image) {
        document.getElementById("preview_banner_image").src = item.banner_image;
        document.getElementById("preview_banner_image").style.display = "block";
    } else {
        document.getElementById("preview_banner_image").style.display = "none";
    }
    
    tinymce.get("content_editor").setContent(item.content);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function editBlogById(id) {
    if (blogsById[id]) {
        editBlog(blogsById[id]);
    }
}

function deleteBlog(id) {
    if (!confirm("Are you sure you want to delete this blog post?")) return;
    
    blogApiRequest("/delete/" + id, { method: "DELETE" })
        .then(() => {
            alert("Blog post deleted successfully!");
            loadBlogs();
        })
        .catch(err => {
            console.error("Error deleting blog:", err);
        alert(err.message || "An error occurred while deleting the blog.");
        });
}

function resetBlogForm() {
    editingId = null;
    document.getElementById("form-title-heading").innerText = "Add New Blog Post";
    document.getElementById("title").value = '';
    document.getElementById("author").value = '';
    document.getElementById("image").value = '';
    document.getElementById("image_file").value = '';
    document.getElementById("preview_image").style.display = "none";
    if (document.getElementById("banner_image")) {
        document.getElementById("banner_image").value = '';
    }
    document.getElementById("banner_image_file").value = '';
    document.getElementById("preview_banner_image").style.display = "none";
    if (document.getElementById("banner_text")) {
        document.getElementById("banner_text").value = '';
    }
    tinymce.get("content_editor").setContent('');
    clearErrors();
}

document.addEventListener("DOMContentLoaded", function () {
    function bindImageUpload(fileInputId, hiddenInputId, previewImageId) {
        const fileInput = document.getElementById(fileInputId);
        if (!fileInput) return;

        fileInput.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append("file", file);

            const previewImg = document.getElementById(previewImageId);
            previewImg.src = '';
            previewImg.style.display = "none";

            blogApiRequest("/upload-image", {
                method: "POST",
                body: formData
            })
            .then(data => {
                if (data.url) {
                    document.getElementById(hiddenInputId).value = data.url;
                    previewImg.src = data.url;
                    previewImg.style.display = "block";
                } else {
                    alert("Upload failed: " + (data.error || "Unknown error"));
                }
            })
            .catch(err => {
                console.error("Error uploading image:", err);
                alert(err.message || "An error occurred during file upload.");
            });
        });
    }

    bindImageUpload("image_file", "image", "preview_image");
    bindImageUpload("banner_image_file", "banner_image", "preview_banner_image");

    loadBlogs();
});
</script>
<?php } ?>
