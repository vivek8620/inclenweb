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
     global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);


    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin";
    try {

        $client = new S3Client([
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



function get_all_news() {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'image'   => esc_url_raw($params['image'])
    ]);

    return ['status' => 'success'];
}

function update_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'image'   => esc_url_raw($params['image'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_news($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'news';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}



function news_manager_page() { ?>

<div class="wrap">
<h1>News Manager</h1>

<style>
    .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
</style>

<div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">

<h2>Add / Edit News</h2>

<table class="form-table">

<tr>
<th>Title</th>
<td>
    <input type="text" id="title" class="regular-text" required>
    <span id="title_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Content</th>
<td>
    <?php wp_editor('', 'content_editor'); ?>
    <span id="content_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Author</th>
<td>
    <input type="text" id="author" class="regular-text">
    <span id="author_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Image</th>
<td>
<input type="file" id="image_file">
<input type="hidden" id="image">
<br><br>
<img id="preview_image" style="max-width:150px;display:none;">
</td>
</tr>

</table>

<p>
<button class="button button-primary" onclick="addNews()">Save News</button>
</p>

</div>

<div style="margin-top:30px;">
<h2>All News</h2>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th>Title</th>
<th>Author</th>
<th>Image</th>
<th>Action</th>
</tr>
</thead>
<tbody id="newsList"></tbody>
</table>
</div>
</div>

<script>
const API_BASE = "<?php echo site_url('/wp-json/news/v1'); ?>";
let editingId = null;

function loadNews(){
fetch(API_BASE+"/all")
.then(res=>res.json())
.then(data=>{
let html='';
data.forEach(item=>{
html+=`
<tr>
<td>${item.title}</td>
<td>${item.author}</td>
<td>${item.image?'<img src="'+item.image+'" width="60">':''}</td>
<td>
<button class="button button-small edit-btn" onclick='editNews(${JSON.stringify(item)})'>Edit</button>
<button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteNews(${item.id})'>Delete</button>
</td>
</tr>`;
});
document.getElementById("newsList").innerHTML=html;
});
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}

function addNews(){
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
    
    if (!contentVal) {
        document.getElementById("content_error").innerText = "Content is required.";
        document.getElementById("wp-content_editor-wrap").classList.add("input-error");
        isValid = false;
    }
    
    if (!authorVal) {
        document.getElementById("author_error").innerText = "Author is required.";
        document.getElementById("author").classList.add("input-error");
        isValid = false;
    }
    
    if (!isValid) return;

    const data={
        title: titleVal,
        content: contentVal,
        author: authorVal,
        image: imageVal
    };
    
    let url=API_BASE+"/add";
    if(editingId) url=API_BASE+"/update/"+editingId;

    fetch(url,{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify(data)
    })
    .then(()=>{ 
        alert("Saved successfully!"); 
        editingId=null; 
        document.getElementById("title").value = '';
        document.getElementById("author").value = '';
        document.getElementById("image").value = '';
        document.getElementById("preview_image").style.display = "none";
        tinymce.get("content_editor").setContent('');
        loadNews(); 
    });
}

function editNews(item){
    clearErrors();
    editingId=item.id;
    document.getElementById("title").value=item.title;
    document.getElementById("author").value=item.author;
    document.getElementById("image").value=item.image;
    document.getElementById("preview_image").src=item.image;
    document.getElementById("preview_image").style.display="block";
    tinymce.get("content_editor").setContent(item.content);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteNews(id){
if(!confirm("Are you sure you want to delete this news item?")) return;
fetch(API_BASE+"/delete/"+id,{method:"DELETE"})
.then(()=>loadNews());
}



document.getElementById("image_file").addEventListener("change",function(){
let file=this.files[0];
let formData=new FormData();
formData.append("file",file);

fetch(API_BASE+"/upload-image",{method:"POST",body:formData})
.then(res=>res.json())
.then(data=>{
if(data.url){
document.getElementById("image").value=data.url;
document.getElementById("preview_image").src=data.url;
document.getElementById("preview_image").style.display="block";
}else{
alert("Upload Failed");
}
});
});

document.addEventListener("DOMContentLoaded",loadNews);
</script>
<?php } ?>