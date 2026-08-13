<?php
/**
 * Custom Theme functions and definitions.
 */

// Include Cloud configuration
if (file_exists(__DIR__ . '/cloud_config.php')) {
    include('cloud_config.php');
}

// Hide admin menus
function custom_hide_admin_menu() {
    remove_menu_page('edit.php'); 
    remove_menu_page('upload.php');     
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php'); // Allow theme access to switch back if needed, or uncomment to hide
    remove_menu_page('plugins.php');
    remove_menu_page('users.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
}
add_action('admin_menu', 'custom_hide_admin_menu', 999);

// Load AWS SDK
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Aws\S3\S3Client;

// Add Parent Menus for Managers (Grouped 2-by-2)
function custom_register_grouped_menus() {

     // Group 5: Collaborators & Library
    add_menu_page('About', 'About', 'manage_options', 'group-resources', '', 'dashicons-database', 29);
    // Group 1: News & Updates
    add_menu_page('Insight', 'Insight', 'manage_options', 'group-news', '', 'dashicons-megaphone', 25);
    
    // Group 3: Careers
    add_menu_page('Careers', 'Careers', 'manage_options', 'group-careers', '', 'dashicons-businessperson', 27);
    
    // Group 4: Team & Research
    add_menu_page('Resources', 'Resources', 'manage_options', 'group-team', '', 'dashicons-groups', 28);
    
   
    
    // Group 6: Projects & Tools
    add_menu_page('Projects & Tools', 'Projects & Tools', 'manage_options', 'group-research', '', 'dashicons-admin-tools', 30);
}
add_action('admin_menu', 'custom_register_grouped_menus');

// Make Parent Menus Unclickable
function custom_make_menus_unclickable() {
    ?>
    <style>
        #toplevel_page_group-news > a,
        #toplevel_page_group-events > a,
        #toplevel_page_group-careers > a,
        #toplevel_page_group-team > a,
        #toplevel_page_group-resources > a,
        #toplevel_page_group-research > a {
            pointer-events: none !important;
            cursor: default !important;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $('#toplevel_page_group-news > a, #toplevel_page_group-events > a, #toplevel_page_group-careers > a, #toplevel_page_group-team > a, #toplevel_page_group-resources > a, #toplevel_page_group-research > a').on('click', function(e) {
                e.preventDefault();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'custom_make_menus_unclickable');

// Remove duplicate parent link from the submenus
function custom_remove_duplicate_submenus() {
    remove_submenu_page('group-news', 'group-news');
    remove_submenu_page('group-events', 'group-events');
    remove_submenu_page('group-careers', 'group-careers');
    remove_submenu_page('group-team', 'group-team');
    remove_submenu_page('group-resources', 'group-resources');
    remove_submenu_page('group-research', 'group-research');
}
add_action('admin_menu', 'custom_remove_duplicate_submenus', 999);

// Include Manager Files
$managers = [
    'news-manager.php',
    'announcement.php',
    'events.php',
    'headings.php',
    'current-openings.php',
    'internship-manager.php',
    'publications-manager.php',
    'governance-team-manager.php',
    'academic-collaborators.php',
    'document-library.php',
    'research-projects.php',
    'inclen-tools.php'
];

foreach ($managers as $manager) {
    if (file_exists(__DIR__ . '/' . $manager)) {
        include($manager);
    }
}

/**
 * Theme Functions - Custom Login with CAPTCHA
 */

function custom_redirect_login_page() {
    $login_page = home_url('/login/');
    $page_viewed = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    if ($page_viewed === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['logout', 'lostpassword', 'rp', 'resetpass'])) {
            return;
        }
        wp_redirect($login_page);
        exit;
    }
}
add_action('init', 'custom_redirect_login_page', 1);


/* =============================================
   2. Redirect logged-in users away from login page
   ============================================= */
function custom_redirect_logged_in_user() {
    if (is_user_logged_in() && is_page('login')) {
        wp_redirect(admin_url());
        exit;
    }
}
add_action('template_redirect', 'custom_redirect_logged_in_user');


/* =============================================
   3. Custom Logout Redirect
   ============================================= */
function custom_logout_redirect() {
    wp_redirect(home_url('/login/'));
    exit;
}
add_action('wp_logout', 'custom_logout_redirect');


/* =============================================
   4. LOGIN VALIDATIONS - Correct Priority Order
   ============================================= */

/**
 * Priority 10: CAPTCHA Verification (First Check)
 */
function verify_captcha_login($user, $username, $password) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $user;
    }

    // Check if CAPTCHA is empty
    if (empty($_POST['g-recaptcha-response'])) {
        wp_redirect(home_url('/login/?error=captcha_empty'));
        exit;
    }

    $secret   = '6LdGZj8tAAAAAFuP_kM8Npb2dEIBwM8r2FuPaD1s';
    $response = sanitize_text_field($_POST['g-recaptcha-response']);

    // Verify with Google reCAPTCHA
    $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        )
    ));

    if (is_wp_error($verify)) {
        wp_redirect(home_url('/login/?error=captcha_failed'));
        exit;
    }

    $body   = wp_remote_retrieve_body($verify);
    $result = json_decode($body, true);

    if (empty($result['success']) || $result['success'] !== true) {
        wp_redirect(home_url('/login/?error=captcha_failed'));
        exit;
    }

    return $user;
}
add_filter('authenticate', 'verify_captcha_login', 10, 3);


/**
 * Priority 20: Empty Username or Password Check
 */
function custom_verify_user_pass($user, $username, $password) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $user;
    }

    if (empty($username) || empty($password)) {
        wp_redirect(home_url('/login/?login=failed'));
        exit;
    }

    return $user;
}
add_filter('authenticate', 'custom_verify_user_pass', 20, 3);


/**
 * Login Failed (Wrong username or password)
 */
function custom_login_failed() {
    wp_redirect(home_url('/login/?login=failed'));
    exit;
}
add_action('wp_login_failed', 'custom_login_failed', 10);



?>