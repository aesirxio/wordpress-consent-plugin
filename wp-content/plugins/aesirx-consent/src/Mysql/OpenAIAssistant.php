<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Openai_Assistant extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $response = [];
        $assistant_id = '';
        $user_message = sanitize_text_field($params[1]);

        $authorization = 'Bearer ';
        
        $headers = [
            'Authorization' => $authorization,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];
         // 1. Get or create thread_id
        $thread_id = get_option('openai_assistant_thread_id');
        if (!$thread_id) {
            $response = wp_remote_post('https://api.openai.com/v1/threads', [
                'headers' => $headers,
                'body' => '{}',
            ]);
            $thread_data = json_decode(wp_remote_retrieve_body($response), true);
            $thread_id = $thread_data['id'];
            update_option('openai_assistant_thread_id', $thread_id);
        }

        // 2. Add user message
        wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
            'body' => json_encode([
                'role' => 'user',
                'content' => $user_message,
            ]),
        ]);

        // 3. Run assistant
        $runRes = wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/runs", [
            'headers' => $headers,
            'body' => json_encode(['assistant_id' => $assistant_id]),
        ]);
        $run = json_decode(wp_remote_retrieve_body($runRes), true);
        $run_id = $run['id'];

        // 4. Poll until run completes
        $status = 'queued';
        while (in_array($status, ['queued', 'in_progress'])) {
            sleep(1);
            $statusRes = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}", [
                'headers' => $headers,
            ]);
            $statusData = json_decode(wp_remote_retrieve_body($statusRes), true);
            $status = $statusData['status'];
        }

        // 5. Get all messages
        $msgRes = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
        ]);
        $msgData = json_decode(wp_remote_retrieve_body($msgRes), true);

        // Format messages
        $messages = array_map(function ($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }, array_reverse($msgData['data'])); // oldest to newest

        return rest_ensure_response([
            'thread_id' => $thread_id,
            'messages' => $messages,
        ]);
    }
}
