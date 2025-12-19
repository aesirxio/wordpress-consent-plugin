<?php
/**
 * Plugin Name: AesirX Consent
 * Plugin URI: https://analytics.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics
 * Description: Aesirx Consent plugin. When you join forces with AesirX, you're not just becoming a Partner - you're also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/partner?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>
 * Version: 2.0.3
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-consent
 * Requires PHP: 7.4
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
 **/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use AesirxAnalytics\CliFactory;
use AesirxAnalyticsLib\Exception\ExceptionWithResponseCode;
use AesirxAnalytics\Route\Middleware\IsBackendMiddleware;
use AesirxAnalyticsLib\RouterFactory;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Route\RouteUrl;
use AesirxAnalytics\Migrator\MigratorMysql;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';


include_once(ABSPATH . 'wp-admin/includes/plugin.php');

add_action('wp_enqueue_scripts', function (): void {
    $options = get_option('aesirx_analytics_plugin_options');
    $optionsConsentVerify = get_option('aesirx_consent_verify_plugin_options', []);
    $template = get_option('aesirx_consent_modal_plugin_options', []);

    $datastream_template = $template['datastream_template'] ?? '';
    $minimumAge = isset($optionsConsentVerify['minimum_age']) ? (int)$optionsConsentVerify['minimum_age'] : 0;
    $maximumAge = isset($optionsConsentVerify['maximum_age']) ? (int)$optionsConsentVerify['maximum_age'] : 0;
    $ageCheck = (isset($optionsConsentVerify['age_check']) && $optionsConsentVerify['age_check'] === 'ageCheck')
    ? (($minimumAge > 0 || $maximumAge > 0) ? 1 : 0)
    : 0;
    $allowedCountries = $optionsConsentVerify['allowed_countries'] ?? [];
    $disallowedCountries = $optionsConsentVerify['disallowed_countries'] ?? [];
    $countryCheck = (isset($optionsConsentVerify['country_check']) && $optionsConsentVerify['country_check'] === 'countryCheck')
            ? ((!empty($allowedCountries) || !empty($disallowedCountries)) ? 1 : 0)
            : 0;

    if ($ageCheck || $countryCheck) {
        $scriptFile = 'assets/vendor/consent-verify.js';
    } elseif ($datastream_template === 'simple-consent-mode') {
        $scriptFile = 'assets/vendor/consent-simple.js';
    };
    if ($datastream_template !== 'simple-consent-mode') {
        $scriptFile = 'assets/vendor/consent.js';
    }

    wp_register_script('aesirx-consent', plugins_url($scriptFile, __FILE__), [], '2.0.3',  array(
        'in_footer' => false,
    ));
    $translation_array = array(
        'txt_shield_of_privacy' => __( 'Shield of Privacy', 'aesirx-consent' ),
        'txt_you_can_revoke' => __( 'Revoke your consent for data use whenever you wish.', 'aesirx-consent' ),
        'txt_manage_consent' => __( 'Manage Decentralized Consent', 'aesirx-consent' ),
        'txt_revoke_consent' => __( 'Revoke Consent', 'aesirx-consent' ),
        'txt_yes_i_consent' => __( 'Consent', 'aesirx-consent' ),
        'txt_reject_consent' => __( 'Reject', 'aesirx-consent' ),
        'txt_please_connect' => __( 'Please connect your Web3 wallet', 'aesirx-consent' ),
        'txt_please_sign' => __( 'Please sign the message on your wallet twice and wait for it to be saved.', 'aesirx-consent' ),
        'txt_saving' => __( 'Saving...', 'aesirx-consent' ),
        'txt_please_connect_your_wallet' => __( 'Please connect to your wallet', 'aesirx-consent' ),
        'txt_connecting' => __( 'Connecting', 'aesirx-consent' ),
        'txt_tracking_data_privacy' => __( 'TRACKING AND DATA PRIVACY PREFERENCES', 'aesirx-consent' ),
        'txt_about' => __( 'About', 'aesirx-consent' ),
        'txt_detail' => __( 'Details', 'aesirx-consent' ),
        'txt_change_consent' => __( 'Decentralized Consent', 'aesirx-consent' ),
        'txt_manage_your_consent' => __( 'Manage Your Consent Preferences', 'aesirx-consent' ),
        'txt_choose_how_we_use' => __( 'Choose how we use your data: "Reject" data collection, allow tracking ["Consent"], or use "Decentralized Consent" for more control over your personal data.', 'aesirx-consent' ),
        'txt_choose_how_we_use_simple' => __( 'Choose how we use your data: "Reject" data collection, allow tracking ["Consent"].', 'aesirx-consent' ),
        'txt_by_consenting' => __( 'By consenting, you allow us to collect & use your data for:', 'aesirx-consent' ),
        'txt_analytics_behavioral' => __( 'Analytics & Behavioral Data: To improve our services & personalize your experience.', 'aesirx-consent' ),
        'txt_form_data' => __( 'Form Data: When you contact us.', 'aesirx-consent' ),
        'txt_please_note' => __( 'Please note', 'aesirx-consent' ),
        'txt_we_do_not_share' => __( 'We do not share your data with third parties without your explicit consent.', 'aesirx-consent' ),
        'txt_you_can_opt_in' => __( 'You can opt-in later for specific features without giving blanket consent.', 'aesirx-consent' ),
        'txt_for_more_details' => __( "For more details, refer to our <a class='text-success fw-semibold text-decoration-underline' href='https://aesirx.io/privacy-policy' target='_blank'>privacy policy.</a>", 'aesirx-consent' ),
        'txt_benefit' => __( 'Benefits', 'aesirx-consent' ),
        'txt_control_your_data' => __( "<span class='fw-semibold text-primary'>Control your data:</span> Choose your preferred level of data collection & tracking.", 'aesirx-consent' ),
        'txt_earn_rewards' => __( "<span class='fw-semibold text-primary'>Earn rewards:</span> Participate in decentralized consent for privacy & rewards.", 'aesirx-consent' ),
        'txt_transparent_data' => __( "<span class='fw-semibold text-primary'>Transparent data collection practices:</span> Understand how your data is collected & used.", 'aesirx-consent' ),
        'txt_understanding_your_privacy' => __( "Understanding Your Privacy Choices", 'aesirx-consent' ),
        'txt_reject_no_data' => __( "<span class='fw-semibold text-primary'>Reject:</span> No data will be collected or loaded except for anonymized page views & rejections. Some personalization features may be disabled.", 'aesirx-consent' ),
        'txt_consent_first_third_party' => __( "<span class='fw-semibold text-primary'>Consent:</span> First & third-party tracking data will be collected to enhance your experience.", 'aesirx-consent' ),
        'txt_decentralizered_consent_choose' => __( "<span class='fw-semibold text-primary'>Decentralized Consent:</span> Choose Decentralized Wallets or Decentralized Wallet + Shield of Privacy. Both options let you manage & revoke consent on-site or through AesirX dApp, plus earn rewards from digital marketing activities.", 'aesirx-consent' ),
        'txt_our_commitment_in_action' => __( "Our Commitment in Action", 'aesirx-consent' ),
        'txt_private_protection' => __( "<span class='fw-semibold text-primary'>Privacy Protection:</span> Users have full control over their data, ensuring maximum privacy.", 'aesirx-consent' ),
        'txt_enables_compliance' => __( "<span class='fw-semibold text-primary'>Enables Compliance:</span> Using Shield of Privacy (SoP) ensures compliance with GDPR, CCPA, ePrivacy Directive, & other data protection regulations.", 'aesirx-consent' ),
        'txt_proactive_protection' => __( "<span class='fw-semibold text-primary'>Proactive Protection:</span> We enhance privacy measures to safeguard your data integrity.", 'aesirx-consent' ),
        'txt_flexible_consent' => __( "<span class='fw-semibold text-primary'>Flexible Consent:</span> You can withdraw your consent anytime on-site or via our <a class='text-success fw-semibold text-decoration-underline' href='https://dapp.shield.aesirx.io' target='_blank'>dApp</a> (Decentralized Application).", 'aesirx-consent' ),
        'txt_learn_more' => __( "<span class='fw-semibold text-primary'>Learn More:</span> Discover our approach to data processing in our <a class='text-success fw-semibold text-decoration-underline' href='https://aesirx.io/privacy-policy' target='_blank'>Privacy Policy</a>.", 'aesirx-consent' ),
        'txt_for_business' => __( "<span class='fw-semibold text-primary'>For Businesses:</span> Enhance trust, secure user identities, & prevent breaches.", 'aesirx-consent' ),
        'txt_more_info_at' => __( "More info at <a class='text-success fw-semibold text-decoration-underline' href='https://shield.aesirx.io' target='_blank'>https://shield.aesirx.io</a>.", 'aesirx-consent' ),
        'txt_select_your_preferred' => __( "Select your preferred decentralized consent option:", 'aesirx-consent' ),
        'txt_decentralized_wallet' => __( "Decentralized Consent", 'aesirx-consent' ),
        'txt_decentralized_wallet_will_be_loaded' => __( "Decentralized consent will be loaded", 'aesirx-consent' ),
        'txt_both_first_party_third_party' => __( "Both first-party & third-party tracking data will be activated.", 'aesirx-consent' ),
        'txt_all_consented_data_will_be_collected' => __( "All consented data will be collected.", 'aesirx-consent' ),
        'txt_users_can_revoke' => __( "Users can revoke consent on-site at any time.", 'aesirx-consent' ),
        'txt_decentralized_wallet_shield' => __( "Decentralized Consent + Shield of Privacy", 'aesirx-consent' ),
        'txt_users_can_revoke_dapp' => __( "Users can revoke consent on-site or from the AesirX dApp at any time.", 'aesirx-consent' ),
        'txt_users_can_earn' => __( "Users can earn rewards from digital marketing activities.", 'aesirx-consent' ),
        'txt_continue' => __( "Continue", 'aesirx-consent' ),
        'txt_back' => __( "Back", 'aesirx-consent' ),
        'txt_you_have_chosen' => __( "You've chosen to reject data collection:", 'aesirx-consent' ),
        'txt_only_anonymized' => __( "Only anonymized page views & limited features will be available. To access all website features, including personalized content & enhanced functionality, please choose an option:", 'aesirx-consent' ),
        'txt_consent_allow_data' => __( "<span class='fw-semibold text-primary'>Consent:</span> Allow data collection for analytics, form data (when you contact us), & behavioral & event tracking, with the option to opt-in for specific features.", 'aesirx-consent' ),
        'txt_decentralized_consent_allow_data' => __( "<span class='fw-semibold text-primary'>Decentralized Consent:</span> Allow data collection for analytics, form data (when you contact us), & behavioral & event tracking, with the option to revoke consent, opt-in for specific features, & earn rewards from digital marketing activities.", 'aesirx-consent' ),
        'txt_you_can_revoke_on_the_site' => __( "You can revoke consent on the site or any explicit opt-in consent, such as payment processing, at any time", 'aesirx-consent' ),
        'txt_revoke_opt_in' => __( "Revoke Opt-In Consent", 'aesirx-consent' ),
        'txt_revoke_opt_in_payment' => __( "Revoke Opt-In Consent for Payment Processing", 'aesirx-consent' ),
        'txt_revoke_opt_in_advisor' => __( "Revoke Opt-In Consent for AesirX Privacy Advisor AI", 'aesirx-consent' ),
        'txt_revoke_consent_for_the_site' => __( "Revoke Consent for the site", 'aesirx-consent' ),
        'txt_consent_nanagement' => __( "Consent Management", 'aesirx-consent' ),
        'txt_details' => __( "Details", 'aesirx-consent' ),
        "txt_customize" => __( "Customize", 'aesirx-consent' ),
        "txt_save" => __( "Save", 'aesirx-consent' ),
        "txt_always_active" => __( "Always active", 'aesirx-consent' ),
        "txt_domain_path_based" => __( "Domain/Path-Based", 'aesirx-consent' ),
        "txt_third_party_plugins" => __( "Third-Party Plugins", 'aesirx-consent' ),
        "txt_essential_tracking" =>  __( "Essential Tracking", 'aesirx-consent' ),
        "txt_essential_tracking_desc" => __( "Required for the website to function (e.g, session cookies, security tracking).", 'aesirx-consent' ),
        "txt_functional_tracking" => __( "Functional Tracking", 'aesirx-consent' ),
        "txt_functional_tracking_desc" => __( "User preferencese & site enhancements (e.g, language setting, live chat).", 'aesirx-consent' ),
        "txt_analytics_tracking" => __( "Analytics Tracking", 'aesirx-consent' ),
        "txt_analytics_tracking_desc" => __( "Visitor behavior monitoring (e.g, Google Analytics, Matomo).", 'aesirx-consent' ),
        "txt_advertising_tracking" => __( "Advertising Tracking", 'aesirx-consent' ),
        "txt_advertising_tracking_desc" => __( "Targeted advertising & remarketing (e.g, Facebook Pixel, Google Ads).", 'aesirx-consent' ),
        "txt_custom_tracking" => __( "Custom Tracking", 'aesirx-consent' ),
        "txt_custom_tracking_desc" => __( "Any additional third-party integrations (e.g, customer support tools, CDNS).", 'aesirx-consent' ),
        "txt_opt_out_tracking" => __( "Opt-Out of tracking", 'aesirx-consent' ),
        "txt_tracking_default" => __( "This website uses tracking by default. You may opt out at any time.", 'aesirx-consent' ),
        "txt_do_not_sell" => __( "Do Not Sell or Share My Personal Information (CCPA)", 'aesirx-consent' ),
        "txt_disables_third_party" => __( "Disables third-party data sharing for California users.", 'aesirx-consent' ),
        "txt_cookie_declaration" => __( "Cookie Declaration", 'aesirx-consent' )
    );
    wp_localize_script( 'aesirx-consent', 'aesirx_analytics_translate', $translation_array );
    wp_enqueue_script('aesirx-consent');

    $domain =
        ($options['storage'] ?? 'internal') === 'internal'
            ? get_bloginfo('url')
            : rtrim($options['domain'] ?? '', '/');

    $trackEcommerce = ($options['track_ecommerce'] ?? 'true') === 'true' ? 'true': 'false';
    $blockingCookiesPath =  isset($options['blocking_cookies']) 
        ? array_filter($options['blocking_cookies'], fn($v) => trim($v) !== '') 
        : [];
    $blockingCookiesCategory = isset($options['blocking_cookies_category']) && count($options['blocking_cookies_category']) > 0 ? $options['blocking_cookies_category'] : [];
    $blockingCookiesPermanent =  isset($options['blocking_cookies_permanent']) &&  count($options['blocking_cookies_permanent']) > 0 ? $options['blocking_cookies_permanent'] : [];
    $arrayCookiesPlugins =  isset($options['blocking_cookies_plugins']) &&  count($options['blocking_cookies_plugins']) > 0 ? $options['blocking_cookies_plugins'] : [];
    $arrayCookiesPluginsCategory =  isset($options['blocking_cookies_plugins_category']) &&  count($options['blocking_cookies_plugins_category']) > 0 ? $options['blocking_cookies_plugins_category'] : [];
    $prefix = "wp-content/plugins/";
    $blockingCookiesPlugins = isset($options['blocking_cookies_plugins']) && count($options['blocking_cookies_plugins']) > 0
        ? array_map(function($value) use ($prefix) {
            return $prefix . $value;
        }, array_filter($arrayCookiesPlugins, fn($v) => trim($v) !== ''))
        : [];

    $blockingCookiesPluginsCategory = [];
    $blockingCookiesPluginsName = [];
    
    foreach ($arrayCookiesPluginsCategory as $slug => $pluginData) {
        foreach ($pluginData as $pluginName => $category) {
            $blockingCookiesPluginsCategory[$prefix . $slug] = $category;
            $blockingCookiesPluginsName[$prefix . $slug] = $pluginName;
        }
    }

    $blockingCookies = array_unique(array_merge($blockingCookiesPath, $blockingCookiesPlugins), SORT_REGULAR);
    
    $blockingCookiesObjects = array_map(function ($cookie, $key) use ($blockingCookiesCategory, $blockingCookiesPermanent, $blockingCookiesPluginsCategory, $blockingCookiesPluginsName) {
        return [
            'domain' => $cookie,
            'category' => $blockingCookiesCategory[$key] ?? ($blockingCookiesPluginsCategory[$cookie] ?? 'custom'),
            'blocking_permanent' => $blockingCookiesPermanent[$key] ?? 'off',
            'name' => $blockingCookiesPluginsName[$cookie] ?? '',
        ];
    }, array_values($blockingCookies), array_keys(array_values($blockingCookies)));
    
    $blockingCookiesJSON = (isset($options['blocking_cookies']) && count($options['blocking_cookies']) > 0  || isset($options['blocking_cookies_plugins']) && count($options['blocking_cookies_plugins']) > 0 )
        ? wp_json_encode($blockingCookiesObjects)
        : '[]';

    $clientId = $options['clientid'] ?? '';
    $secret = $options['secret'] ?? '';
    $optionsGPC = get_option('aesirx_consent_gpc_plugin_options', []);
    $optionsGEO = get_option('aesirx_consent_geo_plugin_options', []);
    $disableGPCSupport  = (isset($optionsGPC['gpc_support']) && $optionsGPC['gpc_support'] === 'no') ? "true" : "false";
    $configConsentGPC   = (isset($optionsGPC['gpc_consent']) && $optionsGPC['gpc_consent'] === 'opt-out') ? "true" : "false";
    $configConsentGPCDoNotSell = (isset($optionsGPC['gpc_consent_donotsell']) && $optionsGPC['gpc_consent_donotsell'] === 'yes') ? "true" : "false";
    $configGeoHandling         = (isset($optionsGEO['geo_handling']) && $optionsGEO['geo_handling'] === 'yes');

    function transformGeoOptions(array $optionsGEO): array {
        $keys = [
            'geo_rules_language',
            'geo_rules_timezone',
            'geo_rules_logic',
            'geo_rules_consent_mode',
            'geo_rules_override',
        ];
        $count = count($optionsGEO['geo_rules_language'] ?? []);
    
        $result = [];
    
        for ($i = 0; $i < $count; $i++) {
            $item = [];
            foreach ($keys as $key) {
                $item[$key] = $optionsGEO[$key][$i] ?? null;
            }
            $result[] = $item;
        }
        return $result;
    }
    $geoRules =  $configGeoHandling ? transformGeoOptions($optionsGEO) : null;

            
    wp_add_inline_script(
        'aesirx-consent',
        'window.aesirx1stparty="' . esc_attr($domain) . '";
        window.aesirxClientID="' . esc_attr($clientId) . '";
        window.aesirxClientSecret="' . esc_attr($secret) . '";
        window.disableGPCsupport="' . esc_attr($disableGPCSupport) . '";
        window.aesirxBlockJSDomains=' . $blockingCookiesJSON . ';
        window.aesirxTrackEcommerce="' . esc_attr($trackEcommerce) . '";
        window.aesirxOptOutMode="' . esc_attr($configConsentGPC) . '";
        window.aesirxOptOutDoNotSell="' . esc_attr($configConsentGPCDoNotSell) . '";
        window.geoRules=' . wp_json_encode($geoRules) . ';
        window.web3Endpoint="https://web3id.backend.aesirx.io:8001";
        window.ageCheck='.$ageCheck.';
        window.minimumAge='.$minimumAge.';
        window.maximumAge='.$maximumAge.';
        window.countryCheck='.$countryCheck.';
        window.allowedCountries='.wp_json_encode($allowedCountries).';
        window.disallowedCountries='.wp_json_encode($disallowedCountries).';'
        ,
        'before');
});

