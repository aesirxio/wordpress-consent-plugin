<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_init', function () {
  register_setting('aesirx_analytics_plugin_options', 'aesirx_analytics_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    if ($input['storage'] == 'internal') {
      if (empty($input['license'])) {
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'license',
          esc_html__('Please register your license at Signup.aesirx.io to enable the external first-party server.', 'aesirx-consent'),
          'warning'
        );
      }
    } elseif ($input['storage'] == 'external') {
      if (empty($input['domain'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Domain is empty.', 'aesirx-consent')
        );
      } elseif (filter_var($input['domain'], FILTER_VALIDATE_URL) === false) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Invalid domain format.', 'aesirx-consent')
        );
      }
    }

    // Ignore the user's changes and use the old database value.
    if (!$valid) {
      $value = get_option('aesirx_analytics_plugin_options');
    }

    return $value;
  });
  register_setting('aesirx_analytics_plugin_options_datastream_gtag_id', 'aesirx_analytics_plugin_options_datastream_gtag_id');
  register_setting('aesirx_analytics_plugin_options_datastream_gtm_id', 'aesirx_analytics_plugin_options_datastream_gtm_id');
  register_setting('aesirx_analytics_plugin_options_datastream_template', 'aesirx_analytics_plugin_options_datastream_template');

  add_settings_section(
    'aesirx_analytics_settings',
    'Aesirx Consent Management',
    function () {
      echo wp_kses_post(
        /* translators: %s: URL to aesir.io read mor details */
        sprintf('<p class= "description"><strong>'. esc_html__('Note: ', 'aesirx-consent') . '</strong>' . esc_html__('Please set Permalink Settings in WP so it is NOT set as plain.', 'aesirx-consent') .'</p>')
      );
    },
    'aesirx_analytics_plugin'
  );

  function aesirx_analytics_warning_missing_license() {
    $options = get_option('aesirx_analytics_plugin_options');

    if (!$options || (empty($options['license']) && $options['storage'] === "internal")) {
      ?>
        <div class="notice-warning notice notice-bi" style="display: none;">
            <p><?php echo esc_html__( 'Please register your license at signup.aesirx.io to enable decentralized consent functionality.', 'aesirx-consent' ); ?></p>
        </div>
      <?php
    }
  }
  add_action( 'admin_notices', 'aesirx_analytics_warning_missing_license' );


  add_settings_field(
    'aesirx_analytics_clientid',
    esc_html__('Client ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_clientid' name='aesirx_analytics_plugin_options[clientid]' type='text' value='" .
        esc_attr($options['clientid'] ?? '') .
        "' />");
        echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".sprintf(__("<p class= 'description'>
		    Provided SSO CLIENT ID from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://dapp.shield.aesirx.io/licenses')."</p>");
        $manifest = json_decode(
          file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
        );
  
        if ($manifest->entrypoints->plugin->assets) {
          foreach ($manifest->entrypoints->plugin->assets->js as $js) {
            wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
          }
        }
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_secret',
    esc_html__('Client Secret', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
        esc_attr($options['secret'] ?? '') .
        "' />");
        echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".sprintf(__("<p class= 'description'>
		    Provided SSO Client Secret from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://dapp.shield.aesirx.io/licenses')."</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );
 

  add_settings_field(
    'aesirx_analytics_license',
    esc_html__('License', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
      esc_attr($options['license'] ?? '') .
      "' /> <p class= 'description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>
      ".sprintf(__("<p class= 'description'>
      Sign up on the AesirX platform to obtain your Shield of Privacy ID and free license, and activate support for decentralized consent at <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://signup.aesirx.io')."</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );


  add_settings_field(
    'aesirx_analytics_consent_template',
    __('Consent Template', 'aesirx-consent'),
    function () {
      $checked = 'checked="checked"';
      $template = get_option('aesirx_analytics_plugin_options_datastream_template') ?? "default";
      // using custom function to escape HTML
      echo '<table id="aesirx-consent-template">';
      echo  '<tr>';
      echo    '<td>';
      echo "<img src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_default.png')."' />";
      echo aesirx_analytics_escape_html('<label>' . esc_html__('Default template', 'aesirx-consent') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options_datastream_template" ' .
      ($template == 'default' ? $checked : '') .
      ' value="default"  /></label>');
      echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".esc_html__("AesirX Consent Management is improving Google Consent Mode 2.0 by not loading any tags until after consent is given reducing the compliance risk.", 'aesirx-consent')."</p>");
      echo    '</td>';
      echo    '<td>';
      echo "<img src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_simple_mode.png')."' />";
      echo aesirx_analytics_escape_html('<label>' . esc_html__('Simple Consent Mode', 'aesirx-consent') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options_datastream_template" ' .
      ($template == 'simple-consent-mode' ? $checked : '') .
      ' value="simple-consent-mode" /></label>');
      echo    '</td>';
      echo  '</tr>';
      echo '</table>';
    }, 
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );


  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtag_id',
    esc_html__('Gtag ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options_datastream_gtag_id');
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_plugin_options_datastream_gtag_id' name='aesirx_analytics_plugin_options_datastream_gtag_id' type='text' value='" .
        esc_attr($options ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtm_id',
    esc_html__('GTM ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options_datastream_gtm_id');
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_plugin_options_datastream_gtm_id' name='aesirx_analytics_plugin_options_datastream_gtm_id' type='text' value='" .
        esc_attr($options ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  
  add_settings_field(
    'aesirx_analytics_blocking_cookies_plugins',
    esc_html__('AesirX Consent Shield for Third-Party Plugins ', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $installed_plugins = get_plugins();
      $active_plugins = get_option('active_plugins');
      echo '<table class="aesirx-consent-cookie-plugin">';
      foreach ($installed_plugins as $path => $plugin) {

        if ($plugin['TextDomain'] === 'aesirx-consent' || $plugin['TextDomain'] === '' || !in_array($path, $active_plugins)) {
          continue;
        }
        echo '<tr class="aesirx-consent-cookie-plugin-item">';
        echo '<td>';
        echo '<label for="aesirx_analytics_blocking_cookies_plugins'.esc_attr($plugin['TextDomain']).'">' . esc_html($plugin['Name']) . '</label>';
        echo '</td>';
        echo '<td>';
        echo aesirx_analytics_escape_html(
          "<input id='aesirx_analytics_blocking_cookies_plugins".esc_attr($plugin['TextDomain'])."' name='aesirx_analytics_plugin_options[blocking_cookies_plugins][]' 
          value='" . esc_attr($plugin['TextDomain']) . "' type='checkbox'" 
          . (isset($options['blocking_cookies_plugins']) && in_array($plugin['TextDomain'], $options['blocking_cookies_plugins']) ? ' checked="checked"' : '') . "/>"
        );
        echo '</td>';
        echo '</tr>';
      }
      echo '</table>';
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks selected third-party plugins from loading until user consent is given.', 'aesirx-consent').'</p>');
      echo aesirx_analytics_escape_html('<ul class="description"><li>'.esc_html__('Completely prevents the loading and execution of chosen third-party plugins before consent.', 'aesirx-consent').'</li><li>'.esc_html__('No network requests are made to third-party servers, enabling maximum compliance with privacy regulations like GDPR and the ePrivacy Directive.', 'aesirx-consent').'</li></ul>');
      echo aesirx_analytics_escape_html('<p class="description">'.sprintf(__("<p class= 'description'>
      For detailed guides, how-to videos, and API documentation, visit our Documentation Hub:  <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://aesirx.io/documentation').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies',
    esc_html__('AesirX Consent Shield for Domain/Path-Based Blocking', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo '<table id="aesirx-consent-blocking-cookies">';
      if (isset($options['blocking_cookies'])) {
          foreach ($options['blocking_cookies'] as $field) {
            echo '<tr class="aesirx-consent-cookie-row">';
            echo '<td>' . aesirx_analytics_escape_html('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-consent').'" value="'.esc_attr($field).'">') . '</td>';
            echo '<td>' . aesirx_analytics_escape_html('<button class="aesirx-consent-remove-cookies-row">'.esc_html__('Remove', 'aesirx-consent').'</button>') . '</td>';
            echo '</tr>';
          }
      } else {
          echo '<tr class="aesirx-consent-cookie-row">';
          echo '<td>' . aesirx_analytics_escape_html('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-consent').'">') . '</td>';
          echo '<td>' . aesirx_analytics_escape_html('<button class="aesirx-consent-remove-cookies-row">'.esc_html__('Remove', 'aesirx-consent').'</button>') . '</td>';
          echo '</tr>';
      }
      echo '</table>';
      echo aesirx_analytics_escape_html('<button id="aesirx-consent-add-cookies-row">'.esc_html__('Add', 'aesirx-consent').'</button>');
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Removes scripts matching specified domains or paths from the browser until user consent is given.', 'aesirx-consent').'</p>');
      echo aesirx_analytics_escape_html("<ul class='description'><li>".esc_html__("Blocks or removes scripts from running in the user's browser before consent is given.", 'aesirx-consent')."</li><li>".esc_html__("While it prevents scripts from executing, initial network requests may still occur, so it enhances privacy compliance under GDPR but may not fully meet the ePrivacy Directive requirements.", 'aesirx-consent')."</li></ul>");
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Disclaimer', 'aesirx-consent').': </strong>'.esc_html__('The AesirX Consent Shield has only just been released and still being adopted based on feedback and inputs from agencies, developers and users, if you experience any issues please contact our support.', 'aesirx-consent').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies_mode',
    esc_html__('Script Blocking Options', 'aesirx-consent'),
    function () {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $checked = 'checked="checked"';
        $mode = $options['blocking_cookies_mode'] ?? '3rd_party';
        // using custom function to escape HTML
        echo aesirx_analytics_escape_html('<div class="description">
        <label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
        ($mode == '3rd_party' ? $checked : '') .
        ' value="3rd_party"  />' . esc_html__('Only Third-Party Hosts (default)', 'aesirx-consent') . '</label></div>');
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks JavaScript from third-party domains, allowing first-party scripts to run normally and keep essential site functions intact.', 'aesirx-consent').'</p>');
        echo aesirx_analytics_escape_html('<p class="description"></p>');
        echo aesirx_analytics_escape_html('<div class="description"><label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
            ($mode == 'both' ? $checked : '') .
            ' value="both" />' . esc_html__('Both First and Third-Party Hosts', 'aesirx-consent') . '</label></div>');
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks JavaScript from both first-party and third-party domains for comprehensive script control, giving you the ability to block any JavaScript from internal or external sources based on user consent.', 'aesirx-consent').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_consent_info',
    '',
    function () {
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<div class='aesirx_consent_info'><div class='wrap'>".esc_html__("Need Help? Access Our Comprehensive Documentation Hub", 'aesirx-consent')."
      <p class='banner-description'>".sprintf(__("Explore How-To Guides, instructions, and tutorials to get the most from AesirX Consent Shield. Whether you're a </br> developer or admin, find all you need to configure and optimize your privacy setup.", 'aesirx-consent'))."</p>
      <p class='banner-description-bold'>".esc_html__("Ready to take the next step? Discover the latest features and best practices.", 'aesirx-consent')."</p><div>
      <a target='_blank' href='https://aesirx.io/documentation'><img src='". plugins_url( 'aesirx-consent/assets/images-plugin/icon_button.svg')."' />".esc_html__('ACCESS THE DOCUMENTATION HUB', 'aesirx-consent')."</a></div>");
    },
    'aesirx_consent_info'
  );

});

add_action('admin_menu', function () {
  add_options_page(
    esc_html__('Aesirx Consent Management', 'aesirx-consent'),
    esc_html__('Aesirx Consent Management', 'aesirx-analytics'),
    'manage_options',
    'aesirx-consent-management-plugin',
    function () {
      ?>
			<form action="options.php" method="post">
				<?php
          settings_fields('aesirx_analytics_plugin_options');
          settings_fields('aesirx_analytics_plugin_options_datastream_gtag_id');
          settings_fields('aesirx_analytics_plugin_options_datastream_gtm_id');
          settings_fields('aesirx_analytics_plugin_options_datastream_template');

          do_settings_sections('aesirx_analytics_plugin');
          wp_nonce_field('aesirx_analytics_settings_save', 'aesirx_analytics_settings_nonce');
        ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save', 'aesirx-analytics'); ?>"/>
			</form>
			<?php
        do_settings_sections('aesirx_consent_info');
    }
  );
});

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'settings_page_aesirx-consent-management-plugin') {
    wp_enqueue_script('aesirx_analytics_repeatable_fields', plugins_url('assets/vendor/aesirx-consent-repeatable-fields.js', __DIR__), array('jquery'), '1.0.0', true);
  }
});
/**
 * Custom escape function for Aesirx Analytics.
 * Escapes HTML attributes in a string using a specified list of allowed HTML elements and attributes.
 *
 * @param string $string The input string to escape HTML attributes from.
 * @return string The escaped HTML string.
 */
function aesirx_analytics_escape_html($string) {
  $allowed_html = array(
    'input' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
        'placeholder' => array(),
        'checked' => array(),
     ),
     'strong' => array(),
     'a' => array(
      'href'  => array(),
      'target'    => array(),
     ),
     'p' => array(
      'class' => array(),
     ),
     'h3' => array(),
     'ul' => array(
      'class' => array(),
     ),
     'li' => array(),
     'br' => array(),
     'img' => array(
      'src'  => array(),
     ),
     'div' => array(
        'class' => array(),
     ),
     'button' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
    ),
  );

  return wp_kses($string, $allowed_html);
}
