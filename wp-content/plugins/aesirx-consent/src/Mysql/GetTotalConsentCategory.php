<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Category extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_category_consent_filters($params, $where_clause, $bind);
        $sql =
        "SELECT
        category,
            SUM(allow_count) AS allow,
            SUM(reject_count) AS reject,
            SUM(allow_count + reject_count) AS total
        FROM (
            -- Count from allow column
            SELECT category_consent.allow AS category, COUNT(*) AS allow_count, 0 AS reject_count
            FROM `#__analytics_category_consent` AS category_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = category_consent.uuid 
            WHERE " . implode(' AND ', $where_clause) ." AND category_consent.allow IS NOT NULL
            GROUP BY category_consent.allow
        
            UNION ALL
        
            -- Count from reject column
            SELECT category_consent.reject AS category, 0 AS allow_count, COUNT(*) AS reject_count
            FROM `#__analytics_category_consent` AS category_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = category_consent.uuid 
            WHERE " . implode(' AND ', $where_clause) ." AND category_consent.reject IS NOT NULL
            GROUP BY category_consent.reject
       
        ) AS combined
        GROUP BY category
        ORDER BY category
        ";
        $total_sql = "";
        // $sort = self::aesirx_analytics_add_sort($params, ["tier", "total"], "tier");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], array_merge($bind, $bind));
    }
}
