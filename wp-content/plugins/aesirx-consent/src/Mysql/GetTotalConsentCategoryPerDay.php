<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Category_Per_Day extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_category_consent_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            DATE(category_consent.datetime) AS date,
             -- Allow counts
            SUM(CASE WHEN category_consent.allow = 'analytics' THEN 1 ELSE 0 END) AS allow_analytics,
            SUM(CASE WHEN category_consent.allow = 'advertising' THEN 1 ELSE 0 END) AS allow_advertising,
            SUM(CASE WHEN category_consent.allow = 'functional' THEN 1 ELSE 0 END) AS allow_functional,
            SUM(CASE WHEN category_consent.allow = 'custom' THEN 1 ELSE 0 END) AS allow_custom,
            
            -- Reject counts
            SUM(CASE WHEN category_consent.reject = 'analytics' THEN 1 ELSE 0 END) AS reject_analytics,
            SUM(CASE WHEN category_consent.reject = 'advertising' THEN 1 ELSE 0 END) AS reject_advertising,
            SUM(CASE WHEN category_consent.reject = 'functional' THEN 1 ELSE 0 END) AS reject_functional,
            SUM(CASE WHEN category_consent.reject = 'custom' THEN 1 ELSE 0 END) AS reject_custom

          FROM `#__analytics_category_consent` AS category_consent
          LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = category_consent.uuid
          WHERE " . implode(" AND ", $where_clause) . "
          GROUP BY DATE(category_consent.datetime)";

        $total_sql = "";

        // $sort = self::aesirx_analytics_add_sort($params, ["date", "total"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
