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

  add_settings_section(
    'aesirx_analytics_settings',
    'Aesirx Consent Management',
    function () {
      echo wp_kses_post(
        sprintf('<p class= "description"><button class="cta-button" type="button" id="sign-up-button">'.esc_html__('Sign up', 'aesirx-consent').'</button></p>')
      );
    },
    'aesirx_analytics_plugin'
  );


  add_settings_field(
    'aesirx_analytics_clientid',
    esc_html__('Client ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo wp_kses("<input id='aesirx_analytics_clientid' name='aesirx_analytics_plugin_options[clientid]' type='text' value='" .
      esc_attr($options['clientid'] ?? '') .
      "' />", aesirx_analytics_escape_html());
        echo wp_kses("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".sprintf(__("<p class= 'description'>
		    Provided SSO CLIENT ID from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://aesirx.io/licenses')."</p>", aesirx_analytics_escape_html());
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
      echo wp_kses("<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
      esc_attr($options['secret'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      echo wp_kses("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".sprintf(__("<p class= 'description'>
      Provided SSO Client Secret from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://aesirx.io/licenses')."</p>", aesirx_analytics_escape_html());  
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
      echo wp_kses("<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
      esc_attr($options['license'] ?? '') .
      "' /> <p class= 'description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>
      ".sprintf(__("<p class= 'description'>
      Sign up on the AesirX platform to obtain your Shield of Privacy ID and buy license, and activate support for decentralized consent <span class='text-link sign-up-link'>here</span>.</p>", 'aesirx-consent'))."</p>", aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );


  add_settings_field(
    'aesirx_analytics_consent_template',
    __('Consent Template', 'aesirx-consent'),
    function () {
      $checked = 'checked="checked"';
      $template = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo '<table id="aesirx-consent-template">';
      echo  '<tr>';
      echo    '<td>';
      echo "<img src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_default.png')."' />";
      echo wp_kses('<label>' . esc_html__('Default template', 'aesirx-consent') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[datastream_template]" ' .
      (!$template['datastream_template'] || $template['datastream_template'] == 'default' ? $checked : '') .
      ' value="default"  /></label>', aesirx_analytics_escape_html());
      echo wp_kses("<p class='description'><strong>".esc_html__('Description', 'aesirx-consent').": </strong>".esc_html__("AesirX Consent Management is improving Google Consent Mode 2.0 by not loading any tags until after consent is given reducing the compliance risk.", 'aesirx-consent')."</p>", aesirx_analytics_escape_html());
      echo    '</td>';
      echo    '<td>';
      echo "<img src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_simple_mode.png')."' />";
      echo wp_kses('<label>' . esc_html__('Simple Consent Mode', 'aesirx-consent') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[datastream_template]" ' .
      ($template['datastream_template'] == 'simple-consent-mode' ? $checked : '') .
      ' value="simple-consent-mode" /></label>', aesirx_analytics_escape_html());
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
      $options = get_option('aesirx_analytics_plugin_options',[]);
      echo wp_kses("<input id='aesirx_analytics_plugin_options_datastream_gtag_id' name='aesirx_analytics_plugin_options[datastream_gtag_id]' type='text' value='" .
      esc_attr($options['datastream_gtag_id'] ?? '') .
      "' />", aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtm_id',
    esc_html__('GTM ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options',[]);
      echo wp_kses("<input id='aesirx_analytics_plugin_options_datastream_gtm_id' name='aesirx_analytics_plugin_options[datastream_gtm_id]' type='text' value='" .
      esc_attr($options['datastream_gtm_id'] ?? '') .
      "' />", aesirx_analytics_escape_html());
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
        echo wp_kses("<input id='aesirx_analytics_blocking_cookies_plugins".esc_attr($plugin['TextDomain'])."' name='aesirx_analytics_plugin_options[blocking_cookies_plugins][]' 
        value='" . esc_attr($plugin['TextDomain']) . "' type='checkbox'" 
        . (isset($options['blocking_cookies_plugins']) && in_array($plugin['TextDomain'], $options['blocking_cookies_plugins']) ? ' checked="checked"' : '') . "/>", aesirx_analytics_escape_html());
        echo '</td>';
        echo '</tr>';
      }
      echo '</table>';
      echo wp_kses('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks selected third-party plugins from loading until user consent is given.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo wp_kses('<ul class="description"><li>'.esc_html__('Completely prevents the loading and execution of chosen third-party plugins before consent.', 'aesirx-consent').'</li><li>'.esc_html__('No network requests are made to third-party servers, enabling maximum compliance with privacy regulations like GDPR and the ePrivacy Directive.', 'aesirx-consent').'</li></ul>', aesirx_analytics_escape_html());
      echo wp_kses('<p class="description">'.sprintf(__("<p class= 'description'>
      For detailed guides, how-to videos, and API documentation, visit our Documentation Hub:  <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-consent'), 'https://aesirx.io/documentation').'</p>', aesirx_analytics_escape_html());
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
            echo '<td>' . wp_kses('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-consent').'" value="'.esc_attr($field).'">', aesirx_analytics_escape_html()) . '</td>';
            echo '<td>' . wp_kses('<button class="aesirx-consent-remove-cookies-row">'.esc_html__('Remove', 'aesirx-consent').'</button>', aesirx_analytics_escape_html()) . '</td>';
            echo '</tr>';
          }
      } else {
          echo '<tr class="aesirx-consent-cookie-row">';
          echo '<td>' . wp_kses('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-consent').'">', aesirx_analytics_escape_html()) . '</td>';
          echo '<td>' . wp_kses('<button class="aesirx-consent-remove-cookies-row">'.esc_html__('Remove', 'aesirx-consent').'</button>', aesirx_analytics_escape_html()) . '</td>';
          echo '</tr>';
      }
      echo '</table>';
      echo wp_kses('<button id="aesirx-consent-add-cookies-row">'.esc_html__('Add', 'aesirx-consent').'</button>', aesirx_analytics_escape_html());
      echo wp_kses('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Removes scripts matching specified domains or paths from the browser until user consent is given.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo wp_kses("<ul class='description'><li>".esc_html__("Blocks or removes scripts from running in the user's browser before consent is given.", 'aesirx-consent')."</li><li>".esc_html__("While it prevents scripts from executing, initial network requests may still occur, so it enhances privacy compliance under GDPR but may not fully meet the ePrivacy Directive requirements.", 'aesirx-consent')."</li></ul>", aesirx_analytics_escape_html());
      echo wp_kses('<p class="description"><strong>'.esc_html__('Disclaimer', 'aesirx-consent').': </strong>'.esc_html__('The AesirX Consent Shield has only just been released and still being adopted based on feedback and inputs from agencies, developers and users, if you experience any issues please contact our support.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
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
        echo wp_kses('<div class="description">
        <label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
        ($mode == '3rd_party' ? $checked : '') .
        ' value="3rd_party"  />' . esc_html__('Only Third-Party Hosts (default)', 'aesirx-consent') . '</label></div>', aesirx_analytics_escape_html());
        echo wp_kses('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks JavaScript from third-party domains, allowing first-party scripts to run normally and keep essential site functions intact.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
        echo wp_kses('<p class="description"></p>', aesirx_analytics_escape_html());
        echo wp_kses('<div class="description"><label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
            ($mode == 'both' ? $checked : '') .
            ' value="both" />' . esc_html__('Both First and Third-Party Hosts', 'aesirx-consent') . '</label></div>', aesirx_analytics_escape_html());
        echo wp_kses('<p class="description"><strong>'.esc_html__('Description', 'aesirx-consent').': </strong>'.esc_html__('Blocks JavaScript from both first-party and third-party domains for comprehensive script control, giving you the ability to block any JavaScript from internal or external sources based on user consent.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_consent_info',
    '',
    function () {
      // using custom function to escape HTML
      echo wp_kses("<div class='aesirx_consent_info'><div class='wrap'>".esc_html__("Need Help? Access Our Comprehensive Documentation Hub", 'aesirx-consent')."
      <p class='banner-description'>".sprintf(__("Explore How-To Guides, instructions, and tutorials to get the most from AesirX Consent Shield. Whether you're a </br> developer or admin, find all you need to configure and optimize your privacy setup.", 'aesirx-consent'))."</p>
      <p class='banner-description-bold'>".esc_html__("Ready to take the next step? Discover the latest features and best practices.", 'aesirx-consent')."</p><div>
      <a target='_blank' href='https://aesirx.io/documentation'><img src='". plugins_url( 'aesirx-consent/assets/images-plugin/icon_button.svg')."' />".esc_html__('ACCESS THE DOCUMENTATION HUB', 'aesirx-consent')."</a></div>", aesirx_analytics_escape_html());
    },
    'aesirx_consent_info'
  );


  add_settings_section(
    'aesirx_signup_modal',
    '',
    function () {
      echo wp_kses("<div class='aesirx_signup_modal'><div class='aesirx_signup_modal_body'><iframe src='https://signup.aesirx.io/'></iframe></div></div>", aesirx_analytics_escape_html());
    },
    'aesirx_signup_modal'
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

          do_settings_sections('aesirx_analytics_plugin');
          wp_nonce_field('aesirx_analytics_settings_save', 'aesirx_analytics_settings_nonce');
        ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save', 'aesirx-analytics'); ?>"/>
			</form>
			<?php
        do_settings_sections('aesirx_consent_info');
        do_settings_sections('aesirx_signup_modal');
    }
  );
});

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'settings_page_aesirx-consent-management-plugin') {
    wp_enqueue_script('aesirx_analytics_repeatable_fields', plugins_url('assets/vendor/aesirx-consent-repeatable-fields.js', __DIR__), array('jquery'), true, true);
  }
});
function aesirx_analytics_get_api($url) {
  $response = wp_remote_get( $url );
  if ( is_wp_error( $response )) {
    add_settings_error(
      'aesirx_analytics_plugin_options',
      'trial',
      esc_html__('Something went wrong. Please contact the administrator', 'aesirx-analytics'),
      'error'
    );
    return false;
  } else {
    return $response;
  }
}

function aesirx_analytics_trigger_trial() {
  $urlPost = 'https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal';
  $args = array(
      'headers' => array(
          'Content-Type' => 'application/json',
      ),
      'timeout' => 45,
      'body' => json_encode( array( 'domain' => $_SERVER['SERVER_NAME'] ) ),
  );

  $responsePost = wp_remote_post( $urlPost, $args);
  if ( $responsePost['response']['code'] === 200 ) {
    $checkTrialAfterPost = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.$_SERVER['SERVER_NAME']);
    $body = wp_remote_retrieve_body($checkTrialAfterPost);
    if(json_decode($body)->result->success) {
      $dateExpired = new DateTime(json_decode($body)->result->date_expired);
      $currentDate = new DateTime();
      $interval = $currentDate->diff($dateExpired);
      $daysLeft = $interval->days;
      add_settings_error(
        'aesirx_analytics_plugin_options',
        'trial',
        wp_kses(sprintf(__("Your trial license ends in %1\$s days. Please update new license <a href='%2\$s' target='_blank'>%2\$s</a>.", 'aesirx-consent'), $daysLeft, 'https://aesirx.io/licenses'), aesirx_analytics_escape_html()),
        'info'
      );
    }
  } else {
    $error_message = $responsePost['response']['message'];
    add_settings_error(
      'aesirx_analytics_plugin_options',
      'trial',
      esc_html__(sprintf(
        __('Something went wrong: %s. Please contact the administrator', 'aesirx-analytics'),
        $error_message
      )),
      'error'
    );
  }
}

function aesirx_analytics_license_info() {
  $options = get_option('aesirx_analytics_plugin_options', []);
  if (!empty($options['license'])) {
    $response = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPLicense&api=hal&license=' . $options['license']);
    $bodyCheckLicense = wp_remote_retrieve_body($response);
    if ($response['response']['code'] === 200 ) {
      if(!json_decode($bodyCheckLicense)->result->success || json_decode($bodyCheckLicense)->result->subscription_product !== "product-aesirx-cmp") {
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'trial',
          wp_kses(sprintf(__("Your license is expried or not found. Please update new license <a href='%1\$s' target='_blank'>%1\$s</a>.", 'aesirx-consent'), 'https://aesirx.io/licenses'), aesirx_analytics_escape_html()),
          'error'
        );
      }
    } else {
      $error_message = $response['response']['message'];
      add_settings_error(
        'aesirx_analytics_plugin_options',
        'trial',
        esc_html__(sprintf(
          __('Check license failed: %s. Please contact the administrator or update your license', 'aesirx-analytics'),
          $error_message
        )),
        'error'
      );
    }
  } else {
    $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.$_SERVER['SERVER_NAME']);
    $body = wp_remote_retrieve_body($checkTrial);
    if($body) {
      if(json_decode($body)->result->success) {
        $dateExpired = new DateTime(json_decode($body)->result->date_expired);
        $currentDate = new DateTime();
        $interval = $currentDate->diff($dateExpired);
        $daysLeft = $interval->days;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'trial',
          wp_kses(sprintf(__("Your trial license ends in %1\$s days. Please update new license <a href='%2\$s' target='_blank'>%2\$s</a>.", 'aesirx-consent'), $daysLeft, 'https://aesirx.io/licenses'), aesirx_analytics_escape_html()),
          'info'
        );
      } else {
        if(json_decode($body)->result->date_expired) {
          add_settings_error(
            'aesirx_analytics_plugin_options',
            'trial',
            wp_kses(sprintf(__("Your free trials has ended. Please update your license. <a href='%1\$s' target='_blank'>%1\$s</a>.", 'aesirx-consent'), 'https://aesirx.io/licenses'), aesirx_analytics_escape_html()),
            'error'
          );
        } else {
          aesirx_analytics_trigger_trial();
        }
      }
    }
  }
}
add_action( 'admin_notices', 'aesirx_analytics_license_info' );

/**
 * Custom escape function for Aesirx Analytics.
 * Escapes HTML attributes in a string using a specified list of allowed HTML elements and attributes.
 *
 * @param string $string The input string to escape HTML attributes from.
 * @return string The escaped HTML string.
 */
function aesirx_analytics_escape_html() {
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
      'span' => array(
        'class' => array(),
      ),
     ),
     'span' => array(
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
     'iframe' => array(
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

  return $allowed_html;
}
