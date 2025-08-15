<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Statement extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
     
        $optionsConsentVerify = get_option('aesirx_consent_verify_plugin_options', []);
        $ageCheck = $optionsConsentVerify['age_check'];
        $countryCheck = $optionsConsentVerify['country_check'];
        $minimumAge = isset($optionsConsentVerify['minimum_age']) ? (int)$optionsConsentVerify['minimum_age'] : 0;
        $maximumAge = isset($optionsConsentVerify['maximum_age']) ? (int)$optionsConsentVerify['maximum_age'] : 150;
        $allowedCountries = $optionsConsentVerify['allowed_countries'] ?? [];
        $disallowedCountries = $optionsConsentVerify['disallowed_countries'] ?? [];
        $response = [];
        if ($countryCheck === "countryCheck") {
            if (!empty($allowedCountries)) {
                $countrySet = array_values($allowedCountries);
                $type = "AttributeInSet";
            } elseif (!empty($disallowedCountries)) {
                $countrySet = array_values($disallowedCountries);
                $type = "AttributeNotInSet";
            }
            if(!empty($countrySet)) {
                $response[] = [
                    "type" => $type,
                    "attributeTag" => "nationality",
                    "set" => $countrySet,
                ];
            }
        }
        
        if ($ageCheck === "ageCheck") {
            $today = new DateTime();
            $lowerDate = $today->sub(new DateInterval("P{$maximumAge}Y"))->format('Ymd');
            $today->add(new DateInterval("P{$maximumAge}Y"));
            $upperDate = $today->sub(new DateInterval("P{$minimumAge}Y"))->format('Ymd');
            $today->add(new DateInterval("P{$minimumAge}Y"));
            $response[] = [
                "type" => "AttributeInRange",
                "attributeTag" => "dob",
                "lower" => $lowerDate,
                "upper" => $upperDate,
            ];
        }
      
        return  $response;
    }
}
