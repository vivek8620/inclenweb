<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-news',
        'Announcement',
        'Announcement',
        'manage_options',
        'announcement-manager',
        'announcement_manager_page'
    );
});


add_action('rest_api_init', function () {
    register_rest_route('announcement/v1', '/all-announcement', [
        'methods'  => 'GET',
        'callback' => 'get_all_announcement',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('announcement/v1', '/add-announcement', [
        'methods'  => 'POST',
        'callback' => 'add_announcement',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('announcement/v1', '/update-announcement/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_announcement',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('announcement/v1', '/delete-announcement/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_announcement',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('announcement/v1', '/upload-image-announcement', [
        'methods'  => 'POST',
        'callback' => 'upload_image_to_r2_announcement',
        'permission_callback' => '__return_true'
    ]);
});


function upload_image_to_r2_announcement() {
     global $accountId, $accessKey, $secretKey, $bucket;
    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-' . basename($_FILES['file']['name']);

    $publicUrlBaseanno = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/announcement";

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
            'Key' => 'admin/announcement/' . $fileName,
            'SourceFile' => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBaseanno . '/' . $fileName];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



function get_all_announcement() {
    global $wpdb;
    $table_annouce = $wpdb->prefix . 'announcement';
    return $wpdb->get_results("SELECT * FROM $table_annouce ORDER BY id DESC");
}


function add_announcement($request) {
    global $wpdb;
    $table_annouce = $wpdb->prefix . 'announcement';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table_annouce, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'purpose' => sanitize_text_field($params['purpose']),  // SAVES PURPOSE
        'image'   => esc_url_raw($params['image'])              // SAVES IMAGE PATH
    ]);

    return ['status' => 'success'];
}


function update_announcement($request) {
    global $wpdb;
    $table_annouce = $wpdb->prefix . 'announcement';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table_annouce, [
        'title'   => sanitize_text_field($params['title']),
        'slug'    => sanitize_title($params['title']),
        'content' => wp_kses_post($params['content']),
        'author'  => sanitize_text_field($params['author']),
        'purpose' => sanitize_text_field($params['purpose']),  // UPDATES PURPOSE
        'image'   => esc_url_raw($params['image'])              // UPDATES IMAGE PATH
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}


function delete_announcement($request) {
    global $wpdb;
    $table_annouce = $wpdb->prefix . 'announcement';
    $wpdb->delete($table_annouce, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}



function announcement_manager_page() { ?>

<div class="wrap">
<h1>Announcement Manager</h1>

<div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">

<h2>Add / Edit Announcement</h2>

<table class="form-table">

<tr>
<th>Title</th>
<td><input type="text" id="title" class="regular-text" placeholder="Enter announcement title"></td>
</tr>

<tr>
<th>Content</th>
<td><?php wp_editor('', 'content_editor'); ?></td>
</tr>

<tr>
<th>Author</th>
<td><input type="text" id="author" class="regular-text" placeholder="Author name"></td>
</tr>

<tr>
<th>Purpose <span style="color:red;">*</span></th>
<td>
<select id="purpose" class="regular-text" style="padding:6px;">
<option value="">-- Select Purpose --</option>
<option value="Academic Programs">Academic Programs</option>
<option value="Capacity Building">Capacity Building</option>
<option value="Career Opportunities">Career Opportunities</option>
<option value="Event">Event</option>
<option value="Research Awards">Research Awards</option>
</select>
</td>
</tr>

<tr>
<th>Image</th>
<td>
<input type="file" id="image_file" accept="image/*">
<input type="hidden" id="image">
<br><br>
<img id="preview_image" style="max-width:150px;display:none;border-radius:5px;border:1px solid #ddd;padding:5px;">
</td>
</tr>

</table>

<p>
<button class="button button-primary" onclick="addAnnouncement()">Save Announcement</button>
<button class="button" onclick="resetForm()" style="margin-left:10px;">Clear Form</button>
</p>

</div>

<div style="margin-top:30px;">
<h2>All Announcements</h2>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th>Title</th>
<th>Author</th>
<th>Purpose</th>
<th>Image</th>
<th>Action</th>
</tr>
</thead>
<tbody id="announcementList"></tbody>
</table>
</div>
</div>

<script>
const API_BASE_ANNO = "<?php echo site_url('/wp-json/announcement/v1'); ?>";
let editingId = null;


function loadAnnouncement(){
    fetch(API_BASE_ANNO+"/all-announcement")
    .then(res=>res.json())
    .then(data=>{
        let html='';
        data.forEach(item=>{
            html+=`
            <tr>
                <td><strong>${item.title}</strong></td>
                <td>${item.author}</td>
                <td><span style="background:#e7f3ff;padding:5px 10px;border-radius:3px;color:#0073aa;font-weight:bold;">${item.purpose}</span></td>
                <td>${item.image ? '<img src="' + item.image + '" width="60" style="border-radius:3px;border:1px solid #ddd;">' : '-'}</td>
                <td>
                    <button onclick='editAnnouncement(${JSON.stringify(item)})' class="button">Edit</button>
                    <button onclick='deleteAnnouncement(${item.id})' class="button button-link-delete">Delete</button>
                </td>
            </tr>`;
        });
        document.getElementById("announcementList").innerHTML=html;
    })
    .catch(err=>console.error("Error loading announcements:",err));
}


function addAnnouncement(){
    if(!document.getElementById("title").value.trim()){
        alert("Please enter title");
        return;
    }
    if(!document.getElementById("purpose").value){
        alert("Please select purpose");
        return;
    }

    let content=tinymce.get("content_editor").getContent();
    const data={
        title:document.getElementById("title").value,
        content:content,
        author:document.getElementById("author").value,
        purpose:document.getElementById("purpose").value,
        image:document.getElementById("image").value
    };

    let url=API_BASE_ANNO+"/add-announcement";
    if(editingId) url=API_BASE_ANNO+"/update-announcement/"+editingId;

    fetch(url,{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify(data)
    })
    .then(res=>res.json())
    .then(data=>{
        alert(editingId ? "Updated Successfully ✓" : "Saved Successfully ✓");
        editingId=null;
        resetForm();
        loadAnnouncement();
    })
    .catch(err=>alert("Error: "+err));
}


function editAnnouncement(item){
    editingId=item.id;
    document.getElementById("title").value=item.title;
    document.getElementById("author").value=item.author;
    document.getElementById("purpose").value=item.purpose;
    document.getElementById("image").value=item.image;
    document.getElementById("preview_image").src=item.image;
    document.getElementById("preview_image").style.display="block";
    tinymce.get("content_editor").setContent(item.content);
    window.scrollTo(0, 0);
}


function deleteAnnouncement(id){
    if(!confirm("Are you sure you want to delete this announcement?")) return;
    fetch(API_BASE_ANNO+"/delete-announcement/"+id,{method:"DELETE"})
    .then(res=>res.json())
    .then(data=>{
        alert("Deleted Successfully ✓");
        loadAnnouncement();
    })
    .catch(err=>alert("Error: "+err));
}


function resetForm(){
    editingId=null;
    document.getElementById("title").value="";
    document.getElementById("author").value="";
    document.getElementById("purpose").value="";
    document.getElementById("image").value="";
    document.getElementById("preview_image").style.display="none";
    tinymce.get("content_editor").setContent("");
}


document.addEventListener("DOMContentLoaded", function(){
    let imageFileInput = document.getElementById("image_file");
    if(imageFileInput){
        imageFileInput.addEventListener("change",function(){
            let file=this.files[0];
            if(!file) return;

            let formData=new FormData();
            formData.append("file",file);

            fetch(API_BASE_ANNO+"/upload-image-announcement",{method:"POST",body:formData})
            .then(res=>res.json())
            .then(data=>{
                if(data.url){
                    document.getElementById("image").value=data.url;
                    document.getElementById("preview_image").src=data.url;
                    document.getElementById("preview_image").style.display="block";
                }else{
                    alert("Upload Failed: " + (data.error || "Unknown error"));
                }
            })
            .catch(err=>alert("Upload Error: "+err));
        });
    }
    loadAnnouncement();
});
</script>
<?php } ?>