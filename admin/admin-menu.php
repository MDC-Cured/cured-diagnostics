<?php
// Register the menu under 'Settings'
add_action('admin_menu', 'cured_hosting_register_admin_page');
function cured_hosting_register_admin_page() {
    add_options_page(
        'Cured Hosting', 'Cured Hosting', 'manage_options', 
        'cured-hosting-settings', 'cured_hosting_render_settings_page'
    );
}

// Render the Card-based UI
function cured_hosting_render_settings_page() {
    $modules = [
        ['title' => 'PlagueDr Diagnostics', 'desc' => 'Core runtime safety engine.', 'icon' => 'shield-alt'],
        ['title' => 'Media Pipeline', 'desc' => 'Optimized media delivery hooks.', 'icon' => 'format-image'],
        ['title' => 'Sentinel', 'desc' => 'Security and attack monitoring.', 'icon' => 'lock'],
        ['title' => 'PlagueDr SEO', 'desc' => 'Metadata and schema injection.', 'icon' => 'search']
    ];
    ?>
    <div class="wrap">
        <h1>Cured Hosting Diagnostics <span style="font-size: 14px; font-weight: normal;">v7.0</span></h1>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($modules as $mod) { ?>
                <div class="card" style="padding: 15px; border-left: 4px solid #2271b1;">
                    <h3><span class="dashicons dashicons-<?php echo $mod['icon']; ?>"></span> <?php echo $mod['title']; ?></h3>
                    <p><?php echo $mod['desc']; ?></p>
                    <button class="button button-secondary">Configure</button>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php
}