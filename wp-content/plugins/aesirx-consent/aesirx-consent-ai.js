jQuery(document).ready(function ($) {
  const endpoint = document.location.origin;

  fetchHistory();

  $('.advisor_chat_footer_send').click(function () {
    sendToAssistant();
  });

  $('.advisor_chat_footer_textarea').on('keydown', function (e) {
    if (e.key === 'Enter') {
      sendToAssistant();
    }
  });

  async function fetchHistory() {
    $('.advisor_chat_loading').addClass('show');
    const res = await fetch(`${endpoint}/openai-assistant`);
    const data = await res.json();
    console.log('data', data);
    renderMessages(data?.data?.messages || []);
    $('.advisor_chat_loading').removeClass('show');
  }

  async function sendToAssistant() {
    const $log = $('.advisor_chat_message_container');
    $('.advisor_chat_loading_text').addClass('show');
    const input = $('.advisor_chat_footer_textarea');
    const button = $('.advisor_chat_footer_send');
    input.prop('disabled', true);
    button.prop('disabled', true);
    const message = input.val();
    if (!message) return;
    const userMessageAppend = `
      <div class="advisor_chat_user_message">
        <div class="advisor_chat_message_avatar">
          <img width='40px' height='40px' src="/wp-content/plugins/aesirx-consent/assets/images-plugin/user_avatar.png" />
        </div>
        <div class="advisor_chat_message_content">
          <div class="advisor_chat_message_content_name">
            User
          </div>
          <div class="advisor_chat_message_content_message">
            ${message}
          </div> 
        </div>
      </div>
    `;
    $log.append(userMessageAppend);
    $log.scrollTop($log.prop('scrollHeight'));
    const res = await fetch(`${endpoint}/openai-assistant`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: message,
      }),
    });

    const data = await res.json();
    renderMessages(data?.data?.messages || []);
    input.val('');
    input.prop('disabled', false);
    button.prop('disabled', false);
    $('.advisor_chat_loading_text').removeClass('show');
  }
  function renderMessages(messages) {
    const $log = $('.advisor_chat_message_container');
    $log.empty();
    messages.forEach((msg, index) => {
      const role = msg.role;
      const content = msg.content.find((item) => item.type === 'text');
      const messageText = extractMessageText(msg.content);
      const isLastMessage = index === messages.length - 1;
      const attachments = msg.attachments;

      const html = renderMessagePair({
        userMessage: role === 'user' ? messageText : null,
        assistantMessage: role === 'assistant' ? messageText : null,
        isLastMessage,
        attachments,
        content,
      });

      $log.append(html);
    });
    $log.scrollTop($log.prop('scrollHeight'));
  }

  function renderMessagePair({ userMessage, assistantMessage, attachments }) {
    let html = '';

    if (userMessage) {
      const formatted = replaceJsonInString(userMessage);
      html += `
        <div class="advisor_chat_user_message">
          <div class="advisor_chat_message_avatar">
            <img width='40px' height='40px' src="/wp-content/plugins/aesirx-consent/assets/images-plugin/user_avatar.png" />
          </div>
          <div class="advisor_chat_message_content">
            <div class="advisor_chat_message_content_name">
              User
            </div>
            <div class="advisor_chat_message_content_message">
              ${formatted}
            </div> 
            ${renderAttachments(attachments)}
          </div>
        </div>
      `;
    }

    if (assistantMessage) {
      html += `
        <div class="advisor_chat_assistant_message">
          <div class="advisor_chat_message_avatar">
            <img width='40px' height='40px' src="/wp-content/plugins/aesirx-consent/assets/images-plugin/advisor_avatar.png" />
          </div>
          <div class="advisor_chat_message_content">
            <div class="advisor_chat_message_content_name">
            Privacy Assistant
            </div>
            <div class="advisor_chat_message_content_message">
              ${assistantMessage}
            </div> 
          </div>
        </div>
      `;
    }

    return html;
  }

  function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) return '';
    return (
      `<div class="attachments">` +
      attachments
        .map(
          (att) =>
            `<a href="https://platform.openai.com/storage/files/${
              att.file_id
            }" target="_blank">${att.name || att.file_id}</a>`
        )
        .join('<br/>') +
      `</div>`
    );
  }

  function extractMessageText(content) {
    const textItem = content.find((item) => item.type === 'text');
    return textItem?.text?.value || '';
  }

  function replaceJsonInString(inputString) {
    let startIndex = inputString?.indexOf('{');
    let endIndex = inputString?.lastIndexOf('}');
    if (inputString?.length > 50 && startIndex !== -1 && endIndex !== -1) {
      return inputString?.slice(0, startIndex) + '' + inputString?.slice(endIndex + 1);
    } else {
      return inputString;
    }
  }
});
