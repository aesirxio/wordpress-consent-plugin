<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Openai_Assistant extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $options = get_option('aesirx_consent_ai_plugin_options');
        $thread_id = $options['thread_id'];
        if (!$thread_id) {
            return rest_ensure_response(['messages' => []]);
        }
        $optionsAIKey = get_option('aesirx_consent_ai_key_plugin_options', []);

        $authorization = 'Bearer '. $optionsAIKey['openai_key'];
        $headers = [
            'Authorization' => $authorization,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];
        $msgRes = wp_remote_get("https://aesirxopenai.openai.azure.com/openai/threads/{$thread_id}/messages", [
            'headers' => $headers,
        ]);
        $msgData = json_decode(wp_remote_retrieve_body($msgRes), true);

        $messages = array_map(function ($msg) {
            return [
                'role' => $msg['role'],
                'content' => str_replace('"', "'", $msg['content'][0]['text']['value']),
            ];
        }, array_reverse($msgData['data']));

        return rest_ensure_response([
            'thread_id' => $thread_id,
            'messages' => $messages,
        ]);
    }
}
