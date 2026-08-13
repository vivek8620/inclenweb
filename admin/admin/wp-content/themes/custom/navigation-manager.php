<?php
/**
 * Website Navigation Menu Visibility Manager
 */

// Register Navigation settings page
add_action('admin_menu', function () {
    add_menu_page(
        'Navigation Settings',
        'Nav Settings',
        'manage_options',
        'navigation-settings',
        'navigation_settings_page',
        'dashicons-menu',
        31
    );
});

// REST API initialization
add_action('rest_api_init', function () {
    register_rest_route('navigation/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_navigation_settings',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('navigation/v1', '/save', [
        'methods'  => 'POST',
        'callback' => 'save_navigation_settings',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('navigation/v1', '/create', [
        'methods'  => 'POST',
        'callback' => 'create_navigation_item',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('navigation/v1', '/delete', [
        'methods'  => 'POST',
        'callback' => 'delete_navigation_item',
        'permission_callback' => '__return_true'
    ]);
});

// Returns default menu structure
function get_default_menu_structure() {
    return [
        [
            'key' => 'about',
            'label' => 'About',
            'visible' => true,
            'children' => [
                ['key' => 'about_who_we_are', 'label' => 'Who We Are', 'visible' => true],
                ['key' => 'about_mission_vision', 'label' => 'Mission & Vision', 'visible' => true],
                ['key' => 'about_presence', 'label' => 'Global Presence', 'visible' => true],
                ['key' => 'about_fcra', 'label' => 'FCRA & Registration', 'visible' => true],
                ['key' => 'about_board', 'label' => 'Board of Trustees', 'visible' => true],
                ['key' => 'about_journey', 'label' => 'Our Journey', 'visible' => true],
                ['key' => 'about_collaborators', 'label' => 'Academic Collaborators', 'visible' => true]
            ]
        ],
        [
            'key' => 'our_work',
            'label' => 'Our Work',
            'visible' => true,
            'children' => [
                ['key' => 'work_area', 'label' => 'Area of Work', 'visible' => true],
                ['key' => 'work_research', 'label' => 'Research Projects', 'visible' => true],
                ['key' => 'work_somaarth', 'label' => 'Somaarth Sites', 'visible' => true],
                ['key' => 'work_capacity', 'label' => 'Capacity Building', 'visible' => true],
                ['key' => 'work_engagement', 'label' => 'Engagement & Advocacy', 'visible' => true],
                ['key' => 'work_community', 'label' => 'Community Activities', 'visible' => true]
            ]
        ],
        [
            'key' => 'our_impact',
            'label' => 'Our Impact',
            'visible' => true,
            'children' => [
                ['key' => 'impact_summary', 'label' => 'Impact Summary', 'visible' => true],
                ['key' => 'impact_partners', 'label' => 'Partners', 'visible' => true],
                ['key' => 'impact_findings', 'label' => 'Key Research Findings', 'visible' => true],
                ['key' => 'impact_device_products', 'label' => 'Device Products', 'visible' => true],
                ['key' => 'impact_policy_influence', 'label' => 'Policy Influence', 'visible' => true]
            ]
        ],
        [
            'key' => 'careers',
            'label' => 'Careers',
            'visible' => true,
            'children' => [
                ['key' => 'careers_openings', 'label' => 'Current Openings', 'visible' => true],
                ['key' => 'careers_fellowships', 'label' => 'Fellowships', 'visible' => true],
                ['key' => 'careers_internships', 'label' => 'Internships', 'visible' => true]
            ]
        ],
        [
            'key' => 'get_involved',
            'label' => 'Get Involved',
            'visible' => true,
            'children' => [
                ['key' => 'involved_academic', 'label' => 'Academic Association', 'visible' => true],
                ['key' => 'involved_research', 'label' => 'Research Partnership', 'visible' => true],
                ['key' => 'involved_industry', 'label' => 'Industry Partnership', 'visible' => true]
            ]
        ],
        [
            'key' => 'insights',
            'label' => 'Insights',
            'visible' => true,
            'children' => [
                ['key' => 'insights_news', 'label' => 'News', 'visible' => true],
                ['key' => 'insights_events', 'label' => 'Events', 'visible' => true],
                ['key' => 'insights_announcements', 'label' => 'Announcements', 'visible' => true],
                ['key' => 'insights_headlines', 'label' => 'Headlines', 'visible' => true]
            ]
        ],
        [
            'key' => 'resources',
            'label' => 'Resources',
            'visible' => true,
            'children' => [
                ['key' => 'resources_all', 'label' => 'All Publications', 'visible' => true],
                ['key' => 'resources_reports', 'label' => 'Annual Reports', 'visible' => true],
                ['key' => 'resources_newsletters', 'label' => 'Newsletters', 'visible' => true],
                ['key' => 'resources_repository', 'label' => 'Data Repository', 'visible' => true],
                ['key' => 'resources_tools', 'label' => 'Research Tools', 'visible' => true],
                ['key' => 'resources_training', 'label' => 'Training Materials', 'visible' => true]
            ]
        ],
        [
            'key' => 'contact',
            'label' => 'Contact',
            'visible' => true
        ]
    ];
}

// Fetch menu visibility configurations
function get_all_navigation_settings() {
    global $wpdb;
    $table = $wpdb->prefix . 'site_navigation';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return [
            'id' => null,
            'menu_structure' => get_default_menu_structure()
        ];
    }

    $result = $wpdb->get_row("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
    if (!$result) {
        return [
            'id' => null,
            'menu_structure' => get_default_menu_structure()
        ];
    }
    return [
        'id' => intval($result->id),
        'menu_structure' => json_decode($result->menu_structure, true)
    ];
}

// Recursive sanitization of menu tree structure
function sanitize_menu_structure_recursive($structure) {
    if (!is_array($structure)) {
        return [];
    }
    $sanitized = [];
    foreach ($structure as $item) {
        $sanitized_item = [
            'key' => sanitize_key($item['key']),
            'label' => sanitize_text_field($item['label']),
            'href' => isset($item['href']) ? sanitize_text_field($item['href']) : '#',
            'visible' => filter_var($item['visible'], FILTER_VALIDATE_BOOLEAN)
        ];
        if (isset($item['children']) && is_array($item['children'])) {
            $sanitized_item['children'] = sanitize_menu_structure_recursive($item['children']);
        }
        $sanitized[] = $sanitized_item;
    }
    return $sanitized;
}

// Save menu configuration
function save_navigation_settings($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'site_navigation';
    $params = json_decode($request->get_body(), true);

    if (empty($params['menu_structure'])) {
        return new WP_Error('empty_data', 'Menu structure is empty', ['status' => 400]);
    }

    $sanitized = sanitize_menu_structure_recursive($params['menu_structure']);
    $id = isset($params['id']) ? intval($params['id']) : null;

    if ($id) {
        $wpdb->update($table, [
            'menu_structure' => wp_json_encode($sanitized)
        ], ['id' => $id]);
    } else {
        $wpdb->query("TRUNCATE TABLE $table");
        $wpdb->insert($table, [
            'menu_structure' => wp_json_encode($sanitized)
        ]);
    }

    return ['status' => 'success'];
}

// Create a new navigation item in the database JSON
function create_navigation_item($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'site_navigation';
    $params = json_decode($request->get_body(), true);

    $label = isset($params['label']) ? sanitize_text_field($params['label']) : '';
    $href = isset($params['href']) ? sanitize_text_field($params['href']) : '#';
    $parent_key = isset($params['parent_key']) ? sanitize_key($params['parent_key']) : '';

    if (empty($label)) {
        return new WP_Error('empty_label', 'Label is required', ['status' => 400]);
    }

    $settings = get_all_navigation_settings();
    $structure = $settings['menu_structure'];
    $id = $settings['id'];

    // Generate unique key
    $clean_label = strtolower(preg_replace('/[^a-z0-9]/', '_', $label));
    $key = 'custom_' . $clean_label . '_' . rand(100, 999);

    $new_item = [
        'key' => $key,
        'label' => $label,
        'href' => $href,
        'visible' => true
    ];

    if (!empty($parent_key)) {
        // Add as child
        $added = false;
        foreach ($structure as &$item) {
            if ($item['key'] === $parent_key) {
                if (!isset($item['children']) || !is_array($item['children'])) {
                    $item['children'] = [];
                }
                $item['children'][] = $new_item;
                $added = true;
                break;
            }
        }
        if (!$added) {
            return new WP_Error('parent_not_found', 'Parent item not found', ['status' => 404]);
        }
    } else {
        // Add as top-level parent
        $new_item['children'] = [];
        $structure[] = $new_item;
    }

    // Save back to DB
    if ($id) {
        $wpdb->update($table, [
            'menu_structure' => wp_json_encode($structure)
        ], ['id' => $id]);
    } else {
        $wpdb->query("TRUNCATE TABLE $table");
        $wpdb->insert($table, [
            'menu_structure' => wp_json_encode($structure)
        ]);
    }

    return ['status' => 'success', 'key' => $key, 'menu_structure' => $structure];
}

// Delete a navigation item from the database JSON
function delete_navigation_item($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'site_navigation';
    $params = json_decode($request->get_body(), true);

    $key = isset($params['key']) ? sanitize_key($params['key']) : '';

    if (empty($key)) {
        return new WP_Error('empty_key', 'Key is required', ['status' => 400]);
    }

    $settings = get_all_navigation_settings();
    $structure = $settings['menu_structure'];
    $id = $settings['id'];

    // Recursive deletion helper
    if (!function_exists('delete_item_recursive_helper')) {
        function delete_item_recursive_helper(&$items, $target_key) {
            $deleted = false;
            foreach ($items as $index => &$item) {
                if ($item['key'] === $target_key) {
                    unset($items[$index]);
                    $deleted = true;
                    break;
                }
                if (isset($item['children']) && is_array($item['children'])) {
                    if (delete_item_recursive_helper($item['children'], $target_key)) {
                        $deleted = true;
                        break;
                    }
                }
            }
            if ($deleted) {
                $items = array_values($items); // re-index array
            }
            return $deleted;
        }
    }

    $success = delete_item_recursive_helper($structure, $key);
    if (!$success) {
        return new WP_Error('item_not_found', 'Item not found in menu structure', ['status' => 404]);
    }

    // Save back to DB
    if ($id) {
        $wpdb->update($table, [
            'menu_structure' => wp_json_encode($structure)
        ], ['id' => $id]);
    } else {
        $wpdb->query("TRUNCATE TABLE $table");
        $wpdb->insert($table, [
            'menu_structure' => wp_json_encode($structure)
        ]);
    }

    return ['status' => 'success', 'menu_structure' => $structure];
}

// Admin page markup
function navigation_settings_page() { ?>
    <div class="wrap">
        <h1>Website Navigation Visibility Settings</h1>
        <p class="description">Control which menu items are displayed on the public website header navigation bar dynamically.</p>

        <style>
            /* Layout Grid */
            .nav-settings-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 30px;
                margin-top: 20px;
                max-width: 1200px;
            }

            /* Glassmorphism Card Style */
            .settings-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.04);
                padding: 25px;
            }
            .settings-card h2 {
                margin-top: 0;
                padding-bottom: 12px;
                border-bottom: 1px solid #eee;
                font-size: 18px;
                font-weight: 600;
                color: #23282d;
            }

            /* Tree UI Controls */
            .tree-node {
                margin-bottom: 8px;
            }
            .node-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 15px;
                border-radius: 6px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                transition: background 0.2s, border-color 0.2s;
            }
            .node-row:hover {
                background: #f0f6fa;
                border-color: #007cba;
            }
            .node-label-container {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .node-label {
                font-weight: 500;
                font-size: 14px;
                color: #1d2327;
            }
            .parent-node {
                margin-top: 15px;
            }
            .parent-node > .node-row {
                background: #f0f0f0;
                border-left: 4px solid #007cba;
            }
            .parent-node > .node-row .node-label {
                font-weight: 600;
                font-size: 15px;
            }
            .child-nodes {
                margin-left: 30px;
                border-left: 2px dashed #ddd;
                padding-left: 15px;
                margin-top: 8px;
                margin-bottom: 15px;
            }
            .child-nodes .node-row {
                position: relative;
            }
            .child-nodes .node-row::before {
                content: "";
                position: absolute;
                left: -17px;
                top: 50%;
                width: 15px;
                height: 2px;
                border-bottom: 2px dashed #ddd;
            }

            /* iOS style Switch */
            .switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 22px;
            }
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0; left: 0; right: 0; bottom: 0;
                background-color: #ccc;
                transition: 0.3s;
                border-radius: 22px;
            }
            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
            }
            input:checked + .slider {
                background-color: #007cba;
            }
            input:checked + .slider:before {
                transform: translateX(22px);
            }
            input:disabled + .slider {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* Live Preview Mockup Styles */
            .preview-browser {
                background: #f7fafc;
                border-radius: 10px;
                border: 1px solid #cbd5e0;
                overflow: visible; /* Ensure dropdowns are not cut off */
                box-shadow: 0 10px 25px rgba(0,0,0,0.05);
                margin-top: 15px;
                position: relative;
            }
            .browser-header {
                background: #edf2f7;
                padding: 12px 20px;
                display: flex;
                align-items: center;
                gap: 8px;
                border-bottom: 1px solid #cbd5e0;
                border-top-left-radius: 9px;
                border-top-right-radius: 9px;
            }
            .browser-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #fc8181;
            }
            .browser-dot:nth-child(2) { background: #f6e05e; }
            .browser-dot:nth-child(3) { background: #68d391; }
            .browser-address {
                background: #fff;
                flex: 0 1 400px;
                margin: 0 auto;
                border-radius: 6px;
                padding: 5px 12px;
                font-size: 11px;
                color: #718096;
                text-align: center;
                border: 1px solid #cbd5e0;
                font-family: monospace;
            }
            
            /* Live Web Navbar */
            .preview-navbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #fff;
                padding: 20px 30px;
                min-height: 70px;
                box-sizing: border-box;
                border-bottom-left-radius: 9px;
                border-bottom-right-radius: 9px;
                position: relative;
            }
            .preview-logo {
                font-weight: 800;
                font-size: 20px;
                color: #111b27;
                letter-spacing: -0.5px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .preview-logo-dot {
                width: 8px;
                height: 8px;
                background: #007cba;
                border-radius: 50%;
            }
            .preview-menu {
                display: flex;
                align-items: center;
                gap: 12px;
                list-style: none;
                margin: 0;
                padding: 0;
                flex-wrap: wrap;
            }
            .preview-item {
                position: relative;
            }
            .preview-link {
                text-decoration: none;
                color: #4a5568;
                font-weight: 600;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 4px;
                padding: 8px 12px;
                border-radius: 4px;
                transition: background 0.2s, color 0.2s;
            }
            .preview-link:hover {
                background: #f0f4f8;
                color: #007cba;
            }
            
            /* Mega Menu Dropdown Layout */
            .preview-dropdown {
                display: none;
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                background: #fff;
                border: 1px solid #cbd5e0;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                margin: 0;
                padding: 25px;
                min-width: 650px;
                z-index: 99999;
                box-sizing: border-box;
                text-align: left;
            }
            .preview-item:hover .preview-dropdown {
                display: flex;
                gap: 25px;
            }
            .dropdown-columns-wrapper {
                display: flex;
                flex: 1;
                gap: 20px;
            }
            .dropdown-column {
                flex: 1;
                min-width: 140px;
            }
            .dropdown-column-heading {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                color: #a0aec0;
                margin-bottom: 12px;
                letter-spacing: 0.5px;
                font-family: inherit;
            }
            .dropdown-column-links {
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .dropdown-link-item {
                margin-bottom: 12px !important;
                list-style: none !important;
            }
            .dropdown-link {
                text-decoration: none !important;
                color: #2d3748 !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                display: block !important;
                transition: color 0.2s;
            }
            .dropdown-link:hover {
                color: #c95714 !important;
            }
            .dropdown-link-desc {
                font-size: 11px;
                color: #718096;
                margin-top: 3px;
                line-height: 1.4;
                font-weight: 400;
            }

            /* Promo Sidebar Card */
            .dropdown-promo {
                width: 220px;
                border-radius: 6px;
                padding: 15px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                box-sizing: border-box;
            }
            .dropdown-promo.variant-gray {
                background: #f7fafc;
                border: 1px solid #e2e8f0;
            }
            .dropdown-promo.variant-brand {
                background: #ebf8ff;
                border: 1px solid #bee3f8;
            }
            .dropdown-promo-title {
                font-size: 13px;
                font-weight: 700;
                color: #2d3748;
                margin-top: 0;
                margin-bottom: 5px;
            }
            .dropdown-promo-desc {
                font-size: 11px;
                color: #4a5568;
                margin-bottom: 10px;
                line-height: 1.4;
            }
            .dropdown-promo-img {
                width: 100%;
                height: 85px;
                object-fit: cover;
                border-radius: 4px;
                margin-bottom: 10px;
            }
            .dropdown-promo-btn {
                background: #c95714;
                color: #fff !important;
                font-size: 11px;
                font-weight: 700;
                padding: 8px 12px;
                text-align: center;
                border-radius: 4px;
                text-decoration: none !important;
                display: inline-block;
                transition: background 0.2s;
            }
            .dropdown-promo-btn:hover {
                background: #F7610C;
            }

            /* Wide Hero Banner */
            .dropdown-hero {
                width: 280px;
                background-size: cover;
                background-position: center;
                border-radius: 6px;
                padding: 20px;
                color: #fff;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                position: relative;
                overflow: hidden;
                box-sizing: border-box;
            }
            .dropdown-hero::before {
                content: "";
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.8));
                z-index: 1;
            }
            .dropdown-hero-content {
                position: relative;
                z-index: 2;
                text-align: left;
            }
            .dropdown-hero-title {
                font-size: 14px;
                font-weight: 700;
                margin-top: 0;
                margin-bottom: 5px;
                color: #fff;
            }
            .dropdown-hero-desc {
                font-size: 11px;
                color: #e2e8f0;
                margin-bottom: 10px;
                line-height: 1.4;
            }

            /* Image Card style */
            .dropdown-imagecard {
                width: 220px;
                position: relative;
                border-radius: 6px;
                overflow: hidden;
                box-sizing: border-box;
                height: 100%;
                min-height: 180px;
            }
            .dropdown-imagecard img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .dropdown-imagecard-overlay {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.85));
                padding: 15px;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                color: #fff;
                text-align: left;
            }

            /* Preview CTA Button */
            .preview-cta-btn {
                background: #F7610C;
                color: #fff !important;
                font-size: 13px;
                font-weight: 700;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none !important;
                transition: background 0.2s;
                margin-left: 10px;
                display: inline-block;
            }
            .preview-cta-btn:hover {
                background: #c95714;
            }

            .hidden-opacity {
                opacity: 0.3 !important;
                text-decoration: line-through;
            }
        </style>

        <div class="nav-settings-container">
            
            <!-- Top Panel: Full-width Interactive Desktop Header Preview -->
            <div class="settings-card" style="margin-bottom: 30px; max-width: 1200px;">
                <h2>Interactive Desktop Header Preview</h2>
                <p class="description" style="margin-bottom: 15px;">Hover over navigation items to check sub-menu dropdown actions. Hidden items fade out here.</p>
                
                <div class="preview-browser">
                    <div class="browser-header">
                        <div class="browser-dot"></div>
                        <div class="browser-dot"></div>
                        <div class="browser-dot"></div>
                        <div class="browser-address">https://www.inclentrust.org/</div>
                    </div>
                    <div class="preview-navbar">
                        <div class="preview-logo">
                            <img src="https://www.inclentrust.org/inclen_new.png" alt="INCLEN Logo" width="auto" height="38px">
                        </div>
                        <ul class="preview-menu" id="preview_nav_menu"></ul>
                    </div>
                </div>
            </div>

            <!-- Bottom Panel: Controls & Summary Grid -->
            <div class="nav-settings-grid">
                
                <!-- Left Panel Controls -->
                <div class="settings-card">
                    <h2>Navigation Items Tree <button type="button" class="button button-primary button-small" onclick="openAddNodeModal(null)" style="float: right; margin-top: -3px;">+ Add Top-Level Menu</button></h2>
                    <div id="tree_controls_container" style="margin-top: 15px;"></div>
                </div>

                <!-- Right Panel Actions & Summary -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="settings-card">
                        <h2>Actions</h2>
                        <button type="button" class="button button-primary button-large" id="save_nav_btn" onclick="saveNavigation()" style="width: 100%; height: 45px; font-size: 15px; font-weight: 600;">
                            Save Navigation Settings
                        </button>
                        <button type="button" class="button button-secondary" onclick="resetToDefaults()" style="width: 100%; height: 40px; margin-top: 15px; font-weight: 500;">
                            Reset to Default Menu
                        </button>
                        <div style="text-align: center; margin-top: 10px;">
                            <span id="save_spinner" class="spinner" style="float: none; margin: 0; vertical-align: middle;"></span>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h2>Configuration Summary</h2>
                        <table class="wp-list-table widefat fixed striped" style="box-shadow: none; border: none; background: transparent;">
                            <thead>
                                <tr>
                                    <th>Top-Level</th>
                                    <th>Sub-Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="summary_parent_count">0 items</td>
                                    <td id="summary_child_count">0 items</td>
                                    <td><span class="badge" id="summary_status_badge" style="background:#46b450;color:#fff;padding:2px 8px;border-radius:4px;font-weight:600;font-size:11px;">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add Item Modal Dialog -->
        <div id="add_node_modal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4); backdrop-filter: blur(2px);">
            <div class="modal-content" style="background-color:#fefefe; margin:12% auto; padding:25px; border:1px solid #ccd0d4; width:450px; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.15); box-sizing: border-box;">
                <h2 id="modal_title" style="margin-top:0; font-size:18px; font-weight:600; padding-bottom:12px; border-bottom:1px solid #eee; color:#1d2327;">Add Menu Item</h2>
                <input type="hidden" id="modal_parent_key">
                
                <div style="margin-top:15px; margin-bottom:15px;">
                    <label for="modal_item_label" style="font-weight:600; color:#1d2327;">Menu Label (Display Text):</label>
                    <input type="text" id="modal_item_label" class="regular-text" style="width:100%; margin-top:5px; padding:6px 10px; border-radius:4px; border:1px solid #8c8f94;" placeholder="e.g. Services">
                </div>
                <div style="margin-top:15px; margin-bottom:20px;">
                    <label for="modal_item_href" style="font-weight:600; color:#1d2327;">Link URL (Href Path):</label>
                    <input type="text" id="modal_item_href" class="regular-text" style="width:100%; margin-top:5px; padding:6px 10px; border-radius:4px; border:1px solid #8c8f94;" placeholder="e.g. /services or #">
                </div>
                
                <div style="text-align:right; gap:10px; display:flex; justify-content:flex-end;">
                    <button type="button" class="button button-secondary" onclick="closeAddNodeModal()">Cancel</button>
                    <button type="button" class="button button-primary" onclick="submitAddNode()">Add Menu Item</button>
                </div>
            </div>
        </div>

    <script>
    const NAV_API_BASE = "<?php echo site_url('/wp-json/navigation/v1'); ?>";
    const NAV_WP_NONCE = "<?php echo wp_create_nonce('wp_rest'); ?>";
    
    let dbRecordId = null;
    let menuStructure = [];

    const MEGA_MENU_CONFIG = {
        about: {
            columns: [
                {
                    heading: 'Organization',
                    linkKeys: ['about_who_we_are', 'about_mission_vision', 'about_presence', 'about_fcra']
                },
                {
                    heading: 'Leadership',
                    linkKeys: ['about_board', 'about_journey', 'about_collaborators']
                }
            ],
            promo: {
                variant: 'gray',
                title: 'Empowering Global Health',
                description: 'Learn how INCLEN is working with global health stakeholders to drive meaningful change.',
                href: '/about',
                ctaLabel: 'Read More',
                image: 'https://images.pexels.com/photos/3184418/pexels-photo-3184418.jpeg'
            }
        },
        our_work: {
            columns: [
                {
                    heading: 'Focus Areas',
                    linkKeys: ['work_area', 'work_research']
                },
                {
                    heading: 'Key Initiatives',
                    linkKeys: ['work_somaarth', 'work_capacity']
                },
                {
                    heading: 'Outreach',
                    linkKeys: ['work_engagement', 'work_community']
                }
            ]
        },
        our_impact: {
            columns: [
                {
                    heading: 'Overview',
                    linkKeys: ['impact_summary', 'impact_partners']
                },
                {
                    heading: 'Achievements',
                    linkKeys: ['impact_findings', 'impact_device_products', 'impact_influence']
                }
            ],
            promo: {
                variant: 'brand',
                title: 'Transforming Lives',
                description: 'See how our research translates into real-world health solutions.',
                href: '/our-impact',
                ctaLabel: 'Explore Impact',
                image: 'https://images.pexels.com/photos/6120214/pexels-photo-6120214.jpeg'
            }
        },
        careers: {
            columns: [
                {
                    heading: 'Opportunities',
                    linkKeys: ['careers_openings', 'careers_fellowships', 'careers_internships']
                }
            ],
            heroBanner: {
                image: 'https://images.pexels.com/photos/3184357/pexels-photo-3184357.jpeg',
                title: 'Shape the Future of Health',
                description: 'Join a team of dedicated professionals working towards global health equity.',
                ctaLabel: 'View All Openings',
                ctaHref: '/careers'
            }
        },
        get_involved: {
            columns: [
                {
                    heading: 'Academic & Research Calls',
                    linkKeys: ['involved_academic', 'involved_research']
                },
                {
                    heading: 'Strategic Partnerships',
                    linkKeys: ['involved_industry']
                }
            ]
        },
        insights: {
            columns: [
                {
                    heading: 'Updates',
                    linkKeys: ['insights_news', 'insights_events']
                },
                {
                    heading: 'Media',
                    linkKeys: ['insights_announcements', 'insights_headlines']
                }
            ]
        },
        resources: {
            columns: [
                {
                    heading: 'Publications',
                    linkKeys: ['resources_all', 'resources_reports', 'resources_newsletters']
                },
                {
                    heading: 'Tools & Data',
                    linkKeys: ['resources_repository', 'resources_tools', 'resources_training']
                }
            ],
            imageCard: {
                image: 'https://images.pexels.com/photos/159866/books-book-pages-read-literature-159866.jpeg',
                title: 'Knowledge Hub',
                description: 'Access our extensive library of research and resources.'
            }
        }
    };

    const LINK_DESCRIPTIONS = {
        work_area: 'Explore our key focus areas and strategic impact domains.',
        work_research: 'Cutting-edge health research, synthesis and analysis.',
        work_somaarth: 'Demographic Development & Environmental Surveillance.',
        work_capacity: 'Strengthening healthcare systems and leadership capabilities.',
        work_engagement: 'Policy advocacy and multi-stakeholder engagement.',
        work_community: 'Community activities and engagement.',
        involved_academic: 'Join our global network of academic professionals and researchers.',
        involved_research: 'Collaborate with INCLEN on groundbreaking health studies.',
        involved_industry: 'Strategic alliances for healthcare innovation and delivery.',
        insights_news: 'Latest updates and press releases from INCLEN.',
        insights_events: 'Upcoming conferences, workshops, and webinars.',
        insights_announcements: 'Official notifications and public notices.',
        insights_headlines: 'INCLEN in the news and media features.'
    };

    // Load configurations from REST API
    function loadNavigation() {
        const primaryUrl = NAV_API_BASE + "/all";
        const fallbackUrl = "<?php echo site_url('/index.php?rest_route=/navigation/v1/all'); ?>";

        fetch(primaryUrl)
        .then(res => {
            if (!res.ok) throw new Error("Primary failed");
            return res.json();
        })
        .then(data => handleLoadedData(data))
        .catch(err => {
            fetch(fallbackUrl)
            .then(res => {
                if (!res.ok) throw new Error("Fallback failed");
                return res.json();
            })
            .then(data => handleLoadedData(data))
            .catch(err2 => console.error("Failed to load navigation configuration:", err2));
        });
    }

    function handleLoadedData(data) {
        dbRecordId = data.id;
        menuStructure = data.menu_structure;
        
        // Auto reset if structures got mismatched or have old format keys
        if (menuStructure.length > 0 && !menuStructure.some(x => x.key === 'about' || x.key === 'our_work')) {
            menuStructure = <?php echo json_encode(get_default_menu_structure()); ?>;
        }
        
        renderTreeControls();
        renderLivePreview();
        updateSummary();
    }

    // Render left-side hierarchical controllers
    function renderTreeControls() {
        const container = document.getElementById('tree_controls_container');
        container.innerHTML = '';

        menuStructure.forEach((item, parentIdx) => {
            // Parent Row Node
            const parentDiv = document.createElement('div');
            parentDiv.className = 'tree-node parent-node';
            
            parentDiv.innerHTML = `
                <div class="node-row" id="row_${item.key}">
                    <div class="node-label-container">
                        <span class="node-label">${item.label}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" class="button-link button-link-delete" onclick="deleteNode('${item.key}')" title="Delete Parent Menu" style="color: #a10000; text-decoration: none; padding: 0; margin-right: 5px; cursor: pointer; display: flex; align-items: center;"><span class="dashicons dashicons-trash" style="font-size: 18px; width: 18px; height: 18px;"></span></button>
                        <label class="switch">
                            <input type="checkbox" id="switch_${item.key}" 
                                   data-key="${item.key}" 
                                   ${item.visible ? 'checked' : ''} 
                                   onchange="toggleParent('${item.key}', ${parentIdx})">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            `;
            
            // Sub-items list
            const childContainer = document.createElement('div');
            childContainer.className = 'child-nodes';
            childContainer.id = `children_${item.key}`;
            
            // If parent is disabled, visual grey out of child container
            if (!item.visible) {
                childContainer.style.opacity = '0.5';
            }

            const children = item.children || [];
            children.forEach((child, childIdx) => {
                const childDiv = document.createElement('div');
                childDiv.className = 'tree-node';
                childDiv.innerHTML = `
                    <div class="node-row" id="row_${child.key}">
                        <div class="node-label-container">
                            <span class="node-label">${child.label}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button type="button" class="button-link button-link-delete" onclick="deleteNode('${child.key}')" title="Delete Sub-item" style="color: #a10000; text-decoration: none; padding: 0; margin-right: 5px; cursor: pointer; display: flex; align-items: center;"><span class="dashicons dashicons-trash" style="font-size: 18px; width: 18px; height: 18px;"></span></button>
                            <label class="switch">
                                <input type="checkbox" id="switch_${child.key}" 
                                       data-key="${child.key}" 
                                       ${child.visible ? 'checked' : ''} 
                                       ${!item.visible ? 'disabled' : ''}
                                       onchange="toggleChild(${parentIdx}, ${childIdx})">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                `;
                childContainer.appendChild(childDiv);
            });

            // "+ Add Sub-item" button
            const addBtnDiv = document.createElement('div');
            addBtnDiv.style.marginTop = '10px';
            addBtnDiv.style.marginBottom = '5px';
            addBtnDiv.innerHTML = `
                <button type="button" class="button button-secondary button-small" onclick="openAddNodeModal('${item.key}')">+ Add Sub-item</button>
            `;
            childContainer.appendChild(addBtnDiv);
            
            parentDiv.appendChild(childContainer);
            container.appendChild(parentDiv);
        });
    }

    // Render dynamic visual preview matching front-end navbar
    function renderLivePreview() {
        const navMenu = document.getElementById('preview_nav_menu');
        navMenu.innerHTML = '';

        menuStructure.forEach(item => {
            // Check top level visibility
            if (!item.visible) return;

            const config = MEGA_MENU_CONFIG[item.key];
            if (!config) {
                // If contact, render as CTA or skip to render at the end
                if (item.key === 'contact') return;

                // Simple parent menu item without children (like Home)
                const li = document.createElement('li');
                li.className = 'preview-item';
                li.innerHTML = `<a href="#" class="preview-link">${item.label}</a>`;
                navMenu.appendChild(li);
                return;
            }

            const li = document.createElement('li');
            li.className = 'preview-item';
            
            let hasVisibleChildren = false;
            let columnsHtml = '';

            config.columns.forEach(col => {
                const visibleColLinks = col.linkKeys
                    .map(k => item.children.find(c => c.key === k))
                    .filter(c => c && c.visible);

                if (visibleColLinks.length > 0) {
                    hasVisibleChildren = true;
                    columnsHtml += `
                        <div class="dropdown-column">
                            <div class="dropdown-column-heading">${col.heading}</div>
                            <ul class="dropdown-column-links">
                    `;
                    visibleColLinks.forEach(link => {
                        const desc = LINK_DESCRIPTIONS[link.key] ? `<div class="dropdown-link-desc">${LINK_DESCRIPTIONS[link.key]}</div>` : '';
                        columnsHtml += `
                            <li class="dropdown-link-item">
                                <a href="#" class="dropdown-link">${link.label}</a>
                                ${desc}
                            </li>
                        `;
                    });
                    columnsHtml += `</ul></div>`;
                }
            });

            let promoHtml = '';
            if (hasVisibleChildren) {
                if (config.promo) {
                    promoHtml = `
                        <div class="dropdown-promo variant-${config.promo.variant}">
                            <div class="dropdown-promo-content">
                                <h4 class="dropdown-promo-title">${config.promo.title}</h4>
                                <p class="dropdown-promo-desc">${config.promo.description}</p>
                            </div>
                            ${config.promo.image ? `<img src="${config.promo.image}" class="dropdown-promo-img">` : ''}
                            ${config.promo.ctaLabel ? `<a href="#" class="dropdown-promo-btn">${config.promo.ctaLabel}</a>` : ''}
                        </div>
                    `;
                } else if (config.heroBanner) {
                    promoHtml = `
                        <div class="dropdown-hero" style="background-image: url('${config.heroBanner.image}')">
                            <div class="dropdown-hero-content">
                                <h4 class="dropdown-hero-title">${config.heroBanner.title}</h4>
                                <p class="dropdown-hero-desc">${config.heroBanner.description}</p>
                                ${config.heroBanner.ctaLabel ? `<a href="#" class="dropdown-promo-btn">${config.heroBanner.ctaLabel}</a>` : ''}
                            </div>
                        </div>
                    `;
                } else if (config.imageCard) {
                    promoHtml = `
                        <div class="dropdown-imagecard">
                            <img src="${config.imageCard.image}" alt="${config.imageCard.title}">
                            <div class="dropdown-imagecard-overlay">
                                <h4 class="dropdown-hero-title" style="margin-bottom:5px;">${config.imageCard.title}</h4>
                                <p class="dropdown-hero-desc" style="margin-bottom:0;">${config.imageCard.description}</p>
                            </div>
                        </div>
                    `;
                }
            }

            if (hasVisibleChildren) {
                li.innerHTML = `
                    <a href="#" class="preview-link">
                        ${item.label} <span style="font-size: 8px; margin-left: 2px; color: #a0aec0;">▼</span>
                    </a>
                    <div class="preview-dropdown">
                        <div class="dropdown-columns-wrapper">${columnsHtml}</div>
                        ${promoHtml}
                    </div>
                `;
            } else {
                li.innerHTML = `
                    <a href="#" class="preview-link">
                        ${item.label}
                    </a>
                `;
            }
            navMenu.appendChild(li);
        });

        // Contact button as CTA at the end
        const contactItem = menuStructure.find(i => i.key === 'contact');
        if (contactItem && contactItem.visible) {
            const ctaLi = document.createElement('li');
            ctaLi.style.listStyle = 'none';
            ctaLi.innerHTML = `<a href="#" class="preview-cta-btn">Contact Us</a>`;
            navMenu.appendChild(ctaLi);
        }
    }

    // Toggle parent switch handler
    function toggleParent(key, idx) {
        const checkbox = document.getElementById(`switch_${key}`);
        const isVisible = checkbox.checked;
        menuStructure[idx].visible = isVisible;

        // Visual opacity & disabled child inputs updates
        const childContainer = document.getElementById(`children_${key}`);
        if (childContainer) {
            childContainer.style.opacity = isVisible ? '1' : '0.5';
            const childInputs = childContainer.querySelectorAll('input[type="checkbox"]');
            childInputs.forEach(input => {
                input.disabled = !isVisible;
            });
        }

        renderLivePreview();
        updateSummary();
    }

    // Toggle child switch handler
    function toggleChild(parentIdx, childIdx) {
        const parent = menuStructure[parentIdx];
        const child = parent.children[childIdx];
        const checkbox = document.getElementById(`switch_${child.key}`);
        child.visible = checkbox.checked;

        renderLivePreview();
        updateSummary();
    }

    // Update bottom summary counts
    function updateSummary() {
        let parentTotal = menuStructure.length;
        let parentVisible = menuStructure.filter(p => p.visible).length;
        
        let childTotal = 0;
        let childVisible = 0;

        menuStructure.forEach(item => {
            if (item.children) {
                childTotal += item.children.length;
                childVisible += item.children.filter(c => c.visible).length;
            }
        });

        document.getElementById('summary_parent_count').innerText = `${parentVisible} of ${parentTotal} active`;
        document.getElementById('summary_child_count').innerText = `${childVisible} of ${childTotal} active`;
    }

    // Save menu visibility states
    function saveNavigation() {
        const saveBtn = document.getElementById('save_nav_btn');
        const spinner = document.getElementById('save_spinner');

        saveBtn.disabled = true;
        spinner.classList.add('is-active');

        const data = {
            id: dbRecordId,
            menu_structure: menuStructure
        };

        const primaryUrl = NAV_API_BASE + "/save";
        const fallbackUrl = "<?php echo site_url('/index.php?rest_route=/navigation/v1/save'); ?>";

        function sendRequest(url) {
            return fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": NAV_WP_NONCE
                },
                body: JSON.stringify(data)
            })
            .then(res => {
                if (!res.ok) throw new Error("Save request failed");
                return res.json();
            });
        }

        function handleSaveSuccess(result) {
            saveBtn.disabled = false;
            spinner.classList.remove('is-active');

            if (result.status === 'success') {
                alert("Navigation menu visibility saved successfully!");
                loadNavigation();
            } else {
                alert("Error saving settings.");
            }
        }

        sendRequest(primaryUrl)
        .then(result => handleSaveSuccess(result))
        .catch(err => {
            sendRequest(fallbackUrl)
            .then(result => handleSaveSuccess(result))
            .catch(err2 => {
                saveBtn.disabled = false;
                spinner.classList.remove('is-active');
                alert("Error sending request: " + err2.message);
            });
        });
    }

    // Reset structure back to default settings
    function resetToDefaults() {
        if (!confirm("Are you sure you want to reset the navigation structure to defaults? Unsaved changes will be lost.")) return;
        
        menuStructure = <?php echo json_encode(get_default_menu_structure()); ?>;
        renderTreeControls();
        renderLivePreview();
        updateSummary();
    }

    // Modal controls for Adding Menu Item
    function openAddNodeModal(parentKey) {
        document.getElementById('modal_parent_key').value = parentKey || '';
        document.getElementById('modal_item_label').value = '';
        document.getElementById('modal_item_href').value = '';
        
        const titleEl = document.getElementById('modal_title');
        if (parentKey) {
            titleEl.innerText = `Add Sub-item under "${parentKey}"`;
        } else {
            titleEl.innerText = "Add Top-Level Menu";
        }
        
        document.getElementById('add_node_modal').style.display = 'block';
    }

    function closeAddNodeModal() {
        document.getElementById('add_node_modal').style.display = 'none';
    }

    // Submit newly created menu item to REST API
    function submitAddNode() {
        const label = document.getElementById('modal_item_label').value.trim();
        const href = document.getElementById('modal_item_href').value.trim() || '#';
        const parentKey = document.getElementById('modal_parent_key').value;

        if (!label) {
            alert("Please enter a menu label.");
            return;
        }

        const primaryUrl = NAV_API_BASE + "/create";
        const fallbackUrl = "<?php echo site_url('/index.php?rest_route=/navigation/v1/create'); ?>";
        const data = { label, href, parent_key: parentKey };

        function sendCreateRequest(url) {
            return fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": NAV_WP_NONCE
                },
                body: JSON.stringify(data)
            })
            .then(res => {
                if (!res.ok) throw new Error("Create request failed");
                return res.json();
            });
        }

        sendCreateRequest(primaryUrl)
        .then(res => handleCreateSuccess(res))
        .catch(err => {
            sendCreateRequest(fallbackUrl)
            .then(res => handleCreateSuccess(res))
            .catch(err2 => alert("Error creating menu item: " + err2.message));
        });

        function handleCreateSuccess(res) {
            if (res.status === 'success') {
                closeAddNodeModal();
                loadNavigation();
            } else {
                alert("Error creating menu item.");
            }
        }
    }

    // Submit deletion of menu item to REST API
    function deleteNode(key) {
        if (!confirm(`Are you sure you want to delete the menu item "${key}"?`)) return;

        const primaryUrl = NAV_API_BASE + "/delete";
        const fallbackUrl = "<?php echo site_url('/index.php?rest_route=/navigation/v1/delete'); ?>";
        const data = { key };

        function sendDeleteRequest(url) {
            return fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": NAV_WP_NONCE
                },
                body: JSON.stringify(data)
            })
            .then(res => {
                if (!res.ok) throw new Error("Delete request failed");
                return res.json();
            });
        }

        sendDeleteRequest(primaryUrl)
        .then(res => handleDeleteSuccess(res))
        .catch(err => {
            sendDeleteRequest(fallbackUrl)
            .then(res => handleDeleteSuccess(res))
            .catch(err2 => alert("Error deleting menu item: " + err2.message));
        });

        function handleDeleteSuccess(res) {
            if (res.status === 'success') {
                loadNavigation();
            } else {
                alert("Error deleting menu item.");
            }
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadNavigation();
    });
    </script>
<?php }
