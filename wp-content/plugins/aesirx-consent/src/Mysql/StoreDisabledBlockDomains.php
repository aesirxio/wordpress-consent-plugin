<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Store_Disabled_Block_Domains extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;
        $response = [];
        $disabledCategories = [];

        if($params[1]) {
            $jsonBlockDomain = json_encode($params[1], JSON_PRETTY_PRINT);
            update_option('aesirx_analytics_plugin_options_disabled_block_domains', $jsonBlockDomain);
            foreach ($params[1] as $item) {
                if (!empty($item['category'])) {
                    $disabledCategories[] = $item['category'];
                }
            }
        } else {
            $jsonBlockDomain = '';
            update_option('aesirx_analytics_plugin_options_disabled_block_domains', '');
        }
        if($params[2] && $params[3]) {
              // Get the current date and time
            $now = gmdate('Y-m-d H:i:s');
            $uuid = sanitize_text_field($params[3]);
            $listCategory = $params[2];

            foreach ($listCategory as $category) {
                if($category !=='essential') {
                    $isRejected = in_array($category, $disabledCategories);

                    $data = [
                        'id'         => wp_generate_uuid4(),
                        'uuid'       => $uuid,
                        'datetime'   => $now,
                        'expiration' => null,
                        'allow'      => $isRejected ? null : $category,
                        'reject'     => $isRejected ? $category : null,
                    ];
                    // Conditionally add consent_uuid
                    $data_types = array_fill(0, count($data), '%s');
                   
                    $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                        $wpdb->prefix . 'analytics_category_consent',
                        $data,
                        $data_types
                    );
                } else {
                    continue;
                }
            }
        }
        $response['disabled_block_domains'] = sanitize_text_field($jsonBlockDomain);
        return $response;
    }
}