add_action('plugins_loaded', function () {
  load_plugin_textdomain(
    'aesirx-consent',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages/'
  );
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  $url = esc_url(add_query_arg('page', 'aesirx-consent-management-plugin', get_admin_url() . 'admin.php'));
  array_push($links, "<a href='$url'>" . esc_html__('Settings', 'aesirx-consent') . '</a>');
  return $links;
});

add_action( 'parse_request', 'aesirx_analytics_url_handler' );


function aesirx_analytics_url_handler()
{
    $options = get_option('aesirx_analytics_plugin_options');

    if (($options['storage'] ?? 'internal') !== 'internal') {
        return;
    }

    $callCommand = function (array $command): string {
        try
        {
            $data = CliFactory::getCli()->processAnalytics($command);
        }
        catch (Exception $e)
        {
            $data = wp_json_encode([
                'error' => $e->getMessage()
            ]);
        }

        if (!headers_sent()) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        return $data;
    };

    try {
        $router = (new RouterFactory(
            $callCommand,
            new IsBackendMiddleware(),
            null,
            site_url( '', 'relative' ))
        )
            ->getSimpleRouter();

        $router->addRoute(
            (new RouteUrl('/remember_flow/{flow}', static function (string $flow): string {

                set_transient('analytics_flow_uuid', $flow, HOUR_IN_SECONDS);

                return wp_json_encode(true);
            }))
                ->setWhere(['flow' => '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}'])
                ->setRequestMethods([Request::REQUEST_TYPE_POST])
        );

        echo wp_kses_post($router->start());
    } catch (Throwable $e) {
        if ($e instanceof NotFoundHttpException) {
        return;
        }

        if ($e instanceof ExceptionWithResponseCode) {
            $code = $e->getResponseCode();
        } else {
            $code = 500;
        }

        if (!headers_sent()) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        http_response_code($code);
        echo wp_json_encode([
            'error' => $e->getMessage(),
        ]);
    }

    die();
}

