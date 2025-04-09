<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_init', function () {
  register_setting('aesirx_analytics_plugin_options', 'aesirx_analytics_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    if ($input['storage'] === 'internal') {
      if (empty($input['license'])) {
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'license',
          esc_html__('Please register your license at Signup.aesirx.io to enable the external first-party server.', 'aesirx-consent'),
          'warning'
        );
      }
    } elseif ($input['storage'] === 'external') {
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

  register_setting('aesirx_consent_modal_plugin_options', 'aesirx_consent_modal_plugin_options', function (
    $value
  ) {
    $valid = true;
    // Ignore the user's changes and use the old database value.
    if (!$valid) {
      $value = get_option('aesirx_consent_modal_plugin_options');
    }
    return $value;
  });

  add_settings_section(
    'aesirx_analytics_settings',
    'Aesirx Consent Management',
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo wp_kses("
      <input id='aesirx_analytics_first_time_access' name='aesirx_analytics_plugin_options[first_time_access]' type='hidden' value='" .esc_attr($options['first_time_access'] ?? '') .
      "' />
      <input id='aesirx_analytics_verify_domain' name='aesirx_analytics_plugin_options[verify_domain]' type='hidden' value='" .esc_attr($options['verify_domain'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
        }
      }
    },
    'aesirx_analytics_plugin'
  );


  add_settings_field(
    'aesirx_analytics_clientid',
    esc_html__('Your Client ID *', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo "<div class='input_container'>";
      echo wp_kses("
        <input id='aesirx_analytics_clientid' 
                class='aesirx_consent_input'
                placeholder='" . esc_attr__('SSO Client ID', 'aesirx-consent') . "'
                name='aesirx_analytics_plugin_options[clientid]'
                type='text' value='" .esc_attr($options['clientid'] ?? '') ."' />", aesirx_analytics_escape_html());
      echo wp_kses("
        <div class='input_information'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/infor_icon.png')."' />
          ".sprintf(__("<div class='input_information_content'>
          Provided SSO CLIENT ID from <a href='%1\$s' target='_blank'>%1\$s</a>.</div>", 'aesirx-consent'), 'https://aesirx.io/licenses')."
        </div>
      ", aesirx_analytics_escape_html());
      echo "</div>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_clientid_row',
    ]
  );

  add_settings_field(
    'aesirx_analytics_secret',
    esc_html__('Your Client Secret *', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo "<div class='input_container'>";
      echo wp_kses("<input id='aesirx_analytics_secret' class='aesirx_consent_input'  placeholder='".esc_attr__('SSO Client Secret', 'aesirx-consent')."' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
      esc_attr($options['secret'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      echo wp_kses("
        <div class='input_information'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/infor_icon.png')."' />
          ".sprintf(__("<div class='input_information_content'>
          Provided SSO Client Secret from <a href='%1\$s' target='_blank'>%1\$s</a>.</div>", 'aesirx-consent'), 'https://aesirx.io/licenses')."
        </div>
      ", aesirx_analytics_escape_html());
      echo "</div>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_secret_row',
    ]
  );
 

  add_settings_field(
    'aesirx_analytics_license',
    esc_html__('Your License Key', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo "<div class='input_container'>";
      echo wp_kses("<input id='aesirx_analytics_license' class='aesirx_consent_input' placeholder='".esc_attr__('License Key', 'aesirx-consent')."' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
      esc_attr($options['license'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      echo wp_kses("
        <div class='input_information'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/infor_icon.png')."' />
          ".sprintf(__("<div class='input_information_content'>
          Sign up to obtain your Shield of Privacy ID and purchase licenses <a href='https://aesirx.io/licenses' target='blank' class='text-link'>here</a>.</div>", 'aesirx-consent'))."
        </div>
      ", aesirx_analytics_escape_html());
      echo "</div>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_license_row',
    ]
  );


  add_settings_field(
    'aesirx_analytics_consent_template',
    __('Choose your tailored template', 'aesirx-consent'),
    function () {
      $template = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo wp_kses("
        <div class='aesirx_consent_template'>
          <label class='aesirx_consent_template_item ".($template['datastream_template'] === 'simple-consent-mode' ? 'active' : '')."' for='simple-mode'>
            <img width='585px' height='388px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_simple_mode.png')."' />
            <p class='title'>".esc_html__('Simple Consent Mode', 'aesirx-consent')."</p>
            <input id='simple-mode' type='radio' class='analytic-consent-class' name='aesirx_analytics_plugin_options[datastream_template]' " .
            ($template['datastream_template'] === 'simple-consent-mode' ? "checked='checked'" : '') .
            " value='simple-consent-mode'  />
            <p>".esc_html__("Simple Consent Mode follows Google Consent Mode 2.0 by not loading any tags until after consent is given, reducing compliance risks.", 'aesirx-consent')."</p>
          </label>
          <label class='aesirx_consent_template_item ".
          (!$template['datastream_template'] || $template['datastream_template'] === 'default' ? 'active' : '') ."' for='default'>
            <img width='585px' height='388px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/consent_default.png')."' />
            <p class='title'>".esc_html__('Decentralized Consent Mode', 'aesirx-consent')."</p>
            <input type='radio' id='default' class='analytic-consent-class' name='aesirx_analytics_plugin_options[datastream_template]' " .
            (!$template['datastream_template'] || $template['datastream_template'] === 'default' ? "checked='checked'" : '') .
            "value='default'  />
            <p>".esc_html__("The Default setup improves Google Consent Mode 2.0 by not loading any scripts, beacons, or tags until after consent is given, reducing compliance risks. It also includes Decentralized Consent, for more control over personal data and rewards.", 'aesirx-consent')."</p>
          </label>
        </div>
      ", aesirx_analytics_escape_html());
    }, 
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_consent_template_row',
    ]
  );


  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtag_id',
    esc_html__('Gtag ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options',[]);
      echo "<div class='input_container'>";
      echo wp_kses("<input id='aesirx_analytics_plugin_options_datastream_gtag_id' class='aesirx_consent_input' name='aesirx_analytics_plugin_options[datastream_gtag_id]' type='text' value='" .
      esc_attr($options['datastream_gtag_id'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      echo wp_kses("
        <div class='input_information'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/infor_icon.png')."' />
          ".sprintf(__("<div class='input_information_content'>
          Remember to include the explicit purpose (e.g., analytics, marketing) in the consent text to inform users why GTM is being used.</div>", 'aesirx-consent'))."
        </div>
      ", aesirx_analytics_escape_html());
      echo "</div>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_plugin_options_datastream_gtag_id_row',
    ]
  );

  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtm_id',
    esc_html__('GTM ID', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options',[]);
      echo '<div class="input_container">';
      echo wp_kses("<input id='aesirx_analytics_plugin_options_datastream_gtm_id' class='aesirx_consent_input' name='aesirx_analytics_plugin_options[datastream_gtm_id]' type='text' value='" .
      esc_attr($options['datastream_gtm_id'] ?? '') .
      "' />", aesirx_analytics_escape_html());
      echo wp_kses("
        <div class='input_information'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/infor_icon.png')."' />
          ".sprintf(__("<div class='input_information_content'>
          Remember to include the explicit purpose (e.g., analytics, marketing) in the consent text to inform users why GTM is being used.</div>", 'aesirx-consent'))."
        </div>
      ", aesirx_analytics_escape_html());
      echo '</div>';
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_plugin_options_datastream_gtm_id_row',
    ]
  );

  add_settings_field(
    'aesirx_analytics_plugin_options_datastream_gtm_id_general',
    esc_html__('GTM General', 'aesirx-consent'),
    function () {
      echo wp_kses('<p class="small-description mb-10">
      <img width="18px" height="18px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/question_icon.png').'" />'.esc_html__('To configure, input your Google Tag Manager Gtag ID & GTM ID in the designated fields. Once set up, Google Tag Manager will only load after the user provides consent.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_plugin_options_datastream_gtm_id_general',
    ]
  );
  add_settings_section(
    'aesirx_consent_modal_settings',
    'Consent Modal Management',
    function () {
      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
        }
      }
    },
    'aesirx_consent_modal_plugin'
  );
  add_settings_field(
    'aesirx_consent_modal_datastream_consent',
    esc_html__('Customize Consent Text ', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_consent_modal_plugin_options', []);
      $decodedHtml = html_entity_decode($options['datastream_consent'], ENT_QUOTES, 'UTF-8');
      echo wp_kses('<input id="aesirx_consent_modal_datastream_consent" class="aesirx_consent_input" name="aesirx_consent_modal_plugin_options[datastream_consent]" type="hidden" 
      value="'.esc_attr($options['datastream_consent']).'" />', aesirx_analytics_escape_html());
      echo wp_kses('
      <div id="datastream_consent">
        <div>'.$decodedHtml.'</div>'.'
      </div>', aesirx_analytics_escape_html());
      echo wp_kses('
      <button type="button" class="reset_consent_button aesirx_btn_success_light">
        <img width="20px" height="20px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/reset_icon.png').'" />
        '.esc_html__("Reset Consent", 'aesirx-consent').'
      </button>', aesirx_analytics_escape_html());
    },
    'aesirx_consent_modal_plugin',
    'aesirx_consent_modal_settings',
    [
      'class' => 'aesirx_consent_modal_datastream_consent_row',
    ]
  );

  add_settings_field(
    'aesirx_consent_modal_datastream_detail',
    esc_html__('Customize Detail Text ', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_consent_modal_plugin_options', []);
      $decodedHtml = html_entity_decode($options['datastream_detail'], ENT_QUOTES, 'UTF-8');
      echo wp_kses('<input id="aesirx_consent_modal_datastream_detail" class="aesirx_consent_input" name="aesirx_consent_modal_plugin_options[datastream_detail]" type="hidden" 
      value="'.esc_attr($options['datastream_detail']).'" />', aesirx_analytics_escape_html());
      echo wp_kses('
      <div id="datastream_detail">
        <div>'.$decodedHtml.'</div>'.'
      </div>', aesirx_analytics_escape_html());
      echo wp_kses('
      <button type="button" class="reset_detail_button aesirx_btn_success_light">
        <img width="20px" height="20px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/reset_icon.png').'" />
        '.esc_html__("Reset Detail", 'aesirx-consent').'
      </button>', aesirx_analytics_escape_html());
    },
    'aesirx_consent_modal_plugin',
    'aesirx_consent_modal_settings',
    [
      'class' => 'aesirx_consent_modal_datastream_detail_row',
    ]
  );

  add_settings_field(
    'aesirx_consent_modal_datastream_reject',
    esc_html__('Customize Reject Text ', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_consent_modal_plugin_options', []);
      $decodedHtml = html_entity_decode($options['datastream_reject'], ENT_QUOTES, 'UTF-8');
      echo wp_kses('<input id="aesirx_consent_modal_datastream_reject" class="aesirx_consent_input" name="aesirx_consent_modal_plugin_options[datastream_reject]" type="hidden" 
      value="'.esc_attr($options['datastream_reject']).'" />', aesirx_analytics_escape_html());
      echo wp_kses('
      <div id="datastream_reject">
        <div>'.$decodedHtml.'</div>'.'
      </div>', aesirx_analytics_escape_html());
      echo wp_kses('
      <button type="button" class="reset_reject_button aesirx_btn_success_light">
        <img width="20px" height="20px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/reset_icon.png').'" />
        '.esc_html__("Reset Reject", 'aesirx-consent').'
      </button>', aesirx_analytics_escape_html());
    },
    'aesirx_consent_modal_plugin',
    'aesirx_consent_modal_settings',
    [
      'class' => 'aesirx_consent_modal_datastream_reject_row',
    ]
  );

  
  add_settings_field(
    'aesirx_analytics_blocking_cookies_plugins',
    esc_html__('AesirX Consent Shield for Third-Party Plugins ', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $installed_plugins = get_plugins();
      $active_plugins = get_option('active_plugins');
      echo wp_kses('<p class="small-description mb-10">'.esc_html__('Blocks selected third-party plugins from loading until user consent is given.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo '<div class="aesirx-consent-cookie-plugin mb-10">';

      foreach ($installed_plugins as $path => $plugin) {
        if ($plugin['TextDomain'] === 'aesirx-consent' || $plugin['TextDomain'] === '' || !in_array($path, $active_plugins, true)) {
          continue;
        }
        echo '<div class="aesirx-consent-cookie-plugin-item">';
        echo '<div class="aesirx-consent-cookie-plugin-item-label">';
        echo wp_kses("<input id='aesirx_analytics_blocking_cookies_plugins".esc_attr($plugin['TextDomain'])."' name='aesirx_analytics_plugin_options[blocking_cookies_plugins][]' 
        value='" . esc_attr($plugin['TextDomain']) . "' type='checkbox'" 
        . (isset($options['blocking_cookies_plugins']) && in_array($plugin['TextDomain'], $options['blocking_cookies_plugins'], true) ? ' checked="checked"' : '') . "/>", aesirx_analytics_escape_html()); 
        echo '<label for="aesirx_analytics_blocking_cookies_plugins'.esc_attr($plugin['TextDomain']).'">' . esc_html($plugin['Name']) . '</label>';
        echo '</div>';
        echo wp_kses('
        <select name="aesirx_analytics_plugin_options[blocking_cookies_plugins_category]['.esc_attr($plugin['TextDomain']).']['.esc_html($plugin['Name']).']">
          <option value="essential" '.($options['blocking_cookies_plugins_category'][esc_attr($plugin['TextDomain'])][esc_html($plugin['Name'])] === 'essential' ? 'selected' : '').'>Essential</option>
          <option value="functional" '.($options['blocking_cookies_plugins_category'][esc_attr($plugin['TextDomain'])][esc_html($plugin['Name'])] === 'functional' ? 'selected' : '').'>Functional</option>
          <option value="analytics" '.($options['blocking_cookies_plugins_category'][esc_attr($plugin['TextDomain'])][esc_html($plugin['Name'])] === 'analytics' ? 'selected' : '').'>Analytics</option>
          <option value="advertising" '.($options['blocking_cookies_plugins_category'][esc_attr($plugin['TextDomain'])][esc_html($plugin['Name'])] === 'advertising' ? 'selected' : '').'>Advertising</option>
          <option value="custom" '.($options['blocking_cookies_plugins_category'][esc_attr($plugin['TextDomain'])][esc_html($plugin['Name'])] === 'custom' ? 'selected' : '').'>Custom</option>
        </select>
      ', aesirx_analytics_escape_html());
        echo '</div>';
      }
      echo '</div>';
      echo wp_kses("
        <div class='aesirx_consent_info_wrapper'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/shield_icon.png')."' />
          <div class='aesirx_consent_info_content small-description'>
            ".sprintf(__("Completely prevents the loading and execution of chosen third-party plugins before consent.", 'aesirx-consent'))."
          </div>
        </div>
        <div class='aesirx_consent_info_wrapper'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/shield_icon.png')."' />
          <div class='aesirx_consent_info_content small-description'>
            ".sprintf(__("No network requests are made to third-party servers, enabling maximum compliance with privacy regulations like GDPR and the ePrivacy Directive.", 'aesirx-consent'))."
          </div>
        </div>
      ", aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_blocking_cookies_plugins_row',
    ]
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies',
    esc_html__('AesirX Consent Shield for Domain/Path-Based Blocking', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo wp_kses('<p class="small-description mb-10">'.esc_html__('Removes scripts matching specified domains or paths from the browser until user consent is given.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo '<div id="aesirx-consent-blocking-cookies">';
      if (isset($options['blocking_cookies'])) {
          foreach ($options['blocking_cookies'] as $key => $field) {
            echo wp_kses('
            <div class="aesirx-consent-cookie-row">
              <div class="title">'.esc_html__('Domain', 'aesirx-consent').'</div>
              <input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_attr__('Enter domain or path', 'aesirx-consent').'" value="'.esc_attr($field).'">
              <select name="aesirx_analytics_plugin_options[blocking_cookies_category][]">
                <option value="essential" '.($options['blocking_cookies_category'][$key] === 'essential' ? 'selected' : '').'>Essential</option>
                <option value="functional" '.($options['blocking_cookies_category'][$key] === 'functional' ? 'selected' : '').'>Functional</option>
                <option value="analytics" '.($options['blocking_cookies_category'][$key] === 'analytics' ? 'selected' : '').'>Analytics</option>
                <option value="advertising" '.($options['blocking_cookies_category'][$key] === 'advertising' ? 'selected' : '').'>Advertising</option>
                <option value="custom" '.($options['blocking_cookies_category'][$key] === 'custom' ? 'selected' : '').'>Custom</option>
              </select>
              <button class="aesirx-consent-remove-cookies-row">
                <img width="25px" height="30px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/trash_icon.png').'" />
              </button>
            </div>
            ', aesirx_analytics_escape_html());
          }
      } else {
        echo wp_kses('
        <div class="aesirx-consent-cookie-row">
          <div class="title">'.esc_html__('Domain', 'aesirx-consent').'</div>
          <input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_attr__('Enter domain or path', 'aesirx-consent').'">
          <select name="aesirx_analytics_plugin_options[blocking_cookies_category][]">
            <option value="essential" selected>Essential</option>
            <option value="functional">Functional</option>
            <option value="analytics">Analytics</option>
            <option value="advertising">Advertising</option>
            <option value="custom">Custom</option>
          </select>
          <button class="aesirx-consent-remove-cookies-row">
            <img width="25px" height="30px" src="'. plugins_url( 'aesirx-consent/assets/images-plugin/trash_icon.png').'" />
          </button>
        </div>
        ', aesirx_analytics_escape_html());
      }
      echo '</div>';
      echo wp_kses("
      <button id='aesirx-consent-add-cookies-row'>
        <img width='23px' height='30px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/plus_icon_green.png')."' />
      </button>", aesirx_analytics_escape_html());
      echo wp_kses("
      <div class='aesirx_consent_info_wrapper'>
        <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/shield_icon.png')."' />
        <div class='aesirx_consent_info_content small-description'>
          ".sprintf(__("Blocks or removes scripts from running in the user's browser before consent is given.", 'aesirx-consent'))."
        </div>
      </div>
      <div class='aesirx_consent_info_wrapper'>
        <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/shield_icon.png')."' />
        <div class='aesirx_consent_info_content small-description'>
          ".sprintf(__("While it prevents scripts from executing, initial network requests may still occur, so it enhances privacy compliance under GDPR but may not fully meet the ePrivacy Directive requirements.", 'aesirx-consent'))."
        </div>
      </div>
    ", aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_blocking_cookies_row',
    ]
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies_mode',
    esc_html__('Script Blocking Options', 'aesirx-consent'),
    function () {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $checked = 'checked="checked"';
        $mode = $options['blocking_cookies_mode'] ?? '3rd_party';
        // using custom function to escape HTML
        echo wp_kses('<p class="small-description mb-10">'.esc_html__('Configure how JavaScript is blocked based on user consent preferences.', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
        echo wp_kses('
        <div class="blocking_cookies_section">
          <div class="description">
            <label class="radio_wrapper">
              <input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
          ($mode === '3rd_party' ? $checked : '') .
          ' value="3rd_party"  />
              <div class="input_content">
                <p>'. esc_html__('Only Third-Party Hosts (default)', 'aesirx-consent') . '</p>
                <p class="small-description">'. esc_html__('Blocks JavaScript from third-party domains, allowing first-party scripts to run normally & keep essential site functions intact.', 'aesirx-consent') . '</p>
              </div>
            </label>
          </div>
          <div class="description">
            <label class="radio_wrapper">
              <input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
            ($mode === 'both' ? $checked : '') .
            ' value="both" />
              <div class="input_content">
                <p>'. esc_html__('Both First and Third-Party Hosts', 'aesirx-consent') . '</p>
                <p class="small-description">'. esc_html__('Blocks JavaScript from both first-party & third-party domains for comprehensive script control, giving you the ability to block any JavaScript from internal or external sources based on user consent.', 'aesirx-consent') . '</p>
              </div>
              </label>
          </div>
        </div>
          ', aesirx_analytics_escape_html());
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_blocking_cookies_mode_row',
    ]
  );
  add_settings_field(
    'aesirx_analytics_gpc',
    esc_html__('GPC Compliance', 'aesirx-consent'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $mode = $options['gpc_support'] ?? 'yes';
      // using custom function to escape HTML
      echo wp_kses('
      <div class="gpc_support_section">
        <label class="radio_wrapper">
          <input type="radio" class="analytic-gpc_support-class" name="aesirx_analytics_plugin_options[gpc_support]" ' .
      ($mode === 'yes' ? $checked : '') .
      ' value="yes"  />
          <div class="input_content">
            <p>'. esc_html__('Enable GPC Support', 'aesirx-consent') . '</p>
          </div>
        </label>
        <label class="radio_wrapper">
          <input type="radio" class="analytic-gpc_support-class" name="aesirx_analytics_plugin_options[gpc_support]" ' .
        ($mode === 'no' ? $checked : '') .
        ' value="no" />
          <div class="input_content">
            <p>'. esc_html__('Disable GPC Support', 'aesirx-consent') . '</p>
          </div>
          </label>
      </div>
        ', aesirx_analytics_escape_html());
      $json_content = aesirx_generate_gpc_json();
      $policy_url = get_option('aesirx_privacy_policy_url', get_site_url() . "/privacy-policy");
      echo wp_kses('<p class="small-description mb-10">'.esc_html__('To comply with Global Privacy Control (GPC), please download and upload the following file:', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo wp_kses('<div class="example-content code">'.$json_content.'</div>', aesirx_analytics_escape_html());
      echo wp_kses("<div class='download-button'><a href='data:application/json;charset=utf-8," . urlencode($json_content) . "' download='gpc.json' class='aesirx_btn_success'>Download gpc.json</a></div>", aesirx_analytics_escape_html());
      echo wp_kses('<p class="mb-10">'.esc_html__('Upload Instructions:', 'aesirx-consent').'</p>', aesirx_analytics_escape_html());
      echo wp_kses('<ol>
          <li>'.esc_html__('Download the gpc.json file.', 'aesirx-consent').'</li>
          <li>'.esc_html__('Connect to your website via FTP or File Manager.', 'aesirx-consent').'</li>
          <li>'.sprintf(__('Upload the file to: <code>/public_html/.well-known/gpc.json</code>', 'aesirx-consent')).'</li>
          <li>'.sprintf(__("Ensure the file is accessible by visiting: <a href='" . get_site_url() . "/.well-known/gpc.json' target='_blank'>" . get_site_url() . "/.well-known/gpc.json</a>", 'aesirx-consent')).'</li>
      </ol>', aesirx_analytics_escape_html());
      echo wp_kses('<h3 class="mb-10">Privacy Policy Update</h3>', aesirx_analytics_escape_html());
      echo wp_kses('<p class="small-description mb-10">You must update your Privacy Policy to reflect GPC compliance. Below is a suggested update:</p>', aesirx_analytics_escape_html());
      echo wp_kses("<div class='example-content'><div>Global Privacy Control (GPC)</div> Compliance Our website respects the Global Privacy Control (GPC) signal. If your browser sends a GPC signal, we automatically disable non-essential cookies and opt you out of data sharing. For more details, please visit our Privacy Policy: $policy_url.</div>", aesirx_analytics_escape_html());
      
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings',
    [
      'class' => 'aesirx_analytics_gpc_row',
    ]
  );

  add_settings_section(
    'aesirx_consent_info',
    '',
    function () {
      // using custom function to escape HTML
      echo wp_kses("
      <div class='aesirx_consent_info'>
        <img class='aesirx_consent_banner' width='334px' height='175px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/banner_3.png')."' />
        <div class='wrap'>
          <p class='aesirx_consent_title'>".esc_html__("Need Help? Access Our Comprehensive Documentation Hub", 'aesirx-consent')."</p>
          <div class='aesirx_consent_info_wrapper'>
            <img class='banner' width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/plus_icon.png')."' />
            <div class='aesirx_consent_info_content'>
              ".sprintf(__("Explore How-To Guides, instructions, & tutorials to get the most from AesirX Consent Shield. Whether you're a developer or admin, find all you need to configure & optimize your privacy setup.", 'aesirx-consent'))."
            </div>
          </div>
          <div class='aesirx_consent_info_wrapper'>
            <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/plus_icon.png')."' />
            <div class='aesirx_consent_info_content'>
              ".esc_html__("Discover the latest features & best practices.", 'aesirx-consent')."
            </div>
          </div>
        </div>
        <a class='aesirx_btn_success' target='_blank' href='https://aesirx.io/documentation'>
          Access Doc Hub
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/external_link_icon.png')."' />
        </a>
      </div>", aesirx_analytics_escape_html());
    },
    'aesirx_consent_info'
  );

  add_settings_section(
    'aesirx_consent_register_license',
    '',
    function () {
      // using custom function to escape HTML
      $options = get_option('aesirx_analytics_plugin_options', []);
      $isRegisted = $options['secret'] && $options['clientid'] ? true : false;
      echo wp_kses("
      <div class='aesirx_consent_register_license'>
        ".($isRegisted ? "<img width='255px' height='96px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/banner_1.png')."' />" :"")."
        <div class='aesirx_consent_register_license_notice'>
        ".aesirx_analytics_license_info()."
        </div>
        ".($isRegisted ? "
          <p>".esc_html__("Haven't got a license yet?", 'aesirx-consent')."</p>
        " :"
          <p>".esc_html__("Haven't got Shield of Privacy ID yet?", 'aesirx-consent')."</p>
        ")."
        ".($isRegisted ? "
          <a class='aesirx_btn_success cta-button' target='_blank' href='https://aesirx.io/licenses/consent-management-platform'>
            ".esc_html__("Register Licence Here", 'aesirx-consent')."
            <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/external_link_icon.png')."' />
          </a>
        " :"
          <button class='aesirx_btn_success cta-button' type='button' id='sign-up-button'>
            ".esc_html__("Sign up now", 'aesirx-consent')."
            <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/external_link_icon.png')."' />
          </button>
        ")."
        
        
      </div>", aesirx_analytics_escape_html());
    },
    'aesirx_consent_register_license'
  );

  add_settings_section(
    'aesirx_consent_scanner',
    '',
    function () {
      // using custom function to escape HTML
      echo wp_kses("
      <div class='aesirx_consent_scanner'>
        <p class='aesirx_consent_title'>".esc_html__("Scan Your Site for Privacy Risks", 'aesirx-consent')."</p>
        <div class='aesirx_consent_info_wrapper'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/check_icon.png')."' />
          <div class='aesirx_consent_info_content'>
            ".sprintf(__("Use the Privacy Scanner to quickly identify scripts, tags, & trackers running on your site that may compromise user privacy.", 'aesirx-consent'))."
          </div>
        </div>
        <div class='aesirx_consent_info_wrapper'>
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/check_icon.png')."' />
          <div class='aesirx_consent_info_content'>
            ".esc_html__("Use Consent Shield to block those scripts by adding their domains or paths, ensuring quick & simple compliance.", 'aesirx-consent')."
          </div>
        </div>
        <a class='aesirx_btn_success' target='_blank' href='https://privacyscanner.aesirx.io'>
          Run Privacy Scanner
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/external_link_icon.png')."' />
        </a>
        <div class='aesirx_diviner'></div>
        <img class='aesirx_consent_banner mb-20' width='334px' height='175px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/banner_2.png')."' />
        <p class='aesirx_consent_title'>".esc_html__("Learn how to use AesirX Privacy Scanner with Consent Shield to detect privacy-intrusive elements, using the JetPack plugin as an example.", 'aesirx-consent')."</p>
        <a class='aesirx_btn_success_light' target='_blank' href='https://aesirx.io/documentation/cmp/how-to/jetpack-gdpr-compliance-with-aesirx-cmp'>
          Read the How-To Guide
          <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/book_icon.png')."' />
        </a>
      </div>", aesirx_analytics_escape_html());
    },
    'aesirx_consent_scanner'
  );


  add_settings_section(
    'aesirx_signup_modal',
    '',
    function () {
      echo wp_kses("<div class='aesirx_signup_modal'><div class='aesirx_signup_modal_body'><iframe id='aesirx_signup_iframe' src='https://cmp.signup.aesirx.io'></iframe></div></div>", aesirx_analytics_escape_html());
    },
    'aesirx_signup_modal'
  );

});

add_action('admin_menu', function () {
  add_menu_page(
    'AesirX CMP',
    'AesirX CMP',
    'manage_options',
    'aesirx-consent-management-plugin',
    function () {
      ?>
      <h2 class="aesirx_heading">Aesirx Consent Management</h2>
      <div class="aesirx_consent_wrapper">
      <div class="form_wrapper">
        <form action="options.php" method="post">
          <?php
            settings_fields('aesirx_analytics_plugin_options');

            do_settings_sections('aesirx_analytics_plugin');
            wp_nonce_field('aesirx_analytics_settings_save', 'aesirx_analytics_settings_nonce');
          ?>
          <button type="submit" class="submit_button aesirx_btn_success">
            <?php
              echo wp_kses("
                <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/save_icon.png')."' />
                ".esc_html__("Save settings", 'aesirx-consent')."
              ", aesirx_analytics_escape_html()); 
            ?>
          </button>
        </form>
      </div>
			<?php
        echo '<div class="aesirx_consent_info_section">';
        do_settings_sections('aesirx_consent_register_license');
        do_settings_sections('aesirx_consent_scanner');
        do_settings_sections('aesirx_consent_info');
        do_settings_sections('aesirx_signup_modal');
        echo '</div>';
        echo '</div>';
    },
    plugins_url( 'aesirx-consent/assets/images-plugin/AesirX_BI_icon.png'),
    3
  );
  add_submenu_page(
    'aesirx-consent-management-plugin',
    'Consent Shield',
    'Consent Shield',
    'manage_options',
    'aesirx-consent-management-plugin',
    function () {
      ?><?php
    },
    3
  );
  add_submenu_page(
    'aesirx-consent-management-plugin',
    'Consent Log',
    'Consent Log',
    'manage_options',
    'aesirx-bi-consents',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3
  );
  add_submenu_page(
    'aesirx-consent-management-plugin',
    'Consent Modal',
    'Consent Modal',
    'manage_options',
    'aesirx-cmp-modal',
    function () {
      ?>
      <h2 class="aesirx_heading">Consent Modal Management</h2>
      <div class="aesirx_consent_wrapper">
      <div class="form_wrapper">
        <form action="options.php" method="post">
          <?php
            settings_fields('aesirx_consent_modal_plugin_options');

            do_settings_sections('aesirx_consent_modal_plugin');
            wp_nonce_field('aesirx_analytics_settings_save', 'aesirx_analytics_settings_nonce');
          ?>
          <button type="submit" class="submit_button aesirx_btn_success">
            <?php
              echo wp_kses("
                <img width='20px' height='20px' src='". plugins_url( 'aesirx-consent/assets/images-plugin/save_icon.png')."' />
                ".esc_html__("Save settings", 'aesirx-consent')."
              ", aesirx_analytics_escape_html()); 
            ?>
          </button>
        </form>
      </div>
			<?php
        echo '</div>';
    },
    3);
  
  });

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'toplevel_page_aesirx-consent-management-plugin' || $hook === "aesirx-cmp_page_aesirx-cmp-modal") {
    wp_enqueue_script('aesirx_analytics_ckeditor', plugins_url('assets/vendor/aesirx-consent-ckeditor.js', __DIR__), array('jquery'), true, true);
    wp_register_script('aesirx_analytics_repeatable_fields', plugins_url('assets/vendor/aesirx-consent-repeatable-fields.js', __DIR__), array('jquery'), false, true);
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
      'txt_choose_how_we_use' => __( 'Choose how we use your data: "Reject" data collection, allow tracking ["Consent"], or use "Decentralized Consent" for more control over your personal data & rewards.', 'aesirx-consent' ),
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
      'txt_details' => __( "Details", 'aesirx-consent' )
  );
    wp_localize_script( 'aesirx_analytics_repeatable_fields', 'aesirx_analytics_translate', $translation_array );
    wp_enqueue_script('aesirx_analytics_repeatable_fields');
  }
  if ($hook === 'aesirx-cmp_page_aesirx-bi-consents' || $hook === 'aesirx-bi_page_aesirx-bi-consents') {

    $options = get_option('aesirx_analytics_plugin_options');

    $protocols = ['http://', 'https://'];
    $domain = str_replace($protocols, '', site_url());
    $streams = [['name' => get_bloginfo('name'), 'domain' => $domain]];
    $endpoint = get_bloginfo('url');

    $manifest = json_decode(
      file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
    );

    if ($manifest->entrypoints->bi->assets) {
      foreach ($manifest->entrypoints->bi->assets->js as $js) {
        wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
      }
    }

    $clientId = $options['clientid'];
    $clientSecret = $options['secret'];

    $jwt = '';

    wp_register_script( 'aesrix_bi_window', '', array(), '1.0', false );

    wp_enqueue_script('aesrix_bi_window');

    wp_add_inline_script(
      'aesrix_bi_window',
      'window.env = {};
		  window.aesirxClientID = "' . esc_html($clientId) . '";
		  window.aesirxClientSecret = "' . esc_html($clientSecret) . '";
      window.env.REACT_APP_BI_ENDPOINT_URL = "' . esc_url($endpoint) . '";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(' . wp_json_encode($streams) . ');
		  window.env.PUBLIC_URL= "' . esc_url(plugin_dir_url(__DIR__)) . '";
      window.env.STORAGE= "internal";
      ' . htmlspecialchars($jwt, ENT_NOQUOTES),
    );
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
  $domain = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field($_SERVER['SERVER_NAME']) : '';
  $args = array(
      'headers' => array(
          'Content-Type' => 'application/json',
      ),
      'body' => wp_json_encode( array(
        'domain' => $domain
      ) ),
  );

  $responsePost = wp_remote_post( $urlPost, $args);
  if ( $responsePost['response']['code'] === 200 ) {
    $checkTrialAfterPost = aesirx_analytics_get_api(
        'https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain=' . rawurlencode($domain)
    );
    $body = wp_remote_retrieve_body($checkTrialAfterPost);
    if(json_decode($body)->result->success) {
      $dateExpired = new DateTime(json_decode($body)->result->date_expired);
      $currentDate = new DateTime();
      $interval = $currentDate->diff($dateExpired);
      $daysLeft = $interval->days;
      return wp_kses(sprintf(__("Your trial license ends in %1\$s days. Please update new license <a href='%2\$s' target='_blank'>%2\$s</a>.", 'aesirx-consent'), $daysLeft, 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
    }
  } else {
    $error_message = $responsePost['response']['message'];
    return wp_kses(
        sprintf(
            __("Something went wrong: %1\$s. Please contact the administrator.", 'aesirx-consent'),
            $error_message,
        ),
        aesirx_analytics_escape_html()
    );
  }
}

function aesirx_analytics_license_info() {
  $options = get_option('aesirx_analytics_plugin_options', []);
  $domain = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field($_SERVER['SERVER_NAME']) : '';
  if (!empty($options['license'])) {
    $response = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPLicense&api=hal&license=' . $options['license']);
    $bodyCheckLicense = wp_remote_retrieve_body($response);
    $decodedDomains = json_decode($bodyCheckLicense)->result->domain_list->decoded ?? [];
    $domainList = array_column($decodedDomains, 'domain');

    if ($response['response']['code'] === 200 ) {
      if(!json_decode($bodyCheckLicense)->result->success || json_decode($bodyCheckLicense)->result->subscription_product !== "product-aesirx-cmp") {
        if($options['current_license']) {
          $options['current_license'] = '';
          update_option('aesirx_analytics_plugin_options', $options);
        }
        return  wp_kses(sprintf(__("Your license is expried or not found. Please update new license <a href='%1\$s' target='_blank'>%1\$s</a>.", 'aesirx-consent'), 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
      } else if(!in_array($domain, $domainList, true)) {
        if( $options['isDomainValid'] !== 'false') {
          $options['isDomainValid'] = 'false';
          $options['verify_domain'] = round(microtime(true) * 1000);
          update_option('aesirx_analytics_plugin_options', $options);
        }
        return  wp_kses(sprintf(__("Your domain is not match with your license. Please update domain in your license <a href='%1\$s' target='_blank'>%1\$s</a> and click <span class='verify_domain'>here</span> to verify again.", 'aesirx-consent'), 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
      } else {
        if($options['isDomainValid'] === 'false') {
          $options['isDomainValid'] = 'true';
          $options['verify_domain'] = round(microtime(true) * 1000);
          update_option('aesirx_analytics_plugin_options', $options);
        }
        $dateExpired = new DateTime(json_decode($bodyCheckLicense)->result->date_expired);
        $currentDate = new DateTime();
        $interval = $currentDate->diff($dateExpired);
        $daysLeft = $interval->days;
        return wp_kses(sprintf(__("Your license ends in %1\$s days. Please update new license <a href='%2\$s' target='_blank'>%2\$s</a>.", 'aesirx-consent'), $daysLeft, 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
      }
    } else {
      $error_message = $response['response']['message'];
      return wp_kses(
          sprintf(
              __("Check license failed: %1\$s. Please contact the administrator or update your license.", 'aesirx-consent'),
              $error_message,
          ),
          aesirx_analytics_escape_html()
      );
    }
  } else {
    $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.rawurlencode($domain));
    $body = wp_remote_retrieve_body($checkTrial);
    if($body) {
      if(json_decode($body)->result->success) {
        $dateExpired = new DateTime(json_decode($body)->result->date_expired);
        $currentDate = new DateTime();
        $interval = $currentDate->diff($dateExpired);
        $daysLeft = $interval->days;
        $hoursLeft = $interval->h;
        if ($daysLeft === 0) {
          $hoursLeft = max(1, $hoursLeft); // Ensure at least 1 hour is shown
          return wp_kses(
              sprintf(
                  __("Your trial license ends in %1\$s hour(s). Please update your license <a href='%2\$s' target='_blank'>here</a>.", 'aesirx-consent'),
                  $hoursLeft,
                  'https://aesirx.io/licenses'
              ),
              aesirx_analytics_escape_html()
          );
        }
        return wp_kses(sprintf(__("Your trial license ends in %1\$s days. Please update new license <a href='%2\$s' target='_blank'>%2\$s</a>.", 'aesirx-consent'), $daysLeft, 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
      } else {
        if(json_decode($body)->result->date_expired) {
          return wp_kses(sprintf(__("Your free trials has ended. Please update your license. <a href='%1\$s' target='_blank'>%1\$s</a>.", 'aesirx-consent'), 'https://aesirx.io/licenses'), aesirx_analytics_escape_html());
        } else {
          return aesirx_analytics_trigger_trial();
        }
      }
    }
  }
}

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
        'checked' => array(),
        'placeholder' => array(),
     ),
     'select' => array(
        'id'    => array(),
        'name'  => array(),
        'class' => array(),
      ),
      'option' => array(
        'name'  => array(),
        'value' => array(),
        'class' => array(),
        'selected' => array(),
      ),
     'strong' => array(),
     'a' => array(
      'href'  => array(),
      'target'    => array(),
      'class'    => array(),
      'download'    => array(),
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
     'h3' => array(
      'class' => array(),
     ),
     'ul' => array(
      'class' => array(),
     ),
     'li' => array(),
     'br' => array(),
     'label' => array(
      'for'  => array(),
      'class'  => array(),
     ),
     'img' => array(
      'src'  => array(),
      'class'  => array(),
      'width'  => array(),
      'height'  => array(),
     ),
     'iframe' => array(
      'src'  => array(),
     ),
     'div' => array(
        'id' => array(),
        'class' => array(),
     ),
     'button' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
    ),
    'textarea' => array(
      'id' => array(),
      'class' => array(),
      'rows' => array(),
      'cols' => array(),
      'readonly' => array(),
   ),
   'code' => array(
      'class' => array(),
   )
  );

  return $allowed_html;
}

function aesirx_generate_gpc_json() {
  $policy_url = get_option('aesirx_privacy_policy_url', get_site_url() . "/privacy-policy");

  $gpc_data = array(
      "gpc" => true,
      "policy_url" => esc_url($policy_url)
  );

  return json_encode($gpc_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
function allow_data_protocol($protocols) {
  $protocols[] = 'data'; // Add "data" to the allowed protocols list
  return $protocols;
}
add_filter('kses_allowed_protocols', 'allow_data_protocol');