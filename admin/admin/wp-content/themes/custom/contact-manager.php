<?php
/**
 * Contact Page & Footer Manager
 */

// Register Admin Menu
add_action('admin_menu', function () {
    add_menu_page(
        'Contact & Site Info',
        'Contact Info',
        'manage_options',
        'contact-manager',
        'contact_manager_page',
        'dashicons-location-alt',
        31
    );
});

// Register REST API routes
add_action('rest_api_init', function () {
    register_rest_route('contact-info/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_contact_info_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('contact-info/v1', '/update', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_contact_info_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('contact-info/v1', '/save', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_contact_info_rest',
        'permission_callback' => '__return_true'
    ]);
});

function get_contact_info_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_info';
    
    // Ensure table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        if (function_exists('custom_setup_database_tables')) {
            custom_setup_database_tables();
        }
    }

    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    
    if (!$row) {
        // Fallback default structure
        return [
            'id'                  => 1,
            'office_title'        => 'Executive Office',
            'address_line1'       => 'A-157–158, 3rd Floor, DDA Shed, Okhla Phase-II',
            'address_line2'       => 'New Delhi – 110020',
            'phone'               => '+91-11-47730000',
            'phone_footer'        => '+91 11 47730000 - 99',
            'email'               => 'ieodelhi@inclentrust.org',
            'map_query'           => 'A-157 DDA Shed, Okhla Phase-II, New Delhi 110020',
            'map_embed_url'       => 'https://maps.google.com/maps?q=A-157%20DDA%20Shed%2C%20Okhla%20Phase-II%2C%20New%20Delhi%20110020&t=&z=16&ie=UTF8&iwloc=&output=embed',
            'hero_title'          => "Let's start a Conversation",
            'hero_subtitle'       => "Connect with the INCLEN Executive Office. Whether it's data access, institutional partnership, or global research inquiries.",
            'research_title'      => 'A Global Research Infrastructure',
            'research_description'=> 'With 89 Clinical Epidemiology Units across 34 countries, our network provides a unique platform for high-impact multicentric studies.',
            'stat1_value'         => '34',
            'stat1_label'         => 'Countries Connected',
            'stat2_value'         => '89',
            'stat2_label'         => 'Partner Institutes',
            'stat3_value'         => '400k+',
            'stat3_label'         => 'Population Monitored'
        ];
    }

    return $row;
}

function save_contact_info_db($params) {
    global $wpdb;
    $table = $wpdb->prefix . 'contact_info';

    // Ensure table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        if (function_exists('custom_setup_database_tables')) {
            custom_setup_database_tables();
        }
    }

    $data = [
        'office_title'         => sanitize_text_field($params['office_title'] ?? 'Executive Office'),
        'address_line1'        => sanitize_textarea_field($params['address_line1'] ?? ''),
        'address_line2'        => sanitize_textarea_field($params['address_line2'] ?? ''),
        'phone'                => sanitize_text_field($params['phone'] ?? ''),
        'phone_footer'         => sanitize_text_field($params['phone_footer'] ?? ''),
        'email'                => sanitize_email($params['email'] ?? ''),
        'map_query'            => sanitize_text_field($params['map_query'] ?? ''),
        'map_embed_url'        => esc_url_raw($params['map_embed_url'] ?? ''),
        'research_title'       => sanitize_text_field($params['research_title'] ?? 'A Global Research Infrastructure'),
        'research_description' => sanitize_textarea_field($params['research_description'] ?? ''),
        'stat1_value'          => sanitize_text_field($params['stat1_value'] ?? ''),
        'stat1_label'          => sanitize_text_field($params['stat1_label'] ?? ''),
        'stat2_value'          => sanitize_text_field($params['stat2_value'] ?? ''),
        'stat2_label'          => sanitize_text_field($params['stat2_label'] ?? ''),
        'stat3_value'          => sanitize_text_field($params['stat3_value'] ?? ''),
        'stat3_label'          => sanitize_text_field($params['stat3_label'] ?? ''),
    ];

    if (!empty($params['hero_title'])) {
        $data['hero_title'] = sanitize_text_field($params['hero_title']);
    }
    if (!empty($params['hero_subtitle'])) {
        $data['hero_subtitle'] = sanitize_textarea_field($params['hero_subtitle']);
    }

    if (empty($data['map_embed_url']) && !empty($data['map_query'])) {
        $data['map_embed_url'] = 'https://maps.google.com/maps?q=' . urlencode($data['map_query']) . '&t=&z=16&ie=UTF8&iwloc=&output=embed';
    }

    $existing_id = $wpdb->get_var("SELECT id FROM $table ORDER BY id ASC LIMIT 1");

    if ($existing_id) {
        $wpdb->update($table, $data, ['id' => $existing_id]);
    } else {
        $wpdb->insert($table, $data);
    }

    return true;
}

