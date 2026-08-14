<?php
/**
 * Custom Theme functions and definitions.
 */

// Include Cloud configuration
if (file_exists(__DIR__ . '/cloud_config.php')) {
    include('cloud_config.php');
}

// Global utility for admin capability check
if (!function_exists('is_user_admin_check')) {
    function is_user_admin_check()
    {
        return current_user_can('manage_options');
    }
}

// Setup Database Tables
if (file_exists(__DIR__ . '/setup-tables.php')) {
    include(__DIR__ . '/setup-tables.php');
}


// Hide admin menus
function custom_hide_admin_menu()
{
    remove_menu_page('edit.php');
    remove_menu_page('upload.php');
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php');
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

// Add Parent Menus for Managers
function custom_register_grouped_menus()
{
    add_menu_page('Home Page Sections', 'Home Page Sections', 'manage_options', 'group-home', '', 'dashicons-admin-home', 24);
    add_menu_page('About', 'About', 'manage_options', 'group-resources', '', 'dashicons-database', 29);
    add_menu_page('Insight', 'Insight', 'manage_options', 'group-news', '', 'dashicons-megaphone', 25);
    add_menu_page('Careers', 'Careers', 'manage_options', 'group-careers', '', 'dashicons-businessperson', 27);
    add_menu_page('Resources', 'Resources', 'manage_options', 'group-team', '', 'dashicons-groups', 28);
    add_menu_page('Projects & Tools', 'Projects & Tools', 'manage_options', 'group-research', '', 'dashicons-admin-tools', 30);
    add_menu_page('Partners', 'Partners', 'manage_options', 'group-partners', '', 'dashicons-networking', 26);
}
add_action('admin_menu', 'custom_register_grouped_menus');

// Make Parent Menus Unclickable
function custom_make_menus_unclickable()
{
    ?>
    <style>
        #toplevel_page_group-home>a,
        #toplevel_page_group-news>a,
        #toplevel_page_group-events>a,
        #toplevel_page_group-careers>a,
        #toplevel_page_group-team>a,
        #toplevel_page_group-resources>a,
        #toplevel_page_group-research>a,
        #toplevel_page_group-partners>a {
            pointer-events: none !important;
            cursor: default !important;
        }
    </style>
    <script>
        jQuery(document).ready(function ($) {
            $('#toplevel_page_group-home > a, #toplevel_page_group-news > a, #toplevel_page_group-events > a, #toplevel_page_group-careers > a, #toplevel_page_group-team > a, #toplevel_page_group-resources > a, #toplevel_page_group-research > a, #toplevel_page_group-partners > a').on('click', function (e) {
                e.preventDefault();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'custom_make_menus_unclickable');

// Remove duplicate parent link from the submenus
function custom_remove_duplicate_submenus()
{
    remove_submenu_page('group-home', 'group-home');
    remove_submenu_page('group-news', 'group-news');
    remove_submenu_page('group-events', 'group-events');
    remove_submenu_page('group-careers', 'group-careers');
    remove_submenu_page('group-team', 'group-team');
    remove_submenu_page('group-resources', 'group-resources');
    remove_submenu_page('group-research', 'group-research');
    remove_submenu_page('group-partners', 'group-partners');
}
add_action('admin_menu', 'custom_remove_duplicate_submenus', 999);

// Include Manager Files
$managers = [
    'news-manager.php',
    'blog-manager.php',
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
    'inclen-tools.php',
    'completed-projects.php',
    'priority-settings.php',
    'download-requests.php',
    'partners-manager.php',
    'annual-reports.php',
    'newsletters-manager.php',
    'device-products.php',
    'fcra-registration.php',
    'training-materials.php',
    'data-repository.php',
    'home-manager.php',
    'about-manager.php',
    'navigation-manager.php'
];

foreach ($managers as $manager) {
    $manager_path = __DIR__ . '/' . $manager;
    if (file_exists($manager_path)) {
        include($manager_path);
    }
}

// Redirect failed logins to custom login page
function custom_login_failed()
{
    wp_redirect(site_url('wp-login.php?login=failed'));
    exit;
}
add_action('wp_login_failed', 'custom_login_failed');

// Redirect empty login to custom login page
function custom_authenticate($user, $username, $password)
{
    if (isset($_POST['log']) && (empty($username) || empty($password))) {
        wp_redirect(site_url('wp-login.php?login=failed'));
        exit;
    }
    return $user;
}
add_filter('authenticate', 'custom_authenticate', 1, 3);

function custom_use_login_template()
{
    global $pagenow;
    if ($pagenow === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_REQUEST['action'])) {
        $template = __DIR__ . '/page-login.php';
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }
}
add_action('login_init', 'custom_use_login_template');