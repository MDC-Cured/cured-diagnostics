<?php
/**
 * PlagueDr Banner & Affiliate Manager
 * Adds lightweight banner and affiliate shortcodes plus settings controls.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Banner_Affiliate {

    public static function init() {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        }

        add_shortcode( 'plaguedr_banner', array( __CLASS__, 'render_banner_shortcode' ) );
        add_shortcode( 'plaguedr_affiliate', array( __CLASS__, 'render_affiliate_shortcode' ) );
    }

    public static function register_admin_menu() {
        add_options_page(
            'Banner & Affiliate Manager',
            'Banner Manager',
            'manage_options',
            'plaguedr-banner-manager',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function register_settings() {
        register_setting(
            'plaguedr_banner_affiliate_group',
            'plaguedr_banner_campaigns',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( __CLASS__, 'sanitize_campaigns' ),
                'default'           => '',
            )
        );

        register_setting(
            'plaguedr_banner_affiliate_group',
            'plaguedr_affiliate_programs',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( __CLASS__, 'sanitize_affiliates' ),
                'default'           => '',
            )
        );
    }

    public static function sanitize_campaigns( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $lines = array_filter( preg_split('/\r\n|\r|\n/', trim( wp_unslash( $value ) ) ) );
        $sanitized = array();

        foreach ( $lines as $line ) {
            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) >= 3 ) {
                $slug = sanitize_key( $parts[0] );
                $image = esc_url_raw( $parts[1] );
                $target = esc_url_raw( $parts[2] );
                $alt = sanitize_text_field( isset( $parts[3] ) ? $parts[3] : '' );
                if ( $slug && $image && $target ) {
                    $sanitized[] = implode( '|', array( $slug, $image, $target, $alt ) );
                }
            }
        }

        return implode( "\n", $sanitized );
    }

    public static function sanitize_affiliates( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $lines = array_filter( preg_split('/\r\n|\r|\n/', trim( wp_unslash( $value ) ) ) );
        $sanitized = array();

        foreach ( $lines as $line ) {
            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) >= 3 ) {
                $slug = sanitize_key( $parts[0] );
                $url = esc_url_raw( $parts[1] );
                $label = sanitize_text_field( $parts[2] );
                if ( $slug && $url && $label ) {
                    $sanitized[] = implode( '|', array( $slug, $url, $label ) );
                }
            }
        }

        return implode( "\n", $sanitized );
    }

    public static function render_admin_page() {
        $campaigns = get_option( 'plaguedr_banner_campaigns', '' );
        $affiliates = get_option( 'plaguedr_affiliate_programs', '' );
        ?>
        <div class="wrap">
            <h1>Banner & Affiliate Manager</h1>
            <p>Define one campaign or affiliate per line using the format below.</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'plaguedr_banner_affiliate_group' ); ?>
                <?php do_settings_sections( 'plaguedr_banner_affiliate_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="plaguedr_banner_campaigns">Banner campaigns</label></th>
                        <td>
                            <textarea id="plaguedr_banner_campaigns" name="plaguedr_banner_campaigns" rows="8" cols="80" class="large-text code"><?php echo esc_textarea( $campaigns ); ?></textarea>
                            <p class="description">Format: slug|image_url|target_url|alt_text</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="plaguedr_affiliate_programs">Affiliate programs</label></th>
                        <td>
                            <textarea id="plaguedr_affiliate_programs" name="plaguedr_affiliate_programs" rows="8" cols="80" class="large-text code"><?php echo esc_textarea( $affiliates ); ?></textarea>
                            <p class="description">Format: slug|target_url|button_text</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Banner Settings' ); ?>
            </form>
        </div>
        <?php
    }

    public static function render_banner_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'slug'  => '',
                'class' => 'plaguedr-banner',
            ),
            $atts,
            'plaguedr_banner'
        );

        $campaign = self::get_campaign_by_slug( sanitize_key( $atts['slug'] ) );
        if ( ! $campaign ) {
            return '';
        }

        $class = esc_attr( $atts['class'] );
        return sprintf(
            '<a href="%1$s" class="%2$s" target="_blank" rel="noopener noreferrer"><img src="%3$s" alt="%4$s" loading="lazy" /></a>',
            esc_url( $campaign['target'] ),
            $class,
            esc_url( $campaign['image'] ),
            esc_attr( $campaign['alt'] )
        );
    }

    public static function render_affiliate_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'slug' => '',
                'text' => 'Learn More',
                'class' => 'plaguedr-affiliate-button',
            ),
            $atts,
            'plaguedr_affiliate'
        );

        $program = self::get_affiliate_by_slug( sanitize_key( $atts['slug'] ) );
        if ( ! $program ) {
            return '';
        }

        $label = esc_html( $atts['text'] );
        $class = esc_attr( $atts['class'] );

        return sprintf(
            '<a href="%1$s" class="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
            esc_url( $program['url'] ),
            $class,
            $label
        );
    }

    private static function get_campaign_by_slug( $slug ) {
        foreach ( self::get_campaigns() as $campaign ) {
            if ( isset( $campaign['slug'] ) && $campaign['slug'] === $slug ) {
                return $campaign;
            }
        }

        return false;
    }

    private static function get_affiliate_by_slug( $slug ) {
        foreach ( self::get_affiliates() as $affiliate ) {
            if ( isset( $affiliate['slug'] ) && $affiliate['slug'] === $slug ) {
                return $affiliate;
            }
        }

        return false;
    }

    private static function get_campaigns() {
        $raw = get_option( 'plaguedr_banner_campaigns', '' );
        if ( ! is_string( $raw ) ) {
            return array();
        }

        $campaigns = array();
        foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) >= 3 ) {
                $campaigns[] = array(
                    'slug'   => sanitize_key( $parts[0] ),
                    'image'  => esc_url_raw( $parts[1] ),
                    'target' => esc_url_raw( $parts[2] ),
                    'alt'    => sanitize_text_field( isset( $parts[3] ) ? $parts[3] : '' ),
                );
            }
        }

        return $campaigns;
    }

    private static function get_affiliates() {
        $raw = get_option( 'plaguedr_affiliate_programs', '' );
        if ( ! is_string( $raw ) ) {
            return array();
        }

        $affiliates = array();
        foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) >= 3 ) {
                $affiliates[] = array(
                    'slug' => sanitize_key( $parts[0] ),
                    'url'  => esc_url_raw( $parts[1] ),
                    'text' => sanitize_text_field( $parts[2] ),
                );
            }
        }

        return $affiliates;
    }
}
