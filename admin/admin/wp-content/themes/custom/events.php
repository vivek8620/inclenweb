<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-news',
        'Event',
        'Event',
        'manage_options',
        'events-manager',
        'events_manager_page'
    );
});

add_action('rest_api_init', function () {

    register_rest_route('event/v1', '/all-events', [
        'methods'  => 'GET',
        'callback' => 'get_all_event',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('event/v1', '/add-event', [
        'methods'  => 'POST',
        'callback' => 'add_event',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('event/v1', '/update-event/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_event',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('event/v1', '/delete-event/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_event',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('event/v1', '/upload-image-event', [
        'methods'  => 'POST',
        'callback' => 'upload_image_to_r2_event',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('event/v1', '/upload-pdf-event', [
        'methods'  => 'POST',
        'callback' => 'upload_pdf_to_r2_event',
        'permission_callback' => '__return_true'
    ]);
});


function upload_image_to_r2_event() {
 global $accountId, $accessKey, $secretKey, $bucket;
    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);


    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/events";

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
            'Key' => 'admin/events/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


function upload_pdf_to_r2_event() {
 global $accountId, $accessKey, $secretKey, $bucket;
    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);

    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/events/pdf";

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
            'Key' => 'admin/events/pdf/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


function get_all_event() {
    global $wpdb;
    $table = $wpdb->prefix . 'events';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}


function add_event($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'events';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'purpose' => sanitize_text_field($params['purpose']),
        'start_time' => sanitize_text_field($params['start_time']),
        'end_time'   => sanitize_text_field($params['end_time']),
        'image'   => esc_url_raw($params['image']),
        'pdf'     => esc_url_raw($params['pdf'])
    ]);

    return ['status' => 'success'];
}


function update_event($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'events';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'purpose' => sanitize_text_field($params['purpose']),
        'start_time' => sanitize_text_field($params['start_time']),
        'end_time'   => sanitize_text_field($params['end_time']),
        'image'   => esc_url_raw($params['image']),
        'pdf'     => esc_url_raw($params['pdf'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}


function delete_event($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'events';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}



function events_manager_page() { ?>

<div class="wrap">
<h1>Events Manager</h1>

<style>
    .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
</style>

<div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">

<h2>Add / Edit Events</h2>

<table class="form-table">

<tr>
<th>Title</th>
<td>
    <input type="text" id="title" class="regular-text">
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
<th>Location</th>
<td>
<select id="author" class="regular-text">
<option value="">-- Select Location --</option>
<option value="India Habitat Centre, New Delhi">India Habitat Centre, New Delhi</option>
<option value="INCLEN Executive Office (Virtual/Hybrid)">INCLEN Executive Office (Virtual/Hybrid)</option>
</select>
<span id="author_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Start Time</th>
<td>
    <input type="time" id="start_time">
    <span id="start_time_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>End Time</th>
<td>
    <input type="time" id="end_time">
    <span id="end_time_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Event Type</th>
<td>
<select id="purpose" class="regular-text">
<option value="">-- Select Event Type --</option>
<option value="Summit">Summit</option>
<option value="Workshop">Workshop</option>
</select>
<span id="purpose_error" class="error-msg"></span>
</td>
</tr>

<tr>
<th>Image</th>
<td>
<input type="file" id="image_file" accept="image/*">
<input type="hidden" id="image">
<img id="preview_image" style="max-width:150px;display:none;">
</td>
</tr>

<tr>
<th>PDF</th>
<td>
<input type="file" id="pdf_file" accept="application/pdf">
<input type="hidden" id="pdf">
</td>
</tr>

</table>

<p>
<button class="button button-primary" onclick="addEvents()">Save Event</button>
</p>

</div>

<div style="margin-top:30px;">
<h2>All Events</h2>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th>Title</th>
<th>Location</th>
<th>Type</th>
<th>Image</th>
<th>PDF</th>
<th>Action</th>
</tr>
</thead>
<tbody id="eventsList"></tbody>
</table>
</div>
</div>

<script>

const API_BASE = "<?php echo site_url('/wp-json/event/v1'); ?>";
let editingId=null;

function loadEvents(){
fetch(API_BASE+"/all-events")
.then(res=>res.json())
.then(data=>{
let html='';
data.forEach(item=>{
html+=`
<tr>
<td>${item.title}</td>
<td>${item.author}</td>
<td>${item.purpose}</td>
<td>${item.image ? '<img src="' + item.image + '" width="60" style="border-radius:3px;border:1px solid #ddd;">' : '-'}</td>
<td>${item.pdf ? '<a href="'+item.pdf+'" target="_blank">View PDF</a>' : '-'}</td>
<td>
<button class="button button-small edit-btn" onclick='editEvent(${JSON.stringify(item)})'>Edit</button>
<button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteEvents(${item.id})'>Delete</button>
</td>
</tr>`;
});
document.getElementById("eventsList").innerHTML=html;
});
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}
 
function addEvents(){
    clearErrors();
    
    const titleVal = document.getElementById("title").value.trim();
    const contentVal = tinymce.get("content_editor").getContent().trim();
    const authorVal = document.getElementById("author").value;
    const startTimeVal = document.getElementById("start_time").value;
    const endTimeVal = document.getElementById("end_time").value;
    const purposeVal = document.getElementById("purpose").value;
    const imageVal = document.getElementById("image").value;
    const pdfVal = document.getElementById("pdf").value;
    
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
        document.getElementById("author_error").innerText = "Location is required.";
        document.getElementById("author").classList.add("input-error");
        isValid = false;
    }
    
    if (!startTimeVal) {
        document.getElementById("start_time_error").innerText = "Start time is required.";
        document.getElementById("start_time").classList.add("input-error");
        isValid = false;
    }
    
    if (!endTimeVal) {
        document.getElementById("end_time_error").innerText = "End time is required.";
        document.getElementById("end_time").classList.add("input-error");
        isValid = false;
    }
    
    if (!purposeVal) {
        document.getElementById("purpose_error").innerText = "Event type is required.";
        document.getElementById("purpose").classList.add("input-error");
        isValid = false;
    }
    
    if (!isValid) return;

    const data={
        title: titleVal,
        content: contentVal,
        author: authorVal,
        start_time: startTimeVal,
        end_time: endTimeVal,
        purpose: purposeVal,
        image: imageVal,
        pdf: pdfVal
    };

    let url=API_BASE+"/add-event";
    if(editingId) url=API_BASE+"/update-event/"+editingId;

    fetch(url,{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify(data)
    })
    .then(res=>res.json())
    .then(()=>{
        alert("Saved successfully!");
        editingId = null;
        // Reset form
        document.getElementById("title").value = "";
        document.getElementById("author").value = "";
        document.getElementById("start_time").value = "";
        document.getElementById("end_time").value = "";
        document.getElementById("purpose").value = "";
        document.getElementById("image").value = "";
        document.getElementById("pdf").value = "";
        document.getElementById("preview_image").style.display = "none";
        tinymce.get("content_editor").setContent("");
        loadEvents();
    });
}

function editEvent(item) {
    clearErrors();
    editingId = item.id;

    document.getElementById("title").value = item.title || "";
    document.getElementById("author").value = item.author || "";
    document.getElementById("start_time").value = item.start_time || "";
    document.getElementById("end_time").value = item.end_time || "";
    document.getElementById("purpose").value = item.purpose || "";
    document.getElementById("image").value = item.image || "";
    document.getElementById("pdf").value = item.pdf || "";

    if (item.image) {
        document.getElementById("preview_image").src = item.image;
        document.getElementById("preview_image").style.display = "block";
    } else {
        document.getElementById("preview_image").style.display = "none";
    }

    if (typeof tinymce !== "undefined" && tinymce.get("content_editor")) {
        tinymce.get("content_editor").setContent(item.content || "");
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteEvents(id){
    if(!confirm("Are you sure you want to delete this event?")) return;
    fetch(API_BASE+"/delete-event/"+id,{method:"DELETE"})
    .then(()=>loadEvents());
}

/* Upload Image */
document.getElementById("image_file").addEventListener("change",function(){
    let file=this.files[0];
    let fd=new FormData();
    fd.append("file",file);

    fetch(API_BASE+"/upload-image-event",{method:"POST",body:fd})
    .then(res=>res.json())
    .then(data=>{
        if(data.url){
            document.getElementById("image").value=data.url;
            document.getElementById("preview_image").src=data.url;
            document.getElementById("preview_image").style.display="block";
        }
    });
});

/* Upload PDF */
document.getElementById("pdf_file").addEventListener("change",function(){
    let file=this.files[0];
    let fd=new FormData();
    fd.append("file",file);

    fetch(API_BASE+"/upload-pdf-event",{method:"POST",body:fd})
    .then(res=>res.json())
    .then(data=>{
        if(data.url){
            document.getElementById("pdf").value=data.url;
            alert("PDF Uploaded Successfully");
        }
    });
});

document.addEventListener("DOMContentLoaded", loadEvents);
</script>
<?php } ?>