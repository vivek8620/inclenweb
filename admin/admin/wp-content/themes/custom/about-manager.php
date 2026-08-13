<?php
/**
 * About Page Manager
 */

add_action('admin_menu', function () {
    // 1. Who We Are
    add_submenu_page(
        'group-resources',
        'Who We Are',
        'Who We Are',
        'manage_options',
        'about-who-we-are',
        'about_who_we_are_page'
    );

    // 2. Our Journey
    add_submenu_page(
        'group-resources',
        'Our Journey',
        'Our Journey',
        'manage_options',
        'about-our-journey',
        'about_our_journey_page'
    );

    // 3. Mission
    add_submenu_page(
        'group-resources',
        'Mission',
        'Mission',
        'manage_options',
        'about-mission',
        'about_mission_page'
    );
});

// REST API setup for Who We Are & Our Journey
add_action('rest_api_init', function () {
    register_rest_route('about-who/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_about_who',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-who/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_about_who',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-who/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_about_who',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-who/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_about_who_image_to_r2',
        'permission_callback' => '__return_true'
    ]);

    // Our Journey Endpoints
    register_rest_route('about-journey/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_about_journey',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-journey/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_about_journey',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-journey/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_about_journey',
        'permission_callback' => '__return_true'
    ]);

    // Mission Endpoints
    register_rest_route('about-mission/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_about_mission',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-mission/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_about_mission',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('about-mission/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_about_mission',
        'permission_callback' => '__return_true'
    ]);
});

