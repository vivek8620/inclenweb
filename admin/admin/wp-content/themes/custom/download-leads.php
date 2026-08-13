<?php
// Register menu for Download Leads (perhaps under a new menu or under Tools)
add_action('admin_menu', function () {
    add_menu_page(
        'PDF Download Leads',
        'PDF Leads',
        'manage_options',
        'download-leads',
        'download_leads_page',
        'dashicons-download',
        30
    );
});

// REST API for frontend to submit the form and trigger the email
add_action('rest_api_init', function () {
    register_rest_route('downloads/v1', '/request', [
        'methods'  => 'POST',
        'callback' => 'handle_pdf_download_request',
        'permission_callback' => '__return_true' // Open to public
    ]);

    // Endpoint for admin to fetch leads
    register_rest_route('downloads/v1', '/leads', [
        'methods'  => 'GET',
        'callback' => 'get_all_download_leads',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

function handle_pdf_download_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'download_leads';
    $params = json_decode($request->get_body(), true);

    $full_name      = sanitize_text_field($params['full_name'] ?? '');
    $email          = sanitize_email($params['email'] ?? '');
    $location       = sanitize_text_field($params['location'] ?? '');
    $speciality     = sanitize_text_field($params['speciality'] ?? '');
    $pdf_url        = esc_url_raw($params['pdf_url'] ?? '');
    $source_section = sanitize_text_field($params['source_section'] ?? 'Website');
    $pdf_name       = sanitize_text_field($params['pdf_name'] ?? 'Requested Document');

    if (empty($full_name) || empty($email) || empty($pdf_url)) {
        return new WP_Error('missing_fields', 'Name, email, and PDF URL are required', ['status' => 400]);
    }

    // 1. Save Lead to Database
    $wpdb->insert($table, [
        'full_name'      => $full_name,
        'email'          => $email,
        'location'       => $location,
        'speciality'     => $speciality,
        'pdf_url'        => $pdf_url,
        'source_section' => $source_section
    ]);

    // 2. Send the Email
    $subject = "Your Requested PDF Download: $pdf_name";
    
    // Create an HTML email template
    $message = "
    <html>
    <head>
      <title>Your PDF Download</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
      <h2>Hello $full_name,</h2>
      <p>Thank you for your interest in our research materials.</p>
      <p>You recently requested to download <strong>$pdf_name</strong> from the INCLEN $source_section section.</p>
      <p>You can access your document using the link below:</p>
      <p style='margin: 20px 0;'>
        <a href='$pdf_url' style='background-color: #f26522; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>
          Download PDF
        </a>
      </p>
      <p>Or copy and paste this URL into your browser:<br>
      <a href='$pdf_url'>$pdf_url</a></p>
      <br>
      <p>Best regards,<br><strong>The INCLEN Trust International</strong></p>
    </body>
    </html>
    ";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: INCLEN Trust International <noreply@inclen.org>'
    ];

    $email_sent = wp_mail($email, $subject, $message, $headers);

    if ($email_sent) {
        return rest_ensure_response(['status' => 'success', 'message' => 'Email sent successfully']);
    } else {
        // Even if email fails (e.g. no SMTP config), we still logged the lead.
        // But we return an error so the frontend can notify the user.
        return new WP_Error('email_failed', 'Failed to send email. Please ensure SMTP is configured on the server.', ['status' => 500]);
    }
}

function get_all_download_leads() {
    global $wpdb;
    $table = $wpdb->prefix . 'download_leads';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function download_leads_page() { ?>
    <div class="wrap">
        <h1>PDF Download Leads</h1>
        <p>This table displays users who have requested PDF downloads via the frontend modal.</p>
        
        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Location</th>
                        <th>Speciality</th>
                        <th>Section</th>
                        <th>PDF Requested</th>
                    </tr>
                </thead>
                <tbody id="leadsList">
                    <tr><td colspan="7">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch("<?php echo site_url('/wp-json/downloads/v1/leads'); ?>")
        .then(res => res.json())
        .then(data => {
            let html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="7">No leads found.</td></tr>';
            } else {
                data.forEach(item => {
                    let dateStr = new Date(item.created_at).toLocaleString();
                    html += `
                    <tr>
                        <td>${dateStr}</td>
                        <td><strong>${item.full_name}</strong></td>
                        <td><a href="mailto:${item.email}">${item.email}</a></td>
                        <td>${item.location}</td>
                        <td>${item.speciality}</td>
                        <td>${item.source_section}</td>
                        <td><a href="${item.pdf_url}" target="_blank">View PDF</a></td>
                    </tr>`;
                });
            }
            document.getElementById("leadsList").innerHTML = html;
        });
    });
    </script>
<?php }
