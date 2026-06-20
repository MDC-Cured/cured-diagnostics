<?php
/**
 * Unified dashboard for Cured Hosting Diagnostics.
 * Provides overview, diagnostics, and safe permission repair controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cured_Hosting_Admin_Dashboard {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_dashboard_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_dashboard_actions' ) );
    }

    public static function register_dashboard_page() {
        add_menu_page(
            'Cured Hosting Diagnostics',
            'Cured Hosting',
            'manage_options',
            'cured-hosting-dashboard',
            array( __CLASS__, 'render_dashboard_page' ),
            'dashicons-admin-tools',
            58
        );

        add_submenu_page(
            'cured-hosting-dashboard',
            'Diagnostics Overview',
            'Overview',
            'manage_options',
            'cured-hosting-dashboard',
            array( __CLASS__, 'render_dashboard_page' )
        );
    }

    public static function handle_dashboard_actions() {
        if ( ! isset( $_GET['page'] ) || 'cured-hosting-dashboard' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
            return;
        }

        if ( isset( $_POST['pd_fix_permissions'] ) ) {
            check_admin_referer( 'pd_fix_permissions_nonce', 'pd_fix_permissions_nonce' );
            self::fix_permission_targets();
            wp_safe_redirect( add_query_arg( 'permissions_fixed', '1', admin_url( 'admin.php?page=cured-hosting-dashboard' ) ) );
            exit;
        }

        if ( isset( $_POST['pd_run_recovery'] ) ) {
            check_admin_referer( 'pd_recovery_nonce', 'pd_recovery_nonce' );
            self::run_recovery_actions();
            wp_safe_redirect( add_query_arg( 'recovery_done', '1', admin_url( 'admin.php?page=cured-hosting-dashboard' ) ) );
            exit;
        }
    }

    public static function render_dashboard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $permission_scan = self::scan_permission_targets();
        $memory          = class_exists( 'PlagueDr_Diagnostics' ) ? PlagueDr_Diagnostics::get_memory_telemetry() : array();
        $is_pro          = class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active();
        $status          = $is_pro ? 'PRO ACTIVE' : 'STANDARD';
        $status_class    = $is_pro ? 'notice-success' : 'notice-warning';
        $issues          = array_filter( $permission_scan, function( $item ) {
            return isset( $item['status'] ) && 'warning' === $item['status'];
        } );
        $module_status   = array(
            'Diagnostics' => class_exists( 'PlagueDr_Diagnostics' ) ? 'Enabled' : 'Missing',
            'SEO'         => class_exists( 'PlagueDr_SEO' ) ? 'Enabled' : 'Missing',
            'Media'       => class_exists( 'PlagueDr_Media_Pipeline' ) ? 'Enabled' : 'Missing',
            'Banner'      => class_exists( 'PlagueDr_Banner_Affiliate' ) ? 'Enabled' : 'Missing',
        );
        $logo_url        = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/brand-mark.png';
        if ( ! file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/images/brand-mark.png' ) ) {
            $logo_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/brand-mark.png';
        }
        ?>
        <div class="wrap">
            <div style="display:flex; align-items:center; gap:16px; background:#fff; padding:18px; border:1px solid #dcdcde; border-radius:8px; margin-bottom:18px;">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Cured Hosting logo" style="width:72px; height:72px; object-fit:contain;" />
                <div>
                    <h1 style="margin:0;">Cured Hosting Diagnostics</h1>
                    <p style="margin:4px 0 0; color:#50575e;">Server health, diagnostics, and safe recovery controls</p>
                </div>
                <span class="notice-inline <?php echo esc_attr( $status_class ); ?>" style="margin-left:auto; padding:6px 10px; border-radius:999px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $status ); ?></span>
            </div>
            <?php if ( isset( $_GET['permissions_fixed'] ) ) : ?>
                <div class="notice notice-success"><p>Permission checks have been re-run and safe defaults were applied where possible.</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['recovery_done'] ) ) : ?>
                <div class="notice notice-success"><p>Recovery actions completed successfully.</p></div>
            <?php endif; ?>

            <div class="dashboard-widgets-wrap">
                <div class="metabox-holder" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-top:16px;">
                    <div class="postbox">
                        <h2 class="hndle"><span>License Status</span></h2>
                        <div class="inside">
                            <p><strong><?php echo esc_html( $status ); ?></strong></p>
                            <p><?php echo esc_html( $is_pro ? 'Premium features are enabled.' : 'Premium features remain locked until a valid token is entered.' ); ?></p>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span>PHP Memory</span></h2>
                        <div class="inside">
                            <p><strong><?php echo esc_html( $memory['usage'] ?? 'n/a' ); ?></strong></p>
                            <p>Limit: <?php echo esc_html( $memory['limit'] ?? 'n/a' ); ?></p>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span>Permission Issues</span></h2>
                        <div class="inside">
                            <p><strong><?php echo count( $issues ); ?></strong> item(s) need attention</p>
                            <p>Check the table below for detailed results.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="postbox" style="margin-top:18px;">
                <h2 class="hndle"><span>Module Status</span></h2>
                <div class="inside">
                    <table class="widefat striped">
                        <tbody>
                            <?php foreach ( $module_status as $name => $state ) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html( $name ); ?></th>
                                    <td><?php echo esc_html( $state ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox" style="margin-top:18px;">
                <h2 class="hndle"><span>Server Diagnostics</span></h2>
                <div class="inside">
                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <th scope="row">PHP Version</th>
                                <td><?php echo esc_html( PHP_VERSION ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">WordPress Version</th>
                                <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Upload Max Filesize</th>
                                <td><?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Post Max Size</th>
                                <td><?php echo esc_html( ini_get( 'post_max_size' ) ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Max Execution Time</th>
                                <td><?php echo esc_html( ini_get( 'max_execution_time' ) . 's' ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox" style="margin-top:18px;">
                <h2 class="hndle"><span>Recovery Tools</span></h2>
                <div class="inside">
                    <form method="post">
                        <?php wp_nonce_field( 'pd_recovery_nonce', 'pd_recovery_nonce' ); ?>
                        <p><button type="submit" name="pd_run_recovery" class="button button-primary">Run Recovery Actions</button></p>
                    </form>
                    <ul>
                        <li>Clear stale plugin transients</li>
                        <li>Re-scan directory permissions</li>
                        <li>Refresh diagnostics snapshot data</li>
                    </ul>
                </div>
            </div>

            <div class="postbox" style="margin-top:18px;">
                <h2 class="hndle"><span>Permission Checks</span></h2>
                <div class="inside">
                    <form method="post">
                        <?php wp_nonce_field( 'pd_fix_permissions_nonce', 'pd_fix_permissions_nonce' ); ?>
                        <p><button type="submit" name="pd_fix_permissions" class="button button-primary">Run Safe Permission Repair</button></p>
                    </form>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Path</th>
                                <th>Type</th>
                                <th>Mode</th>
                                <th>Writable</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $permission_scan as $label => $entry ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $label ); ?></td>
                                    <td><?php echo esc_html( $entry['type'] ); ?></td>
                                    <td><?php echo esc_html( $entry['mode'] ); ?></td>
                                    <td><?php echo $entry['writable'] ? '<span style="color:#1d7a1d;">Yes</span>' : '<span style="color:#b32d2e;">No</span>'; ?></td>
                                    <td>
                                        <span class="dashicons dashicons-<?php echo 'warning' === $entry['status'] ? 'warning' : 'yes'; ?>" aria-hidden="true"></span>
                                        <?php echo esc_html( 'warning' === $entry['status'] ? 'Needs attention' : 'Healthy' ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox" style="margin-top:18px;">
                <h2 class="hndle"><span>Quick Actions</span></h2>
                <div class="inside">
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=plaguedr-license' ) ); ?>" class="button button-secondary">Open License & Cleanup Panel</a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cured-hosting-dashboard' ) ); ?>" class="button button-secondary">Refresh Dashboard</a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    public static function scan_permission_targets() {
        $paths = self::get_permission_targets();
        $scan = array();

        foreach ( $paths as $label => $path ) {
            if ( ! file_exists( $path ) ) {
                $scan[ $label ] = array(
                    'type'     => 'missing',
                    'mode'     => 'n/a',
                    'writable' => false,
                    'status'   => 'warning',
                );
                continue;
            }

            $type = is_dir( $path ) ? 'directory' : 'file';
            $mode = function_exists( 'fileperms' ) ? substr( sprintf( '%o', fileperms( $path ) ), -4 ) : 'n/a';
            $writable = is_writable( $path );
            $status = ( $writable || ( 'directory' === $type && ( '755' === $mode || '775' === $mode || '777' === $mode ) ) ) ? 'healthy' : 'warning';

            $scan[ $label ] = array(
                'type'     => $type,
                'mode'     => $mode,
                'writable' => $writable,
                'status'   => $status,
            );
        }

        return $scan;
    }

    public static function fix_permission_targets() {
        $targets = self::get_permission_targets();

        foreach ( $targets as $label => $path ) {
            if ( ! file_exists( $path ) ) {
                continue;
            }

            if ( is_dir( $path ) ) {
                $target_mode = 0755;
                if ( strpos( $path, '/uploads' ) !== false ) {
                    $target_mode = 0775;
                }
                @chmod( $path, $target_mode );
            } elseif ( is_file( $path ) ) {
                @chmod( $path, 0644 );
            }
        }
    }

    public static function run_recovery_actions() {
        if ( class_exists( 'PlagueDr_Diagnostics' ) ) {
            PlagueDr_Diagnostics::audit_preflight_snapshot();
        }

        delete_transient( 'pd_one_time_owner_token' );
        delete_transient( 'pd_elite_proxy_pool' );
        self::fix_permission_targets();
        update_option( 'cured_hosting_last_recovery_run', current_time( 'mysql' ) );
    }

    private static function get_permission_targets() {
        return array(
            'Plugin Root' => CHD_PATH,
            'WordPress Content' => WP_CONTENT_DIR,
            'Uploads Folder' => WP_CONTENT_DIR . '/uploads',
            'Plugins Folder' => WP_PLUGIN_DIR,
        );
    }
}

Cured_Hosting_Admin_Dashboard::init();
