<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Openai_Assistant extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $response = [];
        $optionsAIKey = get_option('aesirx_consent_ai_key_plugin_options', []);
        $assistant_id = $optionsAIKey['openai_assistant'];
        $user_message = sanitize_text_field($params[1]);

        $authorization = 'Bearer '. $optionsAIKey['openai_key'];
        
        $headers = [
            'Authorization' => $authorization,
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ];
         // 1. Get or create thread_id
        $options = get_option('aesirx_consent_ai_plugin_options');
        $thread_id = $options['thread_id'];
        $updateThread = $options['update_thread'];
        if (!$thread_id || $updateThread !== '1.9.0') {
            $response = wp_remote_post('https://aesirxopenai.openai.azure.com/openai/threads?api-version=2024-12-01-preview', [
                'headers' => $headers,
                'body' => '{}',
            ]);
            $thread_data = json_decode(wp_remote_retrieve_body($response), true);
            $thread_id = $thread_data['id'];
            $options['thread_id'] = $thread_id;
            $options['update_thread'] = '1.9.0';
            update_option('aesirx_consent_ai_plugin_options', $options);
        }

        // 2. Add user message
        wp_remote_post("https://aesirxopenai.openai.azure.com/openai/threads/{$thread_id}/messages?api-version=2024-12-01-preview", [
            'headers' => $headers,
            'body' => json_encode([
                'role' => 'user',
                'content' => $user_message,
            ]),
        ]);

        // 3. Run assistant
        $runRes = wp_remote_post("https://aesirxopenai.openai.azure.com/openai/threads/{$thread_id}/runs?api-version=2024-12-01-preview", [
            'headers' => $headers,
            'body' => json_encode(['assistant_id' => $assistant_id]),
        ]);
        $run = json_decode(wp_remote_retrieve_body($runRes), true);
        $run_id = $run['id'];

        // 4. Poll until run completes
        $status = 'queued';
        while (in_array($status, ['queued', 'in_progress'])) {
            sleep(1);
            $statusRes = wp_remote_get("https://aesirxopenai.openai.azure.com/openai/threads/{$thread_id}/runs/{$run_id}?api-version=2024-12-01-preview", [
                'headers' => $headers,
            ]);
            $statusData = json_decode(wp_remote_retrieve_body($statusRes), true);
            $status = $statusData['status'];
        }

        // 5. Get all messages
        $msgRes = wp_remote_get("https://aesirxopenai.openai.azure.com/openai/threads/{$thread_id}/messages?api-version=2024-12-01-preview", [
            'headers' => $headers,
        ]);
        $msgData = json_decode(wp_remote_retrieve_body($msgRes), true);

        // Format messages
        $messages = array_map(function ($msg) {
            return [
                'role' => $msg['role'],
                'content' => str_replace('"', "'", $msg['content'][0]['text']['value']),
            ];
        }, array_reverse($msgData['data'])); // oldest to newest

        return rest_ensure_response([
            'thread_id' => $thread_id,
            'messages' => $messages,
        ]);
    }
}
