jQuery(document).ready(async function ($) {
  const endpoint = document.location.origin;
  const resBeforeConsent = fetch(
    `https://aesirx.io/api/services/result?url=consent.demo.aesirx.io`
  );

  const resAfterConsent = fetch(
    `https://aesirx.io/api/services/consentresult?url=consent.demo.aesirx.io`
  );

  const responses = await Promise.all([resBeforeConsent, resAfterConsent]);

  const data = await Promise.all(responses.map((res) => res.json()));

  const beforeCookies = data[0]?.cookies?.map((item) => {
    return {
      name: item?.name,
      domain: item?.domain,
      expiresDays: item?.expiresDays,
    };
  });
  const afterCookies = data[1]?.cookies?.map((item) => {
    return {
      name: item?.name,
      domain: item?.domain,
      expiresDays: item?.expiresDays,
    };
  });
  const cookieData = {
    site_url: 'https://consent.demo.aesirx.io',
    cookies_pre_consent: beforeCookies,
    cookies_post_consent: afterCookies,
  };
  const cookie_declaration_prompt = `
  You are a privacy compliance assistant specializing in GDPR, ePrivacy, and global data privacy laws. Generate a legally accurate cookie declaration based on the input JSON of scanned cookies and services. Cookies should be categorized, and include name, domain, purpose, duration, and provider. Assume a consent-based model (prior consent unless strictly necessary). This declaration should only include cookies-not beacons or tracking pixels.
  Format the output in Markdown/HTML for WordPress and ensure it's readable and editable by the site admin. Indicate consent is required for all cookies except strictly necessary ones.
  ${JSON.stringify(cookieData, null, 2)}
  Additions: Explicit instruction: Exclude beacons and pixels. Emphasize consent requirements clearly in the text output. Tables remain the same for cookie display.
  `;
  const privacy_policy_prompt = `
  You are a privacy legal expert assistant tasked with generating privacy policies for websites. Use the provided scan data, services, and jurisdiction to create a GDPR/CCPA/ePrivacy-compliant privacy policy.
  Include the following: Identity of the data controller, Types of personal data collected (incl. data from beacons and tracking pixels), Use of cookies and similar technologies, Legal basis for processing, Third-party services involved, Data retention periods, Sharing of data (who receives it and why), User rights, International transfers, A disclaimer that the policy is a draft and must be reviewed.
  ${JSON.stringify(cookieData, null, 2)}
  Additions: Explicit mention and inclusion of beacons and tracking pixels. Add a draft disclaimer in the generated policy. Ensure third-party data sharing is addressed, with optional placeholder to list vendors. Highlight user rights under GDPR/CCPA.
  `;
  const consent_request_prompt = `
  You are a privacy user experience expert specializing in global privacy compliance (GDPR, ePrivacy, CCPA). Generate a clear, user-friendly consent request message for a website. The message must explain cookie usage, mention beacons/tracking pixels, and clearly state which data types are collected before and after consent.
  Do not include buttons or UI - just the message. Ensure that the user understands: Types of tracking technologies used (cookies + beacons), The purpose and legal basis for their use, The user’s ability to control or withdraw consent, Links to Privacy Policy and Cookie Declaration (assume they exist as WP pages).
  ${JSON.stringify(cookieData, null, 2)}
  Additions: Introduce beacons in the message (same category as cookies but separately acknowledged). Include language like: “We also use technologies like beacons and tracking pixels to measure engagement and improve services.”, Add placeholder link text: “You can read more in our [Privacy Policy] and [Cookie Declaration].”
  `;

  $('.ai_generate_button').click(async function () {
    $(this).prop('disabled', true);
    $(this).find('.loader').addClass('show');

    $('#cookie_declaration .prompt_item_result .loading').addClass('show');
    $('#privacy_policy .prompt_item_result .loading').addClass('show');
    $('#consent_request .prompt_item_result .loading').addClass('show');
    await generateAI(cookie_declaration_prompt, 'cookie_declaration');
    await generateAI(privacy_policy_prompt, 'privacy_policy');
    await generateAI(consent_request_prompt, 'consent_request');
    $(this).find('.loader').removeClass('show');
    $(this).prop('disabled', false);
    $(this).addClass('hide');
  });

  $('.prompt_item_regenerate').click(async function () {
    $('.prompt_item_regenerate').each(function () {
      $(this).prop('disabled', true);
      $(this).find('.loader').addClass('show');
    });
    const id = $(this).parent('.prompt_item').attr('id');
    const prompt =
      id === 'cookie_declaration'
        ? cookie_declaration_prompt
        : id === 'privacy_policy'
          ? privacy_policy_prompt
          : consent_request_prompt;
    await generateAI(prompt, id);
    $('.prompt_item_regenerate').each(function () {
      $(this).prop('disabled', false);
      $(this).find('.loader').removeClass('show');
    });
  });

  async function generateAI(prompt, id) {
    $(`#${id} .prompt_item_result .loading`).addClass('show');
    const openai_result = await fetch(`${endpoint}/openai-assistant`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: prompt,
      }),
    });
    const openai_json = await openai_result.json();
    const openai_content = marked.parse(
      openai_json?.data?.messages[openai_json?.data?.messages?.length - 1]?.content
    );
    const threadID = openai_json?.data?.thread_id;
    $(`#${id} .prompt_item_result .result`).html(`${openai_content}`);
    const newOptions = {
      thread_id: threadID ?? aesirx_ajax.thread_id,
      [id]: openai_content,
    };
    await updateAesirxOptions(newOptions);
    if (!aesirx_ajax[id] || aesirx_ajax[id] === '') {
      aesirx_ajax[id] = openai_content;
    }
    $(`#${id} .prompt_item_result .loading`).removeClass('show');
    $(`#${id} .prompt_item_regenerate`).removeClass('hide');
  }

  async function updateAesirxOptions(options) {
    const cookie_declaration = aesirx_ajax.cookie_declaration;
    const privacy_policy = aesirx_ajax.privacy_policy;
    const consent_request = aesirx_ajax.consent_request;
    const formattedOptions = {
      ...(cookie_declaration ? { cookie_declaration: cookie_declaration } : {}),
      ...(privacy_policy ? { privacy_policy: privacy_policy } : {}),
      ...(consent_request ? { consent_request: consent_request } : {}),
      ...options,
    };
    jQuery.ajax({
      url: aesirx_ajax.ajax_url,
      method: 'POST',
      data: {
        action: 'update_aesirx_options',
        security: aesirx_ajax.nonce,
        options: formattedOptions,
      },
      success: function (response) {
        if (response.success) {
          console.log('Options updated successfully');
        } else {
          console.error('Failed to update options');
        }
      },
      error: function (error) {
        console.error('AJAX error:', error);
      },
    });
  }
});
