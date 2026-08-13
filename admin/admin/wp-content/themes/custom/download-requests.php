<?php
/**
 * Download Requests Manager
 */

/*
add_action('admin_menu', function () {
    add_submenu_page(
        'group-research',
        'Download Requests',
        'Download Requests',
        'manage_options',
        'download-requests',
        'download_requests_page'
    );
});
*/

add_action('rest_api_init', function () {
    register_rest_route('download-requests/v1', '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_download_requests',
        'permission_callback' => 'is_user_admin_check'
    ]);

    register_rest_route('download-requests/v1', '/submit', [
        'methods' => 'POST',
        'callback' => 'submit_download_request',
        'permission_callback' => '__return_true'
    ]);
});

if (!function_exists('is_user_admin_check')) {
    function is_user_admin_check() {
        return current_user_can('manage_options');
    }
}

function get_all_download_requests() {
    global $wpdb;
    $table = $wpdb->prefix . 'download_requests';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC") ?: [];
    return [
        'value' => $results,
        'Count' => count($results)
    ];
}

function submit_download_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'download_requests';
    $params = $request->get_json_params();

    if (empty($params['full_name']) || empty($params['email'])) {
        return new WP_Error('missing_fields', 'Name and Email are required.', ['status' => 400]);
    }

    $wpdb->insert($table, [
        'full_name'     => sanitize_text_field($params['full_name']),
        'email'         => sanitize_email($params['email']),
        'location'      => sanitize_text_field($params['location'] ?? ''),
        'speciality'    => sanitize_text_field($params['speciality'] ?? ''),
        'project_title' => sanitize_text_field($params['project_title'] ?? ''),
        'pdf_url'       => esc_url_raw($params['pdf_url'] ?? ''),
        'timestamp'     => current_time('mysql')
    ]);

    // Send Email via SMTP (PHPMailer)
    $mail_sent = false;
    $error_msg = '';

    try {
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, 'INCLEN Team');
        $mail->addAddress($params['email'], $params['full_name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Download Link: " . $params['project_title'];
        
        $body = "<h2>Hello " . htmlspecialchars($params['full_name']) . ",</h2>";
        $body .= "<p>Thank you for your interest in our research. You can download the requested material using the link below:</p>";
        $body .= "<p><a href='" . esc_url($params['pdf_url']) . "' style='padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 5px;'>Download " . htmlspecialchars($params['project_title']) . "</a></p>";
        $body .= "<p>If the button doesn't work, copy and paste this link into your browser:</p>";
        $body .= "<p>" . esc_url($params['pdf_url']) . "</p>";
        $body .= "<br><p>Best Regards,<br><strong>INCLEN Trust International</strong></p>";

        $mail->Body = $body;
        $mail_sent = $mail->send();

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        error_log("SMTP Mail Error: " . $error_msg);
        return new WP_Error('mail_failed', 'Mail Error: ' . $error_msg, ['status' => 500]);
    }

    return ['status' => 'success'];
}

function download_requests_page() {
    ?>
    <style>
        .inclen-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 40px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th {
            text-align: left;
            padding: 15px;
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #f3f4f6;
        }
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 14px;
        }
        .data-table tr:hover { background: #f9fafb; }
        .tag-project {
            display: inline-block;
            padding: 4px 10px;
            background: #eff6ff;
            color: #1e40af;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>

    <div class="wrap">
        <h1>Download Requests Manager</h1>
        <p class="description">Review and manage user requests for resource materials.</p>

        <div class="inclen-card">
            <h2 style="font-size: 18px; margin-bottom: 20px;">Recent Requests</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>User Details</th>
                        <th>Location & Speciality</th>
                        <th>Requested Project</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody id="requests-list">
                    <tr><td colspan="5" style="text-align:center; padding: 40px;">Loading requests...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('download-requests/v1'); ?>';

        function loadRequests() {
            $.get(API_BASE + '/all', function(res) {
                let html = '';
                const data = res.value || [];
                if (data.length === 0) {
                    html = '<tr><td colspan="5" style="text-align:center; padding: 40px;">No requests found.</td></tr>';
                } else {
                    data.forEach(function(r) {
                        html += `
                            <tr>
                                <td><span style="color: #9ca3af; font-weight: 600;">#${r.id}</span></td>
                                <td>
                                    <div style="font-weight: 600; color: #111827;">${r.full_name}</div>
                                    <div style="font-size: 12px; color: #6b7280;">${r.email}</div>
                                </td>
                                <td>
                                    <div>${r.location || 'N/A'}</div>
                                    <div style="font-size: 12px; color: #6b7280;">${r.speciality || 'N/A'}</div>
                                </td>
                                <td>
                                    <span class="tag-project">${r.project_title}</span>
                                    ${r.pdf_url ? `<div style="font-size: 11px; margin-top: 4px;"><a href="${r.pdf_url}" target="_blank" style="color: #3b82f6;">View PDF</a></div>` : ''}
                                </td>
                                <td>
                                    <div style="font-size: 13px;">${new Date(r.timestamp).toLocaleDateString()}</div>
                                    <div style="font-size: 11px; color: #9ca3af;">${new Date(r.timestamp).toLocaleTimeString()}</div>
                                </td>
                            </tr>`;
                    });
                }
                $('#requests-list').html(html);
            });
        }
        loadRequests();
    });
    </script>
    <?php
}
