<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

include_once plugin_dir_path(__FILE__) . 'ConsentWebhook.php';

Class AesirX_Analytics_Revoke_Consent_Level5 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Validate and sanitize each parameter in the $params array
        $validated_params = [];
        foreach ($params as $key => $value) {
            $validated_params[$key] = sanitize_text_field($value);
        }

        global $wpdb;

        $expiration = gmdate('Y-m-d H:i:s');
        $visitor_uuid = $validated_params['visitor_uuid'];

        // Execute the update
        // doing direct database calls to custom tables
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prefix . 'analytics_visitor_consent',
            ['expiration' => $expiration],
            ['visitor_uuid' => $visitor_uuid, 'consent_uuid' => null],
            array('%s'),  // Data type for 'expiration'
            array('%s')   // Data type for 'visitor_uuid'
        );

        // Execute the update
        // doing direct database calls to custom tables
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prefix . 'analytics_visitors',
            [
                'ip' => '',
                'lang' => '',
                'browser_version' => '',
                'browser_name' => '',
                'device' => '',
                'user_agent' => '',
                'timezone' => ''
            ],
            ['uuid' => $visitor_uuid],
        );

        // Read previously stored category consent BEFORE clearing, so we can record
        // per-purpose denials in the GRC Suite webhook.
        $previous_categories = [];
        $stored_json = get_option('aesirx_analytics_plugin_options_disabled_block_domains', '');
        if (!empty($stored_json)) {
            $stored_items = json_decode($stored_json, true);
            if (is_array($stored_items)) {
                foreach ($stored_items as $item) {
                    if (!empty($item['category'])) {
                        $previous_categories[] = sanitize_text_field($item['category']);
                    }
                }
                $previous_categories = array_unique($previous_categories);
            }
        }

        update_option('aesirx_analytics_plugin_options_disabled_block_domains', '');

        if ($wpdb->last_error) {
            return new WP_Error($wpdb->last_error);
        }

        // Forward the consent withdrawal to GRC Suite via webhook
        if (AesirX_ComplianceOne_Webhook::is_enabled()) {
            $webhook = new AesirX_ComplianceOne_Webhook();
            $webhook->send(AesirX_ComplianceOne_Webhook::buildRevokePayload(
                $visitor_uuid,
                $validated_params,
                $previous_categories
            ));
        }

        return true;
    }
}