register_activation_hook(__FILE__, 'aesirx_analytics_initialize_function');
function aesirx_analytics_initialize_function($manually = false) {
    global $wpdb;

    //Add migration table
    MigratorMysql::aesirx_analytics_create_migrator_table_query();
    $migration_list = array_column(MigratorMysql::aesirx_analytics_fetch_rows(), 'name');

    $files = glob(plugin_dir_path( __FILE__ ) . 'src/Migration/*.php');
    foreach ($files as $file) {
        $realpath = realpath($file);
        if ($realpath && strpos($realpath, plugin_dir_path(__FILE__) . 'src/Migration/') === 0) {
            include_once $realpath; // Safe inclusion
            $file_name = basename($realpath, ".php");
            if (!in_array($file_name, $migration_list, true) || $manually) {
                MigratorMysql::aesirx_analytics_add_migration_query($file_name);
                $sql = $sql ?? []; // Ensure $sql is an array
                foreach ($sql as $each_query) {
                    // Used placeholders and $wpdb->prepare() in variable $each_query
                    // Need $wpdb->query() for ALTER TABLE
                    $wpdb->query($each_query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                }
            }
        }
    }
    add_option('aesirx_analytics_do_activation_redirect', true);
}

function aesirx_analytics_display_update_notice(  ) {
    $notice = get_transient( 'aesirx_analytics_update_notice' );
    if( $notice ) {

        $notice = json_decode($notice, true);

        if ($notice instanceof Throwable)
        {
            /* translators: %s: error message */
            // using custom function to escape HTML in error message
            echo wp_kses('<div class="notice notice-error"><p>' . esc_html__('Problem with Aesirx Analytics plugin install', 'aesirx-consent') . '</p></div>', aesirx_analytics_escape_html());
        }

        delete_transient( 'aesirx_analytics_update_notice' );
    }
}

add_action( 'admin_notices', 'aesirx_analytics_display_update_notice' );

function aesirx_analytics_admin_notice() {
    // If the permalink structure is empty, WordPress is using the default "Plain" format.
    if ( get_option( 'permalink_structure' ) === '' ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php echo esc_html__('Important: Permalink Settings Required', 'aesirx-consent') ?></strong><br>
                <?php
                    echo wp_kses_post(
                        sprintf(
                            /* translators: 1: Permalink structure example, 2: Link to Permalink Settings page */
                            __('Our plugin requires that your site use a pretty permalink structure (for example, %1$s) to work correctly.
                            It looks like your current settings are using the plain permalink format, which might cause issues.
                            Please %2$s to open your Permalink Settings.
                            Once there, select the "Post name" option (or another pretty permalink structure) and click "Save Changes."', 
                            'aesirx-consent'
                            ),
                            '<code>/%postname%/</code>', // 1 - Permalink structure example
                            '<a href="' . esc_url(admin_url('options-permalink.php')) . '">' . esc_html__('click here', 'aesirx-consent') . '</a>' // 2 - Link to Permalink Settings
                        )
                    );
                ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'aesirx_analytics_admin_notice' );

add_action('admin_init', function () {
    if (get_option('aesirx_analytics_do_activation_redirect', false)) {

        delete_option('aesirx_analytics_do_activation_redirect');

        if (wp_safe_redirect("options-general.php?page=aesirx-consent-management-plugin")) {
            exit();
        }
    }
});

global $wpdb;
function get_real_ip() {
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $rawIp = sanitize_text_field(wp_unslash($_SERVER[$header])); 
            $ipList = explode(',', $rawIp);

            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    return false;
}
function get_real_user_agent() {
    $headers = ['HTTP_USER_AGENT'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $rawUserAgent = sanitize_text_field(wp_unslash($_SERVER[$header])); 
            return $rawUserAgent;
        }
    }
    return false;
}

$ip = get_real_ip();
$userAgent = get_real_user_agent();

$consent = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->prepare(
        "SELECT * 
        FROM {$wpdb->prefix}analytics_visitor_consent
        INNER JOIN {$wpdb->prefix}analytics_visitors 
            ON {$wpdb->prefix}analytics_visitor_consent.visitor_uuid = {$wpdb->prefix}analytics_visitors.uuid
        INNER JOIN {$wpdb->prefix}analytics_flows  
            ON {$wpdb->prefix}analytics_visitors.uuid = {$wpdb->prefix}analytics_flows.visitor_uuid  
        WHERE ip = %s AND user_agent = %s AND consent = 1 AND expiration IS NULL
            AND {$wpdb->prefix}analytics_flows.start = {$wpdb->prefix}analytics_flows.end
            AND DATE({$wpdb->prefix}analytics_flows.start) = CURDATE()
        ORDER BY {$wpdb->prefix}analytics_flows.start DESC
        LIMIT 1",
        array($ip, $userAgent)
    )
);
$disabled_block_domains = get_option('aesirx_analytics_plugin_options_disabled_block_domains');
$consentParams = isset($_GET['consent']) ? sanitize_text_field($_GET['consent']) : 'no';
$optionsPlugin = get_option('aesirx_analytics_plugin_options');
$blockingCookiesPermanent =  isset($optionsPlugin['blocking_cookies_permanent']) &&  count($optionsPlugin['blocking_cookies_permanent']) > 0 ? $optionsPlugin['blocking_cookies_permanent'] : [];

