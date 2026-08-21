<?php
require_once dirname(__FILE__) . '/wp-load.php';

global $wpdb;
$blogs_table = $wpdb->prefix . 'blogs';

$charts = [
    'mental-health-awareness' => '
<div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h3 style="color: #2d3748; margin-top: 0; text-align: center; font-size: 20px;">Growth of Support Groups (2020-2025)</h3>
    <div style="display: flex; align-items: flex-end; justify-content: center; gap: 20px; height: 200px; padding-top: 20px; border-bottom: 2px solid #cbd5e0; margin-bottom: 10px;">
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 40px; height: 40px; background: #4299e1; border-radius: 4px 4px 0 0; transition: height 1s ease-in-out;"></div>
            <span style="font-size: 12px; color: #4a5568; font-weight: bold;">2021</span>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 40px; height: 80px; background: #4299e1; border-radius: 4px 4px 0 0;"></div>
            <span style="font-size: 12px; color: #4a5568; font-weight: bold;">2022</span>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 40px; height: 110px; background: #3182ce; border-radius: 4px 4px 0 0;"></div>
            <span style="font-size: 12px; color: #4a5568; font-weight: bold;">2023</span>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 40px; height: 160px; background: #2b6cb0; border-radius: 4px 4px 0 0;"></div>
            <span style="font-size: 12px; color: #4a5568; font-weight: bold;">2024</span>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 40px; height: 190px; background: #2c5282; border-radius: 4px 4px 0 0;"></div>
            <span style="font-size: 12px; color: #4a5568; font-weight: bold;">2025</span>
        </div>
    </div>
    <p style="text-align: center; color: #718096; font-size: 14px; margin: 0;">Number of active community counseling centers established.</p>
</div>',
    
    'community-safety-first-aid' => '
<div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center;">
    <h3 style="color: #2d3748; margin-top: 0; font-size: 20px;">Primary Injuries Treated (Distribution)</h3>
    <svg viewBox="0 0 32 32" style="width: 200px; height: 200px; border-radius: 50%; transform: rotate(-90deg); margin: 20px auto; display: block;">
        <circle r="16" cx="16" cy="16" fill="#f56565" stroke-width="32" stroke-dasharray="100 0"></circle>
        <circle r="16" cx="16" cy="16" fill="#ed8936" stroke-width="32" stroke-dasharray="60 40"></circle>
        <circle r="16" cx="16" cy="16" fill="#ecc94b" stroke-width="32" stroke-dasharray="35 65"></circle>
        <circle r="16" cx="16" cy="16" fill="#48bb78" stroke-width="32" stroke-dasharray="10 90"></circle>
    </svg>
    <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-top: 15px;">
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 12px; height: 12px; background: #f56565; border-radius: 2px;"></div><span style="font-size:14px; color:#4a5568;">Burns (40%)</span></div>
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 12px; height: 12px; background: #ed8936; border-radius: 2px;"></div><span style="font-size:14px; color:#4a5568;">Falls/Fractures (25%)</span></div>
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 12px; height: 12px; background: #ecc94b; border-radius: 2px;"></div><span style="font-size:14px; color:#4a5568;">Snakebites (25%)</span></div>
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 12px; height: 12px; background: #48bb78; border-radius: 2px;"></div><span style="font-size:14px; color:#4a5568;">Violence (10%)</span></div>
    </div>
</div>',

    'combating-neonatal-mortality' => '
<div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h3 style="color: #2d3748; margin-top: 0; text-align: center; font-size: 20px;">Decline in Neonatal Mortality Rate (per 1,000 live births)</h3>
    <svg width="100%" height="200" viewBox="0 0 500 200" preserveAspectRatio="none" style="margin-top: 20px;">
        <polyline fill="none" stroke="#e53e3e" stroke-width="4" stroke-linejoin="round"
            points="0,40 100,70 200,100 300,140 400,160 500,180" />
        <circle cx="0" cy="40" r="6" fill="#c53030" />
        <circle cx="100" cy="70" r="6" fill="#c53030" />
        <circle cx="200" cy="100" r="6" fill="#c53030" />
        <circle cx="300" cy="140" r="6" fill="#c53030" />
        <circle cx="400" cy="160" r="6" fill="#c53030" />
        <circle cx="500" cy="180" r="6" fill="#c53030" />
    </svg>
    <div style="display: flex; justify-content: space-between; margin-top: 10px; color: #718096; font-size: 12px; font-weight: bold;">
        <span>2020<br>(32.5)</span>
        <span>2021<br>(29.1)</span>
        <span>2022<br>(25.4)</span>
        <span>2023<br>(19.8)</span>
        <span>2024<br>(16.2)</span>
        <span style="text-align: right;">2025<br>(12.5)</span>
    </div>
</div>',

    'child-nutrition-growth' => '
<div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center;">
    <h3 style="color: #2d3748; margin-top: 0; font-size: 20px;">Nutrition Program Efficacy</h3>
    <div style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; margin-top: 20px; gap: 20px;">
        <div style="text-align: center;">
            <div style="position: relative; width: 120px; height: 120px; margin: 0 auto;">
                <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                    <path style="stroke: #e2e8f0; stroke-width: 3.8; fill: none;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path style="stroke: #38b2ac; stroke-width: 3.8; fill: none; stroke-dasharray: 85, 100;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 24px; font-weight: bold; color: #234e52;">85%</div>
            </div>
            <p style="margin-top: 10px; color: #4a5568; font-weight: 600;">Reduced Anaemia</p>
        </div>
        <div style="text-align: center;">
            <div style="position: relative; width: 120px; height: 120px; margin: 0 auto;">
                <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                    <path style="stroke: #e2e8f0; stroke-width: 3.8; fill: none;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path style="stroke: #d69e2e; stroke-width: 3.8; fill: none; stroke-dasharray: 62, 100;" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 24px; font-weight: bold; color: #744210;">62%</div>
            </div>
            <p style="margin-top: 10px; color: #4a5568; font-weight: 600;">Improved Stunting</p>
        </div>
    </div>
</div>',

    'infectious-disease-surveillance' => '
<div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h3 style="color: #2d3748; margin-top: 0; text-align: center; font-size: 20px;">Surveillance Data Flow Architecture</h3>
    <div style="text-align: center; margin-top: 20px;">
        <svg width="100%" height="180" viewBox="0 0 500 180" preserveAspectRatio="xMidYMid meet">
            <!-- Nodes -->
            <rect x="50" y="70" width="100" height="40" rx="20" fill="#ebf8ff" stroke="#3182ce" stroke-width="2"/>
            <text x="100" y="95" font-family="sans-serif" font-size="12" font-weight="bold" fill="#2b6cb0" text-anchor="middle">Local Clinics</text>

            <rect x="200" y="20" width="100" height="40" rx="20" fill="#ebf8ff" stroke="#3182ce" stroke-width="2"/>
            <text x="250" y="45" font-family="sans-serif" font-size="12" font-weight="bold" fill="#2b6cb0" text-anchor="middle">Field Agents</text>

            <rect x="200" y="120" width="100" height="40" rx="20" fill="#ebf8ff" stroke="#3182ce" stroke-width="2"/>
            <text x="250" y="145" font-family="sans-serif" font-size="12" font-weight="bold" fill="#2b6cb0" text-anchor="middle">Laboratories</text>

            <rect x="350" y="70" width="120" height="40" rx="8" fill="#2b6cb0" stroke="#2a4365" stroke-width="2"/>
            <text x="410" y="95" font-family="sans-serif" font-size="12" font-weight="bold" fill="#ffffff" text-anchor="middle">Central Data Hub</text>

            <!-- Lines -->
            <line x1="150" y1="90" x2="350" y2="90" stroke="#a0aec0" stroke-width="2" stroke-dasharray="4" />
            <line x1="300" y1="40" x2="350" y2="75" stroke="#a0aec0" stroke-width="2" stroke-dasharray="4" />
            <line x1="300" y1="140" x2="350" y2="105" stroke="#a0aec0" stroke-width="2" stroke-dasharray="4" />
            
            <circle cx="345" cy="90" r="4" fill="#a0aec0" />
            <circle cx="345" cy="78" r="4" fill="#a0aec0" />
            <circle cx="345" cy="102" r="4" fill="#a0aec0" />
        </svg>
        <p style="color: #718096; font-size: 13px; margin-top: 10px;">Real-time data aggregation from multiple regional endpoints.</p>
    </div>
</div>'
];

foreach ($charts as $slug => $chart_html) {
    // Get existing content
    $row = $wpdb->get_row($wpdb->prepare("SELECT content FROM $blogs_table WHERE slug = %s", $slug));
    if ($row) {
        $new_content = $row->content . "\n\n" . $chart_html;
        $wpdb->update($blogs_table, ['content' => $new_content], ['slug' => $slug]);
    }
}

echo "Charts added successfully!\n";
