<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Datastream_Template extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $options = get_option('aesirx_analytics_plugin_options', []);
        return [
            'domain' => $options['datastream_domain'],
            'template' => $options['datastream_template'],
            'gtag_id' => $options['datastream_gtag_id'],
            'gtm_id' => $options['datastream_gtm_id'],
            'consent' => $options['datastream_consent'],
        ];
    }
}
