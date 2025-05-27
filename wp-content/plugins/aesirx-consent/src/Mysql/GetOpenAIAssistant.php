<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Openai_Assistant extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $thread_id = get_option('openai_assistant_thread_id');
        if (!$thread_id) {
            return rest_ensure_response(['messages' => []]);
        }

        $authorization = 'Bearer ';
        $headers = [
            'Authorization' => $authorization,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];
        $msgRes = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
        ]);
        $msgData = json_decode(wp_remote_retrieve_body($msgRes), true);

        $messages = array_map(function ($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }, array_reverse($msgData['data']));

        return rest_ensure_response([
            'thread_id' => $thread_id,
            'messages' => $messages,
        ]);
    }
}