function aesirx_analytics_get_deregistered_scripts($havePermanent = false) {
    global $wp_scripts;
    $deregistered_scripts = array();
    $options = get_option('aesirx_analytics_plugin_options');
    $blockingCookiesPaths = isset($options['blocking_cookies']) 
        ? array_filter($options['blocking_cookies'], fn($v) => trim($v) !== '') 
        : [];
    $arrayCookiesPlugins =  isset($options['blocking_cookies_plugins']) &&  count($options['blocking_cookies_plugins']) > 0 ? $options['blocking_cookies_plugins'] : [];
    $prefix = "wp-content/plugins/";
    $blockingCookiesPlugins = isset($options['blocking_cookies_plugins']) && count($options['blocking_cookies_plugins']) > 0
    ? array_map(function($value) use ($prefix) {
        return $prefix . $value;
    }, array_filter($arrayCookiesPlugins, fn($v) => trim($v) !== ''))
    : [];
    $blockingCookiesPermanent =  isset($options['blocking_cookies_permanent']) &&  count($options['blocking_cookies_permanent']) > 0 ? $options['blocking_cookies_permanent'] : [];

    $blockingCookies = array_unique(array_merge($blockingCookiesPaths, $blockingCookiesPlugins), SORT_REGULAR);
    $blockingCookiesObjects = array_map(function ($cookie, $key) use ($blockingCookiesPermanent) {
        return [
            'domain' => $cookie,
            'blocking_permanent' => $blockingCookiesPermanent[$key] ?? 'off',
        ];
    }, array_values($blockingCookies), array_keys(array_values($blockingCookies)));
    $queueScripts = $wp_scripts->queue;
    $blockingCookiesMode = isset($options['blocking_cookies_mode']) ? $options['blocking_cookies_mode'] : '3rd_party';
    $siteDomain = isset($_SERVER['HTTP_HOST']) ? filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL) : 'unknown';

    foreach ( $wp_scripts->registered as $handle => $script ) {
        if ( !is_string($script->src) || !in_array($handle, $queueScripts, true) ) {
            continue;
        }

        if ($blockingCookiesMode === '3rd_party') {
            $scriptDomain = wp_parse_url($script->src, PHP_URL_HOST);

            if ($scriptDomain && $scriptDomain === $siteDomain) {
                continue;
            }
        }

        foreach ($blockingCookiesObjects as $cookie) {
            if (isset($cookie['domain']) && stripos($script->src, $cookie['domain']) !== false) {
                $deregistered_scripts[$handle] = $script;
                if($havePermanent) {
                    if($cookie['blocking_permanent'] === 'on') {
                        wp_deregister_script( $handle );
                        wp_dequeue_script( $handle );
                    }
                } else {
                    wp_deregister_script( $handle );
                    wp_dequeue_script( $handle );
                }
            }
        }
    }

    return $deregistered_scripts;
}
if (!$consent && $consentParams !== 'yes') {
    add_action( 'wp_enqueue_scripts', function (): void {

		$deregistered_scripts = aesirx_analytics_get_deregistered_scripts();

        wp_localize_script( 'aesirx-consent', 'aesirx_analytics_degistered_scripts', $deregistered_scripts );
	}, 9999 );

    add_action( 'wp_head', function (): void {

        $deregistered_scripts = aesirx_analytics_get_deregistered_scripts();

        ?>
        <script type="text/javascript">
            var deregistered_scripts_head = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );

    add_action( 'wp_footer', function (): void {

        $deregistered_scripts = aesirx_analytics_get_deregistered_scripts();

        ?>
        <script type="text/javascript">
            var deregistered_scripts_footer = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );
} else if ($disabled_block_domains && ($consent || $consentParams !== 'yes')) {
    add_action( 'wp_enqueue_scripts', function (): void {

		$deregistered_scripts = aesirx_analytics_block_disabled_domains();

        wp_localize_script( 'aesirx-consent', 'aesirx_analytics_degistered_scripts', $deregistered_scripts );
	}, 9999 );

    add_action( 'wp_head', function (): void {

        $deregistered_scripts = aesirx_analytics_block_disabled_domains();

        ?>
        <script type="text/javascript">
            var deregistered_scripts_head = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );

    add_action( 'wp_footer', function (): void {

        $deregistered_scripts = aesirx_analytics_block_disabled_domains();

        ?>
        <script type="text/javascript">
            var deregistered_scripts_footer = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );
    function aesirx_analytics_block_disabled_domains() {
        global $wp_scripts;
        $deregistered_scripts = array();
        $disabled_block_domains = get_option('aesirx_analytics_plugin_options_disabled_block_domains');
    
        $disabled_domains_list = is_string($disabled_block_domains) ? json_decode($disabled_block_domains, true) : [];
   
        if (!is_array($disabled_domains_list)) {
            return $deregistered_scripts;
        }
        $queueScripts = $wp_scripts->queue;
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!is_string($script->src) || !in_array($handle, $queueScripts, true)) {
                continue;
            }
            foreach ($disabled_domains_list as $item) {
                if (!empty($item['domain']) && stripos($script->src, $item['domain']) !== false) {
                    $deregistered_scripts[$handle] = $script;
                    wp_deregister_script($handle);
                    wp_dequeue_script($handle);
                    break;
                }
            }
        }
        return $deregistered_scripts;
    }
} else if (isset($optionsPlugin['blocking_cookies_permanent']) && count($blockingCookiesPermanent) > 0) {
    add_action( 'wp_enqueue_scripts', function (): void {

		$deregistered_scripts = aesirx_analytics_get_deregistered_scripts(true);

        wp_localize_script( 'aesirx-consent', 'aesirx_analytics_degistered_scripts', $deregistered_scripts );
	}, 9999 );

    add_action( 'wp_head', function (): void {

        $deregistered_scripts = aesirx_analytics_get_deregistered_scripts(true);

        ?>
        <script type="text/javascript">
            var deregistered_scripts_head = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );

    add_action( 'wp_footer', function (): void {

        $deregistered_scripts = aesirx_analytics_get_deregistered_scripts(true);

        ?>
        <script type="text/javascript">
            var deregistered_scripts_footer = <?php echo wp_json_encode($deregistered_scripts); ?>;
        </script>
    <?php
    }, 9999 );
}