<?php
/**
 * Setup and Update Database Tables for INCLEN Managers
 */

function custom_setup_database_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. INCLEN Tools Table
    $table_inclen = $wpdb->prefix . 'inclen_tools';
    $sql_inclen = "CREATE TABLE $table_inclen (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        project_name varchar(255) NOT NULL,
        tool_name varchar(255) DEFAULT '',
        modules text DEFAULT '[]',
        cover_image varchar(255) DEFAULT '',
        pdfs text DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_inclen);

    // 2. Research Projects (Original)
    $table_research = $wpdb->prefix . 'research_projects';
    $sql_research = "CREATE TABLE $table_research (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research);

    // 3. Completed Projects (New)
    $table_completed = $wpdb->prefix . 'completed_projects';
    $sql_completed = "CREATE TABLE $table_completed (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        co_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_completed);

    // 4. Research Priority Settings (New)
    $table_priority = $wpdb->prefix . 'priority_settings';
    $sql_priority = "CREATE TABLE $table_priority (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_priority);

    // 5. Document Library (Original)
    $table_docs = $wpdb->prefix . 'document_library';
    $sql_docs = "CREATE TABLE $table_docs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_docs);

    // 6. Download Requests Table
    $table_requests = $wpdb->prefix . 'download_requests';
    $sql_requests = "CREATE TABLE $table_requests (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(100) NOT NULL,
        location varchar(255) DEFAULT '',
        speciality varchar(255) DEFAULT '',
        project_title varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_requests);

    // 7. Partners Table
    $table_partners = $wpdb->prefix . 'partners';
    $sql_partners = "CREATE TABLE $table_partners (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        about text DEFAULT '',
        description longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_partners);

    // 8. Industry Partnerships Table
    $table_industry = $wpdb->prefix . 'industry_partnerships';
    $sql_industry = "CREATE TABLE $table_industry (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_industry);

    // 9. Research Partnerships Table
    $table_research_part = $wpdb->prefix . 'research_partnerships';
    $sql_research_part = "CREATE TABLE $table_research_part (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research_part);

    // 10. Annual Reports Table
    $table_annual_reports = $wpdb->prefix . 'annual_reports';
    $sql_annual_reports = "CREATE TABLE $table_annual_reports (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_annual_reports);

    // 11. Newsletters Table
    $table_newsletters = $wpdb->prefix . 'newsletters';
    $sql_newsletters = "CREATE TABLE $table_newsletters (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_newsletters);

    // 12. Device Products Table
    $table_device_products = $wpdb->prefix . 'device_products';
    $sql_device_products = "CREATE TABLE $table_device_products (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        short_description text DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_device_products);

    // 13. FCRA & Registration Table
    $table_fcra = $wpdb->prefix . 'fcra_registration';
    $sql_fcra = "CREATE TABLE $table_fcra (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        declared_year varchar(50) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_fcra);

    // 14. Training Materials Table
    $table_training = $wpdb->prefix . 'training_materials';
    $sql_training = "CREATE TABLE $table_training (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        module varchar(255) DEFAULT '',
        level varchar(255) DEFAULT '',
        duration varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_training);

    // 15. Data Repository Table
    $table_data_repo = $wpdb->prefix . 'data_repository';
    $sql_data_repo = "CREATE TABLE $table_data_repo (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category varchar(255) DEFAULT '',
        title varchar(255) NOT NULL,
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_data_repo);

    // 16. Home Page Hero Section Table
    $table_home_hero = $wpdb->prefix . 'home_hero';
    $sql_home_hero = "CREATE TABLE $table_home_hero (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        images longtext,
        heading varchar(255) NOT NULL,
        paragraph longtext DEFAULT '',
        latest_updates longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_hero);

    // 17. Home Page About Section Table
    $table_home_about = $wpdb->prefix . 'home_about';
    $sql_home_about = "CREATE TABLE $table_home_about (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        description longtext DEFAULT '',
        main_image varchar(255) DEFAULT '',
        quote_text varchar(255) DEFAULT '',
        quote_author varchar(255) DEFAULT '',
        stat_number varchar(255) DEFAULT '',
        stat_text varchar(255) DEFAULT '',
        feature_blocks longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_about);

    // 18. Home Page Presence Section Table
    $table_home_presence = $wpdb->prefix . 'home_presence';
    $sql_home_presence = "CREATE TABLE $table_home_presence (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        stats_data longtext,
        countries_data longtext,
        institutions_data longtext,
        networks_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_presence);

    // 19. Home Page Collaborators Section Table
    $table_home_collaborators = $wpdb->prefix . 'home_collaborators';
    $sql_home_collaborators = "CREATE TABLE $table_home_collaborators (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        logos longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_collaborators);

    // 20. PDF Download Leads Table
    $table_download_leads = $wpdb->prefix . 'download_leads';
    $sql_download_leads = "CREATE TABLE $table_download_leads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        location varchar(255) NOT NULL,
        speciality varchar(255) NOT NULL,
        pdf_url varchar(1000) NOT NULL,
        source_section varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_download_leads);

    // 21. Home Page Impact Statistics Table
    $table_home_impact = $wpdb->prefix . 'home_impact';
    $sql_home_impact = "CREATE TABLE $table_home_impact (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        stats longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_impact);

    // 22. Home Page Key Research Areas Table
    $table_home_research_areas = $wpdb->prefix . 'home_research_areas';
    $sql_home_research_areas = "CREATE TABLE $table_home_research_areas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        areas longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_research_areas);

    // 23. About Page Who We Are Table
    $table_about_who_we_are = $wpdb->prefix . 'about_who_we_are';
    $sql_about_who_we_are = "CREATE TABLE $table_about_who_we_are (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description longtext,
        image_url varchar(1000) DEFAULT '',
        quote_text text,
        quote_subtext varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_who_we_are);

    // 24. About Page Our Journey Table
    $table_about_our_journey = $wpdb->prefix . 'about_our_journey';
    $sql_about_our_journey = "CREATE TABLE $table_about_our_journey (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        subheading text DEFAULT '',
        milestones longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_our_journey);

    // 25. Website Navigation Menu Visibility Settings
    $table_site_navigation = $wpdb->prefix . 'site_navigation';
    $sql_site_navigation = "CREATE TABLE $table_site_navigation (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_structure longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_site_navigation);
    // 26. About Page Mission Table
    $table_about_mission = $wpdb->prefix . 'about_mission';
    $sql_about_mission = "CREATE TABLE $table_about_mission (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description text DEFAULT '',
        cards longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_mission);

    // 27. Blogs Table
    $table_blogs = $wpdb->prefix . 'blogs';
    $sql_blogs = "CREATE TABLE $table_blogs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) DEFAULT '',
        content longtext DEFAULT '',
        author varchar(255) DEFAULT '',
        image varchar(255) DEFAULT '',
        banner_image varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_blogs);
}

// Hook to run during theme load or admin init
add_action('after_switch_theme', 'custom_setup_database_tables');
add_action('admin_init', 'custom_setup_database_tables');