function upload_about_who_image_to_r2() {
    try {
        require_once __DIR__ . '/cloud_config.php';
        
        if (!isset($_FILES['file'])) {
            return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        if (!class_exists('Aws\S3\S3Client')) {
            return new WP_Error('sdk_missing', 'AWS SDK not loaded', ['status' => 500]);
        }

        $client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://" . R2_ACCOUNT_ID . ".r2.cloudflarestorage.com",
            'credentials' => [
                'key' => R2_ACCESS_KEY,
                'secret' => R2_SECRET_KEY,
            ],
        ]);

        $file = $_FILES['file'];
        $fileName = time() . '-' . sanitize_file_name($file['name']);

        $result = $client->putObject([
            'Bucket' => R2_BUCKET,
            'Key' => 'admin/about/who-we-are/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/about/who-we-are/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_about_who() {
    global $wpdb;
    $table = $wpdb->prefix . 'about_who_we_are';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
}

function add_about_who($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_who_we_are';
    $params = json_decode($request->get_body(), true);

    // Delete existing to keep only one record for the page section
    $wpdb->query("TRUNCATE TABLE $table");

    $wpdb->insert($table, [
        'tag_text'      => sanitize_text_field($params['tag_text']),
        'heading'       => sanitize_text_field($params['heading']),
        'description'   => wp_kses_post($params['description']),
        'image_url'     => esc_url_raw($params['image_url']),
        'quote_text'    => sanitize_textarea_field($params['quote_text']),
        'quote_subtext' => sanitize_text_field($params['quote_subtext'])
    ]);

    return ['status' => 'success'];
}

function update_about_who($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_who_we_are';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'tag_text'      => sanitize_text_field($params['tag_text']),
        'heading'       => sanitize_text_field($params['heading']),
        'description'   => wp_kses_post($params['description']),
        'image_url'     => esc_url_raw($params['image_url']),
        'quote_text'    => sanitize_textarea_field($params['quote_text']),
        'quote_subtext' => sanitize_text_field($params['quote_subtext'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function get_all_about_journey() {
    global $wpdb;
    $table = $wpdb->prefix . 'about_our_journey';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
}

function add_about_journey($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_our_journey';
    $params = json_decode($request->get_body(), true);

    // Delete existing to keep only one record for the page section
    $wpdb->query("TRUNCATE TABLE $table");

    $milestones = [];
    if (!empty($params['milestones']) && is_array($params['milestones'])) {
        foreach ($params['milestones'] as $m) {
            $milestones[] = [
                'year'        => sanitize_text_field($m['year']),
                'title'       => sanitize_text_field($m['title']),
                'description' => sanitize_textarea_field($m['description']),
                'footer_tag'  => sanitize_text_field($m['footer_tag'])
            ];
        }
    }

    $wpdb->insert($table, [
        'tag_text'   => sanitize_text_field($params['tag_text']),
        'heading'    => sanitize_text_field($params['heading']),
        'subheading' => sanitize_textarea_field($params['subheading']),
        'milestones' => wp_json_encode($milestones)
    ]);

    return ['status' => 'success'];
}

function update_about_journey($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_our_journey';
    $params = json_decode($request->get_body(), true);

    $milestones = [];
    if (!empty($params['milestones']) && is_array($params['milestones'])) {
        foreach ($params['milestones'] as $m) {
            $milestones[] = [
                'year'        => sanitize_text_field($m['year']),
                'title'       => sanitize_text_field($m['title']),
                'description' => sanitize_textarea_field($m['description']),
                'footer_tag'  => sanitize_text_field($m['footer_tag'])
            ];
        }
    }

    $wpdb->update($table, [
        'tag_text'   => sanitize_text_field($params['tag_text']),
        'heading'    => sanitize_text_field($params['heading']),
        'subheading' => sanitize_textarea_field($params['subheading']),
        'milestones' => wp_json_encode($milestones)
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function get_all_about_mission() {
    global $wpdb;
    $table = $wpdb->prefix . 'about_mission';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
}

function add_about_mission($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_mission';
    $params = json_decode($request->get_body(), true);

    $wpdb->query("TRUNCATE TABLE $table");

    $cards = [];
    if (!empty($params['cards']) && is_array($params['cards'])) {
        foreach ($params['cards'] as $c) {
            $cards[] = [
                'title'       => sanitize_text_field($c['title']),
                'description' => wp_kses_post($c['description']),
                'list_items'  => wp_kses_post($c['list_items'])
            ];
        }
    }

    $wpdb->insert($table, [
        'tag_text'    => sanitize_text_field($params['tag_text']),
        'heading'     => sanitize_text_field($params['heading']),
        'description' => wp_kses_post($params['description']),
        'cards'       => wp_json_encode($cards)
    ]);

    return ['status' => 'success'];
}

function update_about_mission($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'about_mission';
    $params = json_decode($request->get_body(), true);

    $cards = [];
    if (!empty($params['cards']) && is_array($params['cards'])) {
        foreach ($params['cards'] as $c) {
            $cards[] = [
                'title'       => sanitize_text_field($c['title']),
                'description' => wp_kses_post($c['description']),
                'list_items'  => wp_kses_post($c['list_items'])
            ];
        }
    }

    $wpdb->update($table, [
        'tag_text'    => sanitize_text_field($params['tag_text']),
        'heading'     => sanitize_text_field($params['heading']),
        'description' => wp_kses_post($params['description']),
        'cards'       => wp_json_encode($cards)
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}


function about_who_we_are_page() { ?>
    <div class="wrap">
        <h1>About Page: Who We Are</h1>
        <style>
            .form-row { display: flex; align-items: flex-start; margin-bottom: 25px; }
            .form-label { width: 250px; font-weight: 500; font-size: 14px; padding-top: 5px; }
            .form-input { width: 100%; max-width: 600px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
            .image-preview { max-width: 300px; max-height: 200px; display: block; margin-bottom: 10px; border-radius: 4px; }
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; }
        </style>

        <div style="background:#fff;padding:30px;margin-top:20px;border:1px solid #ccc;border-radius:6px;max-width:900px;">
            <input type="hidden" id="who_id">

            <div class="form-row">
                <div class="form-label">Tag Text (e.g. WHO WE ARE)</div>
                <div style="flex:1">
                    <input type="text" id="tag_text" class="form-input" placeholder="WHO WE ARE">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Main Heading</div>
                <div style="flex:1">
                    <input type="text" id="heading" class="form-input" placeholder="A Global Network Acting for Healthier Communities">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Description (Paragraphs)</div>
                <div style="flex:1">
                    <?php 
                    wp_editor('', 'description_editor', [
                        'textarea_name' => 'description',
                        'textarea_rows' => 10,
                        'media_buttons' => false
                    ]); 
                    ?>
                </div>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <div class="form-row">
                <div class="form-label">Main Group Image</div>
                <div style="flex:1">
                    <img id="image_preview" src="" class="image-preview" style="display:none;">
                    <input type="hidden" id="image_url">
                    <input type="file" id="image_file" accept="image/*" onchange="uploadImage(this)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Quote Overlay Text</div>
                <div style="flex:1">
                    <textarea id="quote_text" class="form-input" rows="3" placeholder="From local insights to global policy..."></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Quote Subtext (e.g. SINCE 1980)</div>
                <div style="flex:1">
                    <input type="text" id="quote_subtext" class="form-input" placeholder="SINCE 1980">
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="button" class="button button-primary button-large" onclick="saveWhoWeAre()">Save "Who We Are" Section</button>
            </div>
        </div>

        <div style="margin-top:30px;">
            <h2>Submitted "Who We Are" Data</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Heading</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="whoWeAreList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const WHO_API_BASE = "<?php echo site_url('/wp-json/about-who/v1'); ?>";
    const WHO_WP_NONCE = "<?php echo wp_create_nonce('wp_rest'); ?>";

    function uploadImage(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        
        fileInput.disabled = true;

        fetch(WHO_API_BASE + "/upload-image", {
            method: "POST",
            headers: { 'X-WP-Nonce': WHO_WP_NONCE },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            fileInput.disabled = false;
            if (data.url) {
                document.getElementById('image_url').value = data.url;
                const preview = document.getElementById('image_preview');
                preview.src = data.url;
                preview.style.display = 'block';
            } else {
                alert("Image upload failed.");
            }
        })
        .catch(e => {
            fileInput.disabled = false;
            alert("Upload error.");
        });
    }

    function loadWhoWeAre() {
        fetch(WHO_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const item = data[0];
                document.getElementById('who_id').value = item.id;
                document.getElementById('tag_text').value = item.tag_text || '';
                document.getElementById('heading').value = item.heading || '';
                
                // WP Editor requires special handling
                if (typeof tinymce !== 'undefined' && tinymce.get('description_editor')) {
                    tinymce.get('description_editor').setContent(item.description || '');
                } else {
                    document.getElementById('description_editor').value = item.description || '';
                }

                document.getElementById('image_url').value = item.image_url || '';
                if (item.image_url) {
                    const preview = document.getElementById('image_preview');
                    preview.src = item.image_url;
                    preview.style.display = 'block';
                }

                document.getElementById('quote_text').value = item.quote_text || '';
                document.getElementById('quote_subtext').value = item.quote_subtext || '';

                // Populate list table
                let listHtml = `
                <tr>
                    <td><strong>${item.tag_text || ''}</strong></td>
                    <td>${item.heading || ''}</td>
                    <td>${item.image_url ? '<img src="'+item.image_url+'" style="max-height:50px; border-radius:4px;">' : 'No Image'}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })">Edit Data Above</button>
                    </td>
                </tr>`;
                document.getElementById('whoWeAreList').innerHTML = listHtml;
            } else {
                document.getElementById('whoWeAreList').innerHTML = '<tr><td colspan="4">No data submitted yet.</td></tr>';
            }
        });
    }

    function saveWhoWeAre() {
        // Get description from WP editor
        let desc = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('description_editor')) {
            desc = tinymce.get('description_editor').getContent();
        } else {
            desc = document.getElementById('description_editor').value;
        }

        const data = {
            tag_text: document.getElementById('tag_text').value.trim(),
            heading: document.getElementById('heading').value.trim(),
            description: desc,
            image_url: document.getElementById('image_url').value,
            quote_text: document.getElementById('quote_text').value.trim(),
            quote_subtext: document.getElementById('quote_subtext').value.trim()
        };

        const id = document.getElementById('who_id').value;
        const url = id ? WHO_API_BASE + "/update/" + id : WHO_API_BASE + "/add";

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            loadWhoWeAre();
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadWhoWeAre();
    });
    </script>
<?php }

function about_our_journey_page() { ?>
    <div class="wrap">
        <h1>About Page: Our Journey</h1>
        <style>
            .form-row { display: flex; align-items: flex-start; margin-bottom: 25px; }
            .form-label { width: 250px; font-weight: 500; font-size: 14px; padding-top: 5px; }
            .form-input { width: 100%; max-width: 600px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
            .milestone-item { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; background: #fafafa; border-radius: 4px; position: relative; max-width: 600px; }
            .milestone-item input, .milestone-item textarea { width: 100%; margin-bottom: 10px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
            .remove-milestone { position: absolute; top: 10px; right: 10px; color: #b32d2e; cursor: pointer; border: none; background: none; font-weight: bold; font-size: 16px; }
        </style>

        <div style="background:#fff;padding:30px;margin-top:20px;border:1px solid #ccc;border-radius:6px;max-width:900px;">
            <input type="hidden" id="journey_id">

            <div class="form-row">
                <div class="form-label">Tag Text (e.g. OUR JOURNEY)</div>
                <div style="flex:1">
                    <input type="text" id="journey_tag_text" class="form-input" placeholder="OUR JOURNEY">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Main Heading</div>
                <div style="flex:1">
                    <input type="text" id="journey_heading" class="form-input" placeholder="Decades of Impact">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Subheading</div>
                <div style="flex:1">
                    <textarea id="journey_subheading" class="form-input" rows="3" placeholder="From a single idea in 1980 to a global force for health equity today."></textarea>
                </div>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <h3>Milestone Cards</h3>
            <div id="milestones_container"></div>
            <button type="button" class="button" onclick="addMilestoneCard()">+ Add Milestone Card</button>

            <div style="margin-top: 30px;">
                <button type="button" class="button button-primary button-large" onclick="saveOurJourney()">Save "Our Journey" Section</button>
            </div>
        </div>

        <div style="margin-top:30px;">
            <h2>Submitted "Our Journey" Data</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Heading</th>
                        <th>Milestones Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ourJourneyList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const JOURNEY_API_BASE = "<?php echo site_url('/wp-json/about-journey/v1'); ?>";

    function addMilestoneCard(data = {}) {
        const container = document.getElementById('milestones_container');
        const index = container.children.length;
        const div = document.createElement('div');
        div.className = 'milestone-item';
        div.dataset.index = index;

        let year = data.year || '';
        let title = data.title || '';
        let desc = data.description || '';
        let footer_tag = data.footer_tag || '';

        div.innerHTML = `
            <button type="button" class="remove-milestone" onclick="this.parentElement.remove()">X</button>
            <h4 style="margin-top:0;">Milestone Card</h4>
            
            <label><strong>Year</strong></label>
            <input type="text" class="milestone-year" placeholder="e.g. 1980" value="${year.replace(/"/g, '&quot;')}">
            
            <label><strong>Title</strong></label>
            <input type="text" class="milestone-title" placeholder="e.g. Foundation" value="${title.replace(/"/g, '&quot;')}">
            
            <label><strong>Description</strong></label>
            <textarea class="milestone-desc" rows="3" placeholder="Card Description">${desc.replace(/</g, '&lt;')}</textarea>
            
            <label><strong>Footer Tag</strong></label>
            <input type="text" class="milestone-footer" placeholder="e.g. THE BEGINNING" value="${footer_tag.replace(/"/g, '&quot;')}">
        `;
        container.appendChild(div);
    }

    function loadOurJourney() {
        fetch(JOURNEY_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const item = data[0];
                document.getElementById('journey_id').value = item.id;
                document.getElementById('journey_tag_text').value = item.tag_text || '';
                document.getElementById('journey_heading').value = item.heading || '';
                document.getElementById('journey_subheading').value = item.subheading || '';

                document.getElementById('milestones_container').innerHTML = '';
                let milestones = [];
                try {
                    milestones = JSON.parse(item.milestones) || [];
                } catch(e) {}

                milestones.forEach(m => addMilestoneCard(m));
                if (milestones.length === 0) {
                    addMilestoneCard();
                }

                // Populate list table
                let listHtml = `
                <tr>
                    <td><strong>${item.tag_text || ''}</strong></td>
                    <td>${item.heading || ''}</td>
                    <td>${milestones.length} cards</td>
                    <td>
                        <button class="button button-small edit-btn" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })">Edit Data Above</button>
                    </td>
                </tr>`;
                document.getElementById('ourJourneyList').innerHTML = listHtml;
            } else {
                document.getElementById('ourJourneyList').innerHTML = '<tr><td colspan="4">No data submitted yet.</td></tr>';
                document.getElementById('milestones_container').innerHTML = '';
                addMilestoneCard();
            }
        });
    }

    function saveOurJourney() {
        const milestones = [];
        document.querySelectorAll('.milestone-item').forEach(item => {
            milestones.push({
                year: item.querySelector('.milestone-year').value.trim(),
                title: item.querySelector('.milestone-title').value.trim(),
                description: item.querySelector('.milestone-desc').value.trim(),
                footer_tag: item.querySelector('.milestone-footer').value.trim()
            });
        });

        const data = {
            tag_text: document.getElementById('journey_tag_text').value.trim(),
            heading: document.getElementById('journey_heading').value.trim(),
            subheading: document.getElementById('journey_subheading').value.trim(),
            milestones: milestones
        };

        const id = document.getElementById('journey_id').value;
        const url = id ? JOURNEY_API_BASE + "/update/" + id : JOURNEY_API_BASE + "/add";

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            loadOurJourney();
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadOurJourney();
    });
    </script>
<?php }

function about_mission_page() { ?>
    <div class="wrap">
        <h1>About Page: Mission (Strategic Objectives)</h1>
        <style>
            .form-row { display: flex; align-items: flex-start; margin-bottom: 25px; }
            .form-label { width: 250px; font-weight: 500; font-size: 14px; padding-top: 5px; }
            .form-input { width: 100%; max-width: 600px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
            .card-item { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; background: #fafafa; border-radius: 4px; position: relative; max-width: 700px; }
            .card-item input, .card-item textarea { width: 100%; margin-bottom: 10px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
            .remove-card { position: absolute; top: 10px; right: 10px; color: #b32d2e; cursor: pointer; border: none; background: none; font-weight: bold; font-size: 16px; }
            .icon-preview { max-width: 50px; max-height: 50px; display: block; margin-bottom: 10px; border-radius: 4px; }
        </style>

        <div style="background:#fff;padding:30px;margin-top:20px;border:1px solid #ccc;border-radius:6px;max-width:900px;">
            <input type="hidden" id="mission_id">

            <div class="form-row">
                <div class="form-label">Tag Text (e.g. MISSION)</div>
                <div style="flex:1">
                    <input type="text" id="mission_tag_text" class="form-input" placeholder="MISSION">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Main Heading</div>
                <div style="flex:1">
                    <input type="text" id="mission_heading" class="form-input" placeholder="Strategic Objectives">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Description</div>
                <div style="flex:1">
                    <textarea id="mission_description" class="form-input" rows="4" placeholder="INCLEN works to generate scientific evidence..."></textarea>
                </div>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <h3>Objective Details</h3>
            
            <div class="card-item">
                <h4 style="margin-top:0;">1. Research Excellence</h4>
                <label style="display:block; margin-top:10px;"><strong>Title</strong></label>
                <input type="text" id="research_title" class="form-input" value="Research Excellence">
                <label style="display:block; margin-top:10px;"><strong>Description</strong></label>
                <textarea id="research_desc" class="form-input" rows="3"></textarea>
                <label style="display:block; margin-top:10px;"><strong>List Items (HTML or Text)</strong></label>
                <div style="flex:1; margin-top:5px;">
                    <?php wp_editor('', 'research_list', ['textarea_name' => 'research_list', 'textarea_rows' => 4, 'media_buttons' => false]); ?>
                </div>
            </div>

            <div class="card-item">
                <h4 style="margin-top:0;">2. Capacity Building</h4>
                <label style="display:block; margin-top:10px;"><strong>Title</strong></label>
                <input type="text" id="capacity_title" class="form-input" value="Capacity Building">
                <label style="display:block; margin-top:10px;"><strong>Description</strong></label>
                <textarea id="capacity_desc" class="form-input" rows="3"></textarea>
                <label style="display:block; margin-top:10px;"><strong>List Items (HTML or Text)</strong></label>
                <div style="flex:1; margin-top:5px;">
                    <?php wp_editor('', 'capacity_list', ['textarea_name' => 'capacity_list', 'textarea_rows' => 4, 'media_buttons' => false]); ?>
                </div>
            </div>

            <div class="card-item">
                <h4 style="margin-top:0;">3. Policy Advocacy</h4>
                <label style="display:block; margin-top:10px;"><strong>Title</strong></label>
                <input type="text" id="policy_title" class="form-input" value="Policy Advocacy">
                <label style="display:block; margin-top:10px;"><strong>Description</strong></label>
                <textarea id="policy_desc" class="form-input" rows="3"></textarea>
                <label style="display:block; margin-top:10px;"><strong>List Items (HTML or Text)</strong></label>
                <div style="flex:1; margin-top:5px;">
                    <?php wp_editor('', 'policy_list', ['textarea_name' => 'policy_list', 'textarea_rows' => 4, 'media_buttons' => false]); ?>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="button" class="button button-primary button-large" onclick="saveMission()">Save Mission Section</button>
            </div>
        </div>
    </div>

    <script>
    const MISSION_API_BASE = "<?php echo site_url('/wp-json/about-mission/v1'); ?>";
    const MISSION_WP_NONCE = "<?php echo wp_create_nonce('wp_rest'); ?>";

    function loadMission() {
        fetch(MISSION_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const item = data[0];
                document.getElementById('mission_id').value = item.id;
                document.getElementById('mission_tag_text').value = item.tag_text || '';
                document.getElementById('mission_heading').value = item.heading || '';
                document.getElementById('mission_description').value = item.description || '';

                let cards = [];
                try {
                    cards = JSON.parse(item.cards) || [];
                } catch(e) {}

                if (cards.length < 3) {
                    cards = [
                        {title: "Research Excellence", description: "", list_items: ""},
                        {title: "Capacity Building", description: "", list_items: ""},
                        {title: "Policy Advocacy", description: "", list_items: ""}
                    ];
                }

                document.getElementById('research_title').value = cards[0].title || 'Research Excellence';
                document.getElementById('research_desc').value = cards[0].description || '';
                if (typeof tinymce !== 'undefined' && tinymce.get('research_list')) {
                    tinymce.get('research_list').setContent(cards[0].list_items || '');
                } else {
                    document.getElementById('research_list').value = cards[0].list_items || '';
                }

                document.getElementById('capacity_title').value = cards[1].title || 'Capacity Building';
                document.getElementById('capacity_desc').value = cards[1].description || '';
                if (typeof tinymce !== 'undefined' && tinymce.get('capacity_list')) {
                    tinymce.get('capacity_list').setContent(cards[1].list_items || '');
                } else {
                    document.getElementById('capacity_list').value = cards[1].list_items || '';
                }

                document.getElementById('policy_title').value = cards[2].title || 'Policy Advocacy';
                document.getElementById('policy_desc').value = cards[2].description || '';
                if (typeof tinymce !== 'undefined' && tinymce.get('policy_list')) {
                    tinymce.get('policy_list').setContent(cards[2].list_items || '');
                } else {
                    document.getElementById('policy_list').value = cards[2].list_items || '';
                }
            }
        });
    }

    function saveMission() {
        let research_list = '', capacity_list = '', policy_list = '';

        if (typeof tinymce !== 'undefined' && tinymce.get('research_list')) {
            research_list = tinymce.get('research_list').getContent();
        } else {
            research_list = document.getElementById('research_list').value;
        }

        if (typeof tinymce !== 'undefined' && tinymce.get('capacity_list')) {
            capacity_list = tinymce.get('capacity_list').getContent();
        } else {
            capacity_list = document.getElementById('capacity_list').value;
        }

        if (typeof tinymce !== 'undefined' && tinymce.get('policy_list')) {
            policy_list = tinymce.get('policy_list').getContent();
        } else {
            policy_list = document.getElementById('policy_list').value;
        }

        const cards = [
            {
                title: document.getElementById('research_title').value.trim(),
                description: document.getElementById('research_desc').value.trim(),
                list_items: research_list.trim()
            },
            {
                title: document.getElementById('capacity_title').value.trim(),
                description: document.getElementById('capacity_desc').value.trim(),
                list_items: capacity_list.trim()
            },
            {
                title: document.getElementById('policy_title').value.trim(),
                description: document.getElementById('policy_desc').value.trim(),
                list_items: policy_list.trim()
            }
        ];

        const data = {
            tag_text: document.getElementById('mission_tag_text').value.trim(),
            heading: document.getElementById('mission_heading').value.trim(),
            description: document.getElementById('mission_description').value.trim(),
            cards: cards
        };

        const id = document.getElementById('mission_id').value;
        const url = id ? MISSION_API_BASE + "/update/" + id : MISSION_API_BASE + "/add";

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            loadMission();
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadMission();
    });
    </script>
<?php }

