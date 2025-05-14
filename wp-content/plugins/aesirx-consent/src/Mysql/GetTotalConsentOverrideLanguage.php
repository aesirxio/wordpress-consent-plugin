<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Override_Language extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $where_clause_2 = [];
        $bind = [];

        parent::aesirx_analytics_add_region_consent_filters($params, $where_clause, $bind, $where_clause_2);
        $sql = 
            "SELECT
                COUNT(DISTINCT CASE
                WHEN c.override_language IS NOT NULL AND c.override_language != ''
                THEN CONCAT(c.visitor_uuid, '|', c.datetime)
                END) AS user_override,
                
                COUNT(DISTINCT CASE
                WHEN (c.override_language IS NULL OR c.override_language = '')
                THEN CONCAT(c.visitor_uuid, '|', c.datetime)
                END) AS not_override,
            
                COUNT(DISTINCT CONCAT(c.visitor_uuid, '|', c.datetime)) AS total_consent
            FROM
                `#__analytics_visitor_consent` c
            JOIN
              wp_analytics_visitors v ON c.visitor_uuid = v.uuid
            WHERE " . implode(" AND ", $where_clause) ."";

        $total_sql = "";

        // $sort = self::aesirx_analytics_add_sort($params, ["date", "total"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
