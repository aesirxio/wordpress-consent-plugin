<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Start_Fingerprint extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $license = $options['license'];
        if (!empty($license)) {
            $response = parent::aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPLicense&api=hal&license=' . $options['license']);
            $bodyCheckLicense = wp_remote_retrieve_body($response);
            if ($response['response']['code'] === 200 ) {
                if(!json_decode($bodyCheckLicense)->result->success || json_decode($bodyCheckLicense)->result->subscription_product !== "product-aesirx-cmp") {
                    $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.$_SERVER['SERVER_NAME']);
                    $body = $checkTrial && wp_remote_retrieve_body($checkTrial);
                    if(!json_decode($body)->result->success) {
                        return new WP_Error('validation_error', esc_html__('License is expired or not found. Please update your license', 'aesirx-consent'));
                    }
                }
            } else {
                $error_message = $response['response']['message'];
                return new WP_Error('validation_error', esc_html__(sprintf(
                    __('Check license failed: %s. Please contact the administrator', 'aesirx-analytics'),
                    $error_message
                  )));
            }
        } else {
            $checkTrial = aesirx_analytics_get_api('https://api.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=member&task=validateWPDomain&api=hal&domain='.$_SERVER['SERVER_NAME']);
            $body = wp_remote_retrieve_body($checkTrial);
            if(!json_decode($body)->result->success) {
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
    
            if ($url['host'] != $visitor['domain']) {
                return new WP_Error('validation_error', esc_html__('The domain sent in the new URL does not match the domain stored in the visitor document', 'aesirx-consent'));
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

                if ($referer && $referer['host'] == $url['host'] && $visitor['visitor_flows']) {

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