function get_contact_info_rest() {
    $data = get_contact_info_data();
    return rest_ensure_response($data);
}

function update_contact_info_rest($request) {
    $params = json_decode($request->get_body(), true);

    if (empty($params)) {
        $params = $request->get_params();
    }

    save_contact_info_db($params);

    return rest_ensure_response([
        'status'  => 'success',
        'message' => 'Contact and Site settings updated successfully!',
        'data'    => get_contact_info_data()
    ]);
}

function contact_manager_page() {
    $updated = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact_settings'])) {
        if (check_admin_referer('contact_manager_save_action', 'contact_manager_nonce')) {
            save_contact_info_db($_POST);
            $updated = true;
        }
    }

    $data = get_contact_info_data();
    ?>
    <div class="wrap" style="max-width: 1080px; margin-top: 20px;">
        <h1 style="font-size: 26px; font-weight: 700; color: #1e293b; margin-bottom: 20px;">
            Contact &amp; Site Information Manager
        </h1>

        <?php if ($updated): ?>
            <div class="notice notice-success is-dismissible" style="padding: 12px 16px; margin-bottom: 20px; border-left-color: #10b981;">
                <p style="font-size: 14px; margin: 0;"><strong>Success!</strong> Contact information and stats saved successfully. They are now live on the website!</p>
            </div>
        <?php endif; ?>

        <div id="notice-alert" style="display: none; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;"></div>

        <form method="POST" action="" id="contact-manager-form" onsubmit="saveContactSettings(event)" style="display: flex; flex-direction: column; gap: 24px;">
            <?php wp_nonce_field('contact_manager_save_action', 'contact_manager_nonce'); ?>
            <input type="hidden" name="save_contact_settings" value="1">

            <!-- SECTION 1: CONTACT & OFFICE INFO -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">Office &amp; Contact Details</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">These details will update on the Contact page, Contact cards, and across the website Footer.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Office Title / Header</label>
                        <input type="text" id="office_title" name="office_title" value="<?php echo esc_attr($data['office_title'] ?? 'Executive Office'); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. Executive Office">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Address Line 1 (Street / Building)</label>
                        <textarea id="address_line1" name="address_line1" rows="3" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. A-157–158, 3rd Floor, DDA Shed, Okhla Phase-II"><?php echo esc_textarea($data['address_line1'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Address Line 2 (City, State &amp; Pincode)</label>
                        <textarea id="address_line2" name="address_line2" rows="3" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. New Delhi – 110020"><?php echo esc_textarea($data['address_line2'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Primary Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($data['email'] ?? ''); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. ieodelhi@inclentrust.org">
                        <small style="color: #64748b; font-size: 11px;">Used for contact cards and email click links.</small>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Contact Page Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo esc_attr($data['phone'] ?? ''); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. +91-11-47730000">
                    </div>

                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Footer Phone (Display format)</label>
                        <input type="text" id="phone_footer" name="phone_footer" value="<?php echo esc_attr($data['phone_footer'] ?? ''); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="e.g. +91 11 47730000 - 99">
                        <small style="color: #64748b; font-size: 11px;">Leave blank if you want it to match the Contact Page phone.</small>
                    </div>

                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Google Maps Embed URL / Location</label>
                        <input type="text" id="map_embed_url" name="map_embed_url" value="<?php echo esc_attr($data['map_embed_url'] ?? ''); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="https://maps.google.com/maps?q=...">
                        <small style="color: #64748b; font-size: 11px;">Embed iframe link or Google Maps query link shown on the Contact page map.</small>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: A GLOBAL RESEARCH INFRASTRUCTURE (STATS & HEADING) -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">A Global Research Infrastructure</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Manage the title, description, and statistics shown at the bottom of the Contact page.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Section Title</label>
                        <input type="text" id="research_title" name="research_title" value="<?php echo esc_attr($data['research_title'] ?? 'A Global Research Infrastructure'); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="A Global Research Infrastructure">
                    </div>

                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Section Description</label>
                        <textarea id="research_description" name="research_description" rows="3" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="With 89 Clinical Epidemiology Units across 34 countries, our network provides a unique platform for high-impact multicentric studies."><?php echo esc_textarea($data['research_description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Stat 1 -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <span style="display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Stat 1</span>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Number / Value</label>
                            <input type="text" id="stat1_value" name="stat1_value" value="<?php echo esc_attr($data['stat1_value'] ?? '34'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. 34">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Label Text</label>
                            <input type="text" id="stat1_label" name="stat1_label" value="<?php echo esc_attr($data['stat1_label'] ?? 'Countries Connected'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. Countries Connected">
                        </div>
                    </div>

                    <!-- Stat 2 -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <span style="display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Stat 2</span>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Number / Value</label>
                            <input type="text" id="stat2_value" name="stat2_value" value="<?php echo esc_attr($data['stat2_value'] ?? '89'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. 89">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Label Text</label>
                            <input type="text" id="stat2_label" name="stat2_label" value="<?php echo esc_attr($data['stat2_label'] ?? 'Partner Institutes'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. Partner Institutes">
                        </div>
                    </div>

                    <!-- Stat 3 -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; grid-column: span 2;">
                        <span style="display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Stat 3</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Number / Value</label>
                                <input type="text" id="stat3_value" name="stat3_value" value="<?php echo esc_attr($data['stat3_value'] ?? '400k+'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. 400k+">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Label Text</label>
                                <input type="text" id="stat3_label" name="stat3_label" value="<?php echo esc_attr($data['stat3_label'] ?? 'Population Monitored'); ?>" class="widefat" style="border-radius: 4px; padding: 6px 10px;" placeholder="e.g. Population Monitored">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; align-items: center; margin-top: 10px;">
                <span id="save-status" style="font-size: 13px; color: #64748b;"></span>
                <button type="submit" id="save-btn" style="background: #F7610C; color: #ffffff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-block; box-shadow: 0 4px 12px rgba(247, 97, 12, 0.25); transition: background 0.2s;">
                    Save Contact &amp; Stats Settings
                </button>
            </div>

        </form>
    </div>

    <script>
    const API_UPDATE_URL = "<?php echo site_url('/index.php?rest_route=/contact-info/v1/update'); ?>";

    async function saveContactSettings(e) {
        if (e && e.preventDefault) {
            e.preventDefault();
        }
        const btn = document.getElementById('save-btn');
        const statusSpan = document.getElementById('save-status');
        const noticeAlert = document.getElementById('notice-alert');
        const form = document.getElementById('contact-manager-form');

        btn.disabled = true;
        btn.style.opacity = '0.7';
        statusSpan.innerText = 'Saving changes...';
        noticeAlert.style.display = 'none';

        const payload = {
            office_title: document.getElementById('office_title') ? document.getElementById('office_title').value : '',
            address_line1: document.getElementById('address_line1') ? document.getElementById('address_line1').value : '',
            address_line2: document.getElementById('address_line2') ? document.getElementById('address_line2').value : '',
            phone: document.getElementById('phone') ? document.getElementById('phone').value : '',
            phone_footer: document.getElementById('phone_footer') ? document.getElementById('phone_footer').value : '',
            email: document.getElementById('email') ? document.getElementById('email').value : '',
            map_embed_url: document.getElementById('map_embed_url') ? document.getElementById('map_embed_url').value : '',
            research_title: document.getElementById('research_title') ? document.getElementById('research_title').value : '',
            research_description: document.getElementById('research_description') ? document.getElementById('research_description').value : '',
            stat1_value: document.getElementById('stat1_value') ? document.getElementById('stat1_value').value : '',
            stat1_label: document.getElementById('stat1_label') ? document.getElementById('stat1_label').value : '',
            stat2_value: document.getElementById('stat2_value') ? document.getElementById('stat2_value').value : '',
            stat2_label: document.getElementById('stat2_label') ? document.getElementById('stat2_label').value : '',
            stat3_value: document.getElementById('stat3_value') ? document.getElementById('stat3_value').value : '',
            stat3_label: document.getElementById('stat3_label') ? document.getElementById('stat3_label').value : ''
        };

        try {
            const res = await fetch(API_UPDATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const json = await res.json();
                noticeAlert.style.display = 'block';
                noticeAlert.style.background = '#ecfdf5';
                noticeAlert.style.color = '#065f46';
                noticeAlert.style.border = '1px solid #a7f3d0';
                noticeAlert.innerHTML = '<strong>Success!</strong> Contact information and stats saved successfully. They are now live on the website!';
                statusSpan.innerText = 'Saved!';
                setTimeout(() => { statusSpan.innerText = ''; }, 3000);
            } else {
                // If API returned error status, submit form natively
                form.submit();
            }
        } catch (err) {
            console.warn('AJAX fetch error, falling back to native POST submit:', err);
            form.submit();
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
    </script>
    <?php
}
