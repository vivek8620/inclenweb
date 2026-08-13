<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'group-partners',
        'Partners',
        'Partners',
        'manage_options',
        'partners-manager',
        'partners_manager_page'
    );

    add_submenu_page(
        'group-partners',
        'Industry Partnerships',
        'Industry Partnerships',
        'manage_options',
        'industry-partnerships',
        'industry_partnerships_page'
    );

    add_submenu_page(
        'group-partners',
        'Research Partnerships',
        'Research Partnerships',
        'manage_options',
        'research-partnerships',
        'research_partnerships_page'
    );
});

// REST API setup for Partners
add_action('rest_api_init', function () {
    register_rest_route('partners/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_partners',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partners/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_partner',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partners/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_partner',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partners/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_partner',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partners/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_partner_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_partner_image_to_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-partner-' . basename($_FILES['file']['name']);
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

function get_all_partners() {
    global $wpdb;
    $table = $wpdb->prefix . 'partners';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partners';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'about'        => sanitize_textarea_field($params['about']),
        'description'  => wp_kses_post($params['description'])
    ]);

    return ['status' => 'success'];
}

function update_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partners';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'about'        => sanitize_textarea_field($params['about']),
        'description'  => wp_kses_post($params['description'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partners';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// --- INDUSTRY PARTNERSHIPS API ---
add_action('rest_api_init', function () {
    register_rest_route('industry-partnerships/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_industry_partners',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('industry-partnerships/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_industry_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('industry-partnerships/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_industry_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('industry-partnerships/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_industry_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('industry-partnerships/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_partner_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_industry_partners() {
    global $wpdb;
    $table = $wpdb->prefix . 'industry_partnerships';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_industry_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'industry_partnerships';
    $params = json_decode($request->get_body(), true);
    $wpdb->insert($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'para'         => wp_kses_post($params['para'])
    ]);
    return ['status' => 'success'];
}

function update_industry_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'industry_partnerships';
    $params = json_decode($request->get_body(), true);
    $wpdb->update($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'para'         => wp_kses_post($params['para'])
    ], ['id' => $request['id']]);
    return ['status' => 'updated'];
}

function delete_industry_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'industry_partnerships';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// --- RESEARCH PARTNERSHIPS API ---
add_action('rest_api_init', function () {
    register_rest_route('research-partnerships/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_research_partners',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('research-partnerships/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_research_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('research-partnerships/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_research_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('research-partnerships/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_research_partner',
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('research-partnerships/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_partner_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_research_partners() {
    global $wpdb;
    $table = $wpdb->prefix . 'research_partnerships';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_research_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_partnerships';
    $params = json_decode($request->get_body(), true);
    $wpdb->insert($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'para'         => wp_kses_post($params['para'])
    ]);
    return ['status' => 'success'];
}

function update_research_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_partnerships';
    $params = json_decode($request->get_body(), true);
    $wpdb->update($table, [
        'company_name' => sanitize_text_field($params['company_name']),
        'company_logo' => esc_url_raw($params['company_logo']),
        'para'         => wp_kses_post($params['para'])
    ], ['id' => $request['id']]);
    return ['status' => 'updated'];
}

function delete_research_partner($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'research_partnerships';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function partners_manager_page() { ?>
    <div class="wrap">
        <h1>Partners Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Partner</h2>

            <table class="form-table">
                <tr>
                    <th>Company Name</th>
                    <td>
                        <input type="text" id="company_name" class="regular-text" required>
                        <span id="company_name_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Company Logo</th>
                    <td>
                        <input type="file" id="company_logo_file">
                        <input type="hidden" id="company_logo">
                        <br><br>
                        <img id="preview_logo" style="max-width:150px;display:none;">
                    </td>
                </tr>
                <tr>
                    <th>About</th>
                    <td>
                        <textarea id="about" class="large-text" rows="3" required></textarea>
                        <span id="about_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>
                        <?php wp_editor('', 'description_editor', array('media_buttons' => false)); ?>
                        <span id="description_error" class="error-msg"></span>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="savePartner()">Save Partner</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Partners</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>About</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="partnersList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const API_BASE = "<?php echo site_url('/wp-json/partners/v1'); ?>";
    let editingId = null;

    function loadPartners() {
        fetch(API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.company_logo ? '<img src="' + item.company_logo + '" width="60">' : ''}</td>
                    <td>${item.company_name}</td>
                    <td>${item.about}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editPartner(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deletePartner(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("partnersList").innerHTML = html;
        });
    }

    function clearErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function savePartner() {
        clearErrors();
        
        const companyNameVal = document.getElementById("company_name").value.trim();
        const logoVal = document.getElementById("company_logo").value;
        const aboutVal = document.getElementById("about").value.trim();
        const descriptionVal = tinymce.get("description_editor").getContent().trim();
        
        let isValid = true;
        
        if (!companyNameVal) {
            document.getElementById("company_name_error").innerText = "Company name is required.";
            document.getElementById("company_name").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            company_name: companyNameVal,
            company_logo: logoVal,
            about: aboutVal,
            description: descriptionVal
        };
        
        let url = API_BASE + "/add";
        if (editingId) url = API_BASE + "/update/" + editingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            editingId = null; 
            document.getElementById("company_name").value = '';
            document.getElementById("company_logo").value = '';
            document.getElementById("about").value = '';
            document.getElementById("preview_logo").style.display = "none";
            tinymce.get("description_editor").setContent('');
            loadPartners(); 
        });
    }

    function editPartner(item) {
        clearErrors();
        editingId = item.id;
        document.getElementById("company_name").value = item.company_name;
        document.getElementById("about").value = item.about;
        document.getElementById("company_logo").value = item.company_logo;
        
        if(item.company_logo) {
            document.getElementById("preview_logo").src = item.company_logo;
            document.getElementById("preview_logo").style.display = "block";
        } else {
            document.getElementById("preview_logo").style.display = "none";
        }
        
        tinymce.get("description_editor").setContent(item.description);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deletePartner(id) {
        if (!confirm("Are you sure you want to delete this partner?")) return;
        fetch(API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadPartners());
    }

    document.getElementById("company_logo_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        
        let formData = new FormData();
        formData.append("file", file);

        fetch(API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("company_logo").value = data.url;
                document.getElementById("preview_logo").src = data.url;
                document.getElementById("preview_logo").style.display = "block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadPartners);
    </script>
<?php }

function industry_partnerships_page() { ?>
    <div class="wrap">
        <h1>Industry Partnerships Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Industry Partner</h2>

            <table class="form-table">
                <tr>
                    <th>Company Name</th>
                    <td>
                        <input type="text" id="ind_company_name" class="regular-text" required>
                        <span id="ind_company_name_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Company Logo</th>
                    <td>
                        <input type="file" id="ind_company_logo_file">
                        <input type="hidden" id="ind_company_logo">
                        <br><br>
                        <img id="ind_preview_logo" style="max-width:150px;display:none;">
                    </td>
                </tr>
                <tr>
                    <th>Para (Description)</th>
                    <td>
                        <?php wp_editor('', 'ind_para_editor', array('media_buttons' => false)); ?>
                        <span id="ind_para_error" class="error-msg"></span>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveIndustryPartner()">Save Industry Partner</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Industry Partners</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="industryPartnersList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const IND_API_BASE = "<?php echo site_url('/wp-json/industry-partnerships/v1'); ?>";
    let indEditingId = null;

    function loadIndustryPartners() {
        fetch(IND_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.company_logo ? '<img src="' + item.company_logo + '" width="60">' : ''}</td>
                    <td>${item.company_name}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editIndustryPartner(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteIndustryPartner(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("industryPartnersList").innerHTML = html;
        });
    }

    function clearIndErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveIndustryPartner() {
        clearIndErrors();
        
        const companyNameVal = document.getElementById("ind_company_name").value.trim();
        const logoVal = document.getElementById("ind_company_logo").value;
        const paraVal = tinymce.get("ind_para_editor").getContent().trim();
        
        let isValid = true;
        if (!companyNameVal) {
            document.getElementById("ind_company_name_error").innerText = "Company name is required.";
            document.getElementById("ind_company_name").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            company_name: companyNameVal,
            company_logo: logoVal,
            para: paraVal
        };
        
        let url = IND_API_BASE + "/add";
        if (indEditingId) url = IND_API_BASE + "/update/" + indEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            indEditingId = null; 
            document.getElementById("ind_company_name").value = '';
            document.getElementById("ind_company_logo").value = '';
            document.getElementById("ind_preview_logo").style.display = "none";
            tinymce.get("ind_para_editor").setContent('');
            loadIndustryPartners(); 
        });
    }

    function editIndustryPartner(item) {
        clearIndErrors();
        indEditingId = item.id;
        document.getElementById("ind_company_name").value = item.company_name;
        document.getElementById("ind_company_logo").value = item.company_logo;
        
        if(item.company_logo) {
            document.getElementById("ind_preview_logo").src = item.company_logo;
            document.getElementById("ind_preview_logo").style.display = "block";
        } else {
            document.getElementById("ind_preview_logo").style.display = "none";
        }
        
        tinymce.get("ind_para_editor").setContent(item.para);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteIndustryPartner(id) {
        if (!confirm("Are you sure you want to delete this partner?")) return;
        fetch(IND_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadIndustryPartners());
    }

    document.getElementById("ind_company_logo_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(IND_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("ind_company_logo").value = data.url;
                document.getElementById("ind_preview_logo").src = data.url;
                document.getElementById("ind_preview_logo").style.display = "block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadIndustryPartners);
    </script>
<?php }

function research_partnerships_page() { ?>
    <div class="wrap">
        <h1>Research Partnerships Manager</h1>
        
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Research Partner</h2>

            <table class="form-table">
                <tr>
                    <th>Company Name</th>
                    <td>
                        <input type="text" id="res_company_name" class="regular-text" required>
                        <span id="res_company_name_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Company Logo</th>
                    <td>
                        <input type="file" id="res_company_logo_file">
                        <input type="hidden" id="res_company_logo">
                        <br><br>
                        <img id="res_preview_logo" style="max-width:150px;display:none;">
                    </td>
                </tr>
                <tr>
                    <th>Para (Description)</th>
                    <td>
                        <?php wp_editor('', 'res_para_editor', array('media_buttons' => false)); ?>
                        <span id="res_para_error" class="error-msg"></span>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveResearchPartner()">Save Research Partner</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Research Partners</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="researchPartnersList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const RES_API_BASE = "<?php echo site_url('/wp-json/research-partnerships/v1'); ?>";
    let resEditingId = null;

    function loadResearchPartners() {
        fetch(RES_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `
                <tr>
                    <td>${item.company_logo ? '<img src="' + item.company_logo + '" width="60">' : ''}</td>
                    <td>${item.company_name}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editResearchPartner(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteResearchPartner(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("researchPartnersList").innerHTML = html;
        });
    }

    function clearResErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveResearchPartner() {
        clearResErrors();
        
        const companyNameVal = document.getElementById("res_company_name").value.trim();
        const logoVal = document.getElementById("res_company_logo").value;
        const paraVal = tinymce.get("res_para_editor").getContent().trim();
        
        let isValid = true;
        if (!companyNameVal) {
            document.getElementById("res_company_name_error").innerText = "Company name is required.";
            document.getElementById("res_company_name").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            company_name: companyNameVal,
            company_logo: logoVal,
            para: paraVal
        };
        
        let url = RES_API_BASE + "/add";
        if (resEditingId) url = RES_API_BASE + "/update/" + resEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            resEditingId = null; 
            document.getElementById("res_company_name").value = '';
            document.getElementById("res_company_logo").value = '';
            document.getElementById("res_preview_logo").style.display = "none";
            tinymce.get("res_para_editor").setContent('');
            loadResearchPartners(); 
        });
    }

    function editResearchPartner(item) {
        clearResErrors();
        resEditingId = item.id;
        document.getElementById("res_company_name").value = item.company_name;
        document.getElementById("res_company_logo").value = item.company_logo;
        
        if(item.company_logo) {
            document.getElementById("res_preview_logo").src = item.company_logo;
            document.getElementById("res_preview_logo").style.display = "block";
        } else {
            document.getElementById("res_preview_logo").style.display = "none";
        }
        
        tinymce.get("res_para_editor").setContent(item.para);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteResearchPartner(id) {
        if (!confirm("Are you sure you want to delete this partner?")) return;
        fetch(RES_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadResearchPartners());
    }

    document.getElementById("res_company_logo_file").addEventListener("change", function() {
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(RES_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                document.getElementById("res_company_logo").value = data.url;
                document.getElementById("res_preview_logo").src = data.url;
                document.getElementById("res_preview_logo").style.display = "block";
            } else {
                alert("Upload Failed");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", loadResearchPartners);
    </script>
<?php }
