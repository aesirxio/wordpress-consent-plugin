<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Start_Fingerprint extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $license = $options['license'];
        $verifyDomain = $options['verify_domain'];
        $serverName = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field($_SERVER['SERVER_NAME']) : '';
        $serverName = preg_replace('/^www\./', '', $serverName);
        $isTrial = false;
        if (!empty($license)) {
            $current_time = new DateTime('now', new DateTimeZone('UTC')); // Current time in UTC
            $expiry_time = new DateTime($options['license_date_expired'], new DateTimeZone('UTC'));
            if($license !== $options['current_license'] || $verifyDomain !== $options['current_verify_domain']) {
                $options['current_license'] = $license;
                $options['current_verify_domain'] = $verifyDomain;
                $options['require_change_license'] = true;
                update_option('aesirx_analytics_plugin_options', $options);
            }
            if($options['require_change_license'] || ($current_time > $expiry_time && !$options['license_expired'])) {
                $response = parent::aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPLicense&api=hal&license=' . $options['license']);
                $bodyCheckLicense = wp_remote_retrieve_body($response);
                $decodedDomains = json_decode($bodyCheckLicense)->result->domain_list->decoded ?? [];
                $domainList = array_column($decodedDomains, 'domain');
                $domainList = array_map(function ($d) {
                    return preg_replace('/^www\./', '', $d);
                }, $domainList);
                if ($response['response']['code'] === 200 ) {
                    $isTrial = json_decode($bodyCheckLicense)->result->isTrial ?? false;
                    if ($isTrial !== true) {
                        $options['require_change_license'] = false;
                        if(!json_decode($bodyCheckLicense)->result->success || json_decode($bodyCheckLicense)->result->subscription_product !== "product-aesirx-cmp") {
                            $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.rawurlencode($serverName));
                            $body = $checkTrial && wp_remote_retrieve_body($checkTrial);
                            if(!json_decode($body)->result->success) {
                                $options['license_expired'] = true;
                                update_option('aesirx_analytics_plugin_options', $options);
                                return new WP_Error('validation_error', esc_html__('License is expired or not found. Please update your license', 'aesirx-consent'));
                            } else {
                                $options['license_expired'] = false;
                                $options['license_date_expired'] = json_decode($body)->result->date_expired;
                            }
                        } else if (!in_array($serverName, $domainList, true)) {
                            $options['license_expired'] = true;
                            update_option('aesirx_analytics_plugin_options', $options);
                            return new WP_Error('validation_error', esc_html__('Your domain is not match with your license. Please update domain in your license', 'aesirx-consent'));
                        } else {
                            $options['license_date_expired'] = json_decode($bodyCheckLicense)->result->date_expired;
                            $options['license_expired'] = false;
                        }
                        update_option('aesirx_analytics_plugin_options', $options);
                    }
                } else {
                    $error_message = $response['response']['message'];
                    return new WP_Error(
                        'validation_error',
                        esc_html(
                            sprintf(
                                __('Check license failed: %1\$s. Please contact the administrator.', 'aesirx-consent'),
                                $error_message
                            )
                        )
                    );
                }
            } else if ($options['isDomainValid'] === 'false') {
                return new WP_Error('validation_error', esc_html__('Your domain is not match with your license. Please update domain in your license', 'aesirx-consent'));
            } else if ($options['license_expired']) {
                return new WP_Error('validation_error', esc_html__('License is expired or not found. Please update your license', 'aesirx-consent'));
            }
        }
        if(empty($license) || $isTrial) {
            $current_time = new DateTime('now', new DateTimeZone('UTC')); // Current time in UTC
            $expiry_time = new DateTime($options['trial_date_expired'], new DateTimeZone('UTC'));
            if (!$options['checked_trial'] || ($current_time > $expiry_time && !$options['trial_end'])) {
                $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.rawurlencode($serverName));
                $body = wp_remote_retrieve_body($checkTrial);
                $options['checked_trial'] = true;
                if(!json_decode($body)->result->success) {
                    $options['trial_end'] = true;
                    update_option('aesirx_analytics_plugin_options', $options);
                    return new WP_Error('validation_error', esc_html__('Your trial is ended. Please renew your license', 'aesirx-consent'));
                } else {
                    $options['trial_end'] = false;
                    $options['trial_date_expired'] = json_decode($body)->result->date_expired;
                }
                update_option('aesirx_analytics_plugin_options', $options);
            } else if ($options['trial_end']) {
                return new WP_Error('validation_error', esc_html__('Your trial is ended. Please renew your license', 'aesirx-consent'));
            }
            
        }

        $start = gmdate('Y-m-d H:i:s');
        $domain = parent::aesirx_analytics_validate_domain($params['request']['url']);

        if (is_wp_error($domain)) {
            return $domain;
        }

        $visitor = parent::aesirx_analytics_find_visitor_by_fingerprint_and_domain($params['request']['fingerprint'], $domain);

        if (is_wp_error($visitor)) {
            return $visitor;
        }

        if (!$visitor) {
            $new_visitor_flow = [
                'uuid' => wp_generate_uuid4(),
                'start' => $start,
                'end' => $start,
                'multiple_events' => false,
            ];
    
            $new_visitor = [
                'fingerprint' => $params['request']['fingerprint'],
                'uuid' => wp_generate_uuid4(),
                'ip' => $params['request']['ip'],
                'user_agent' => $params['request']['user_agent'],
                'device' => $params['request']['device'],
                'browser_name' => $params['request']['browser_name'],
                'browser_version' => $params['request']['browser_version'],
                'domain' => $domain,
                'lang' => $params['request']['lang'],
                'timezone' => $params['request']['timezone'],
                'visitor_flows' => [$new_visitor_flow],
            ];
    
            $new_visitor_event = [
                'uuid' => wp_generate_uuid4(),
                'visitor_uuid' => $new_visitor['uuid'],
                'flow_uuid' => $new_visitor_flow['uuid'],
                'url' => $params['request']['url'],
                'referer' => $params['request']['referer'],
                'start' => $start,
                'end' => $start,
                'event_name' => $params['request']['event_name'] ?? 'visit',
                'event_type' => $params['request']['event_type'] ?? 'action',
                'attributes' => isset($params['request']['attributes']) ? $params['request']['attributes'] : '',
            ];
    
            parent::aesirx_analytics_create_visitor($new_visitor);
            parent::aesirx_analytics_create_visitor_event($new_visitor_event);
    
            return [
                'visitor_uuid' => $new_visitor['uuid'],
                'event_uuid' => $new_visitor_event['uuid'],
                'flow_uuid' => $new_visitor_event['flow_uuid'],
            ];
        } else {
            $url = wp_parse_url($params['request']['url']);
            if (!$url || !isset($url['host'])) {
                return new WP_Error('validation_error', esc_html__('Wrong URL format, domain not found', 'aesirx-consent'));
            }
    
            if ($url['host'] !== $visitor['domain']) {
                return new WP_Error('validation_error', esc_html__('The domain sent in the new URL does not match the domain stored in the visitor document', 'aesirx-consent'));
            }
          
            global $wpdb;
            if (!$visitor['timezone']) {
                $updated_data['timezone'] = isset($params['request']['timezone']) ? $params['request']['timezone'] : '';
            }
            if (!$visitor['lang']) {
                $updated_data['lang'] = isset($params['request']['lang']) ? $params['request']['lang'] : '';
            }
            if (!empty($updated_data)) {
                $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prefix . 'analytics_visitors',
                    $updated_data,
                    ['uuid' => $visitor['uuid']],
                );
            }
    
            $create_flow = true;
            $visitor_flow = [
                'uuid' => wp_generate_uuid4(),
                'start' => $start,
                'end' => $start,
                'multiple_events' => false,
            ];
            $is_already_multiple = false;
    
            if ($params['request']['referer']) {
                $referer = wp_parse_url($params['request']['referer']);

                if ($referer && $referer['host'] === $url['host'] && $visitor['visitor_flows']) {

                    $list = $visitor['visitor_flows'];
    
                    if (!empty($list)) {
                        $first = $list[0];
                        $max = $first['start'];
                        $visitor_flow['uuid'] = $first['uuid'];
                        $is_already_multiple = $first['multiple_events'];
                        $create_flow = false;

                        foreach ($list as $val) {
                            if ($max < $val['start']) {
                                $max = $val['start'];
                                $visitor_flow['uuid'] = $val['uuid'];
                            }
                        }
                    }
                }
            }
    
            if ($create_flow) {
                parent::aesirx_analytics_create_visitor_flow($visitor['uuid'], $visitor_flow);
            }
    
            $visitor_event = [
                'uuid' => wp_generate_uuid4(),
                'visitor_uuid' => $visitor['uuid'],
                'flow_uuid' => $visitor_flow['uuid'],
                'url' => $params['request']['url'],
                'referer' => $params['request']['referer'],
                'start' => $start,
                'end' => $start,
                'event_name' => $params['request']['event_name'] ?? 'visit',
                'event_type' => $params['request']['event_type'] ?? 'action',
                'attributes' => $params['request']['attributes'] ?? '',
            ];
    
            parent::aesirx_analytics_create_visitor_event($visitor_event);

            if (!$create_flow && !$is_already_multiple) {
                parent::aesirx_analytics_mark_visitor_flow_as_multiple($visitor_flow['uuid']);
            }
    
            return [
                'visitor_uuid' => $visitor['uuid'],
                'event_uuid' => $visitor_event['uuid'],
                'flow_uuid' => $visitor_event['flow_uuid'],
            ];
        }
    }
}
