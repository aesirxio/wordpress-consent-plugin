jQuery(document).ready(async function ($) {
  const endpoint = document.location.origin;
  const host = document.location.host;
  const resBeforeConsent = fetch(`https://aesirx.io/api/services/result?url=${host}`);

  const resAfterConsent = fetch(`https://aesirx.io/api/services/consentresult?url=${host}`);

  const responses = await Promise.all([resBeforeConsent, resAfterConsent]);

  const data = await Promise.all(
    responses.map((res) => {
      return res?.status === 200 ? res?.json() : [];
    })
  );

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
  if (!afterCookies) {
    $('.domain_categorization_buttons').after(
      "<div class='error' style='color:red'>Your site not having scanned result yet. Please contact Aesirx team to perform scan.</div>"
    );
    $(
      '.domain_categorization_buttons .auto_populated, .domain_categorization_buttons .prompt_item_regenerate'
    ).prop('disabled', true);
  }
  const cookieData = {
    site_url: endpoint,
    cookies_pre_consent: beforeCookies,
    beacons_pre_consent: data[0]?.hosts?.beacons?.thirdParty,
    cookies_post_consent: afterCookies,
    beacons_post_consent: data[1]?.hosts?.beacons?.thirdParty,
  };
  const cookie_declaration_prompt = `
  You are a privacy compliance assistant specializing in GDPR, ePrivacy, and global data privacy laws. Generate a legally accurate cookie declaration based on the input JSON of scanned cookies and services. Cookies should be categorized, and include name, domain, purpose, duration, and provider. Assume a consent-based model (prior consent unless strictly necessary). This declaration should only include cookies-not beacons or tracking pixels.
  Indicate consent is required for all cookies except strictly necessary ones.
  ${JSON.stringify(cookieData, null, 2)}
  Additions: Explicit instruction: Exclude beacons and pixels. Emphasize consent requirements clearly in the text output. Tables remain the same for cookie display.
  `;
  const privacy_policy_prompt = `
  You are a privacy legal expert assistant tasked with generating privacy policies for websites. Use the provided scan data, services, and jurisdiction to create a GDPR/CCPA/ePrivacy-compliant privacy policy.
  Include the following: Identity of the data controller, Types of personal data collected (incl. data from beacons and tracking pixels), Use of cookies and similar technologies, Legal basis for processing, Third-party services involved, Data retention periods, Sharing of data (who receives it and why), User rights, International transfers, A disclaimer that the policy is a draft and must be reviewed.
  ${JSON.stringify(cookieData, null, 2)}
  Additions: Explicit mention and inclusion of beacons and tracking pixels. Add a draft disclaimer in the generated policy. Ensure third-party data sharing is addressed, with optional placeholder to list vendors. Highlight user rights under GDPR/CCPA. Remove phone number since don't need it.
  `;
  const consent_request_prompt = `
    You are a privacy user experience expert specializing in GDPR, ePrivacy, and CCPA. Generate a SHORT, clear consent text for a website based on the scan results provided in JSON.
    Input JSON: ${JSON.stringify(cookieData, null, 2)}
    The text must answer exactly these 3 questions, in this order:
      1. What are we tracking?
      2. Why are we tracking it (purpose)?
      3. Who do we share the data with / who provides the tools?
    Global rules:
      - 140-180 words.
      - Plain language, privacy-first.
      - Output as a single paragraph with no line breaks. Do not format as a list.
      - Do not include buttons or UI.
      - Do not use the words “may”, “might”, “can”, “could” when describing tracking after consent. Use present tense: “we use…”, “we load…”
      - State that consent can be given and revoked on a granular basis by customizing the settings.
      - Do not mention or hint that a scanner, scan, detection script, or technical inspection was used. Describe tracking as part of the site’s normal operation.
      - Always end with: “You can read more in our [Privacy Policy] and [Cookie Declaration].”
      - Use only what is present or clearly inferable from the input lists.
    Logic:
      A) If BOTH cookies_post_consent and beacons_post_consent are empty:
      Write a necessary/functional-only consent text, for example:
        “We use cookies and similar technologies that are necessary to operate and secure this website. These are first-party or essential services only. We do not load analytics or marketing tools without your consent. Data is processed by us and, where needed, by our hosting/technical service providers. You can change your choice at any time, and you can do it granularly by customizing the settings. You can read more in our [Privacy Policy] and [Cookie Declaration].”
      If cookies_pre_consent or beacons_pre_consent have entries, mention they are used only for essential / first-party operation.
      B) If EITHER cookies_post_consent OR beacons_post_consent is non-empty:
        1. Pre-consent statement
          - “Before you give consent, we use only the cookies and technologies needed for this site to work, including essential first-party items.”
        2. Post-consent statement (deterministic, no ‘may’)
          - “After you give consent, we load additional tracking used on this site to keep it functional, understand where visitors come from, measure visits and site performance, and display embedded content.”
        3. Provider naming – safe mapping
          - If domain contains pixel.wp.com or stats.wp.com → call it “WordPress/Automattic statistics services”.
          - If domain contains youtube → call it “YouTube no-cookie delivery” or “YouTube video delivery (no-cookie mode)”.
          - If cookie name starts with sbjs_ → call it “source and campaign attribution cookies”.
          - If domain contains google → call it “Google measurement services”.
          - If domain contains facebook / meta / doubleclick → call it “Meta/advertising services”.
          - For all other domains/names that cannot be confidently classified, end with: “and selected service providers.”
        4. “Who” sentence
          - Name the identifiable providers from the domains/cookie names in the input, then close with: “and selected service providers.”
        5. Control sentence
          - “You can change or withdraw your consent at any time, and you can do it granularly by customizing the settings.”
        6. Links
          - “You can read more in our [Privacy Policy] and [Cookie Declaration].”`;

  let pluginItems = [];

  $('.list_plugin_item').each(function () {
    const name = $(this).text().trim();
    const value = $(this).attr('id');

    pluginItems.push({ name, value });
  });
  const domain_categorization_prompt = `
  This site having the following plugins: ${JSON.stringify(pluginItems)}.
  Base on this domain and beacon from cookies_post_consent, beacons_post_consent list: ${JSON.stringify(
    cookieData,
    null,
    2
  )}.
  Arrange each domain/beacon it into plugins.
  List each domain/beacon into 5 categories Essential, Functional, Analytics, Advertising, Custom.
  And for each plugin please add domain and add should blocked or not.
  Only return domain/beacon not same as ${host}.
  Please return explaination for each domain. Not return JSON.
  `;

  $('.ai_generate_button').click(async function () {
    $(this).prop('disabled', true);
    $(this).find('.loader').addClass('show');

    $('#cookie_declaration .prompt_item_result .loading').addClass('show');
    $('#privacy_policy .prompt_item_result .loading').addClass('show');
    $('#consent_request .prompt_item_result .loading').addClass('show');
    $('#domain_categorization .prompt_item_result .loading').addClass('show');
    await generateAI(cookie_declaration_prompt, 'cookie_declaration');
    await generateAI(privacy_policy_prompt, 'privacy_policy');
    await generateAI(consent_request_prompt, 'consent_request');
    await generateAI(domain_categorization_prompt, 'domain_categorization');
    $(this).find('.loader').removeClass('show');
    $(this).prop('disabled', false);
    $(this).addClass('hide');
  });

  $('.prompt_item_regenerate').click(async function () {
    $('.prompt_item_regenerate, .auto_populated').each(function () {
      $(this).prop('disabled', true);
      $(this).find('.loader').addClass('show');
    });
    const id = $(this).closest('.prompt_item').attr('id');
    const prompt =
      id === 'cookie_declaration'
        ? cookie_declaration_prompt
        : id === 'privacy_policy'
          ? privacy_policy_prompt
          : id === 'consent_request'
            ? consent_request_prompt
            : domain_categorization_prompt;
    await generateAI(prompt, id);
    $('.prompt_item_regenerate, .auto_populated').each(function () {
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
      update_thread: '1.9.0',
      [id]: openai_content,
    };
    await updateAesirxOptions(newOptions);
    aesirx_ajax[id] = openai_content;
    $(`#${id} .prompt_item_result .loading`).removeClass('show');
    $(`#${id} .prompt_item_regenerate, #${id} .auto_populated`).removeClass('hide');
    if (id === 'domain_categorization') {
      $('.auto_populated').prop('disabled', false);
      $('#domain_categorization .error').remove();
    }
  }

  $('.auto_populated').click(async function () {
    const auto_populate_prompt = `
    This is the result of the domain categorization prompt: ${aesirx_ajax.domain_categorization}.
    This is the list of plugins: ${JSON.stringify(pluginItems)}.
    Return a JSON array of objects in the following format:
    [
      {
        "name": "[Name_Plugin]",
        "value": "[Value_Plugin]",
        "domain": "[Domain / Beacon]", 
        "blocked": [true or false],
        "category": "[Category]"
      }
    ]
    Only return object that domain not in ${host} and blocked is true.
    If have duplicate domain, please use the latest. Remove duplicate domain.
    If the domain is aesirx.io, just return empty in domain.
    Only output JSON. No explanation. No markdown. No spacing/line break.`;
    $('.prompt_item_regenerate, .auto_populated').each(function () {
      $(this).prop('disabled', true);
      $(this).find('.loader').addClass('show');
    });
    const openai_result = await fetch(`${endpoint}/openai-assistant`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: auto_populate_prompt,
      }),
    });
    const openai_json = await openai_result.json();
    const openai_content = marked.parse(
      openai_json?.data?.messages[openai_json?.data?.messages?.length - 1]?.content
    );

    $(`.domain_categorization_result`).html(`${openai_content}`);
    $('.prompt_item_regenerate, .auto_populated').each(function () {
      $(this).prop('disabled', false);
      $(this).find('.loader').removeClass('show');
    });
    const rawString = $(`.domain_categorization_result code`).text()
      ? $(`.domain_categorization_result code`).text()
      : $(`.domain_categorization_result p`).text();
    const validJson = rawString.replace(/'/g, '"');
    const result = await generateDomainConfigure(validJson);
    if (result) {
      $('.auto_populated').prop('disabled', true);
      $('.domain_categorization_buttons').after(
        '<div>Blocking rules updated. Review the applied settings on <a href="/wp-admin/admin.php?page=aesirx-consent-management-plugin">the Consent Shield page</a></div>'
      );
    } else {
      $('.auto_populated').prop('disabled', true);
      $('.domain_categorization_buttons').after(
        "<div class='error' style='color:red'>Error on generate blocking list, please regenerate</div>"
      );
    }
  });

  $('.copy_clipboard').click(async function () {
    const html = $(this).parent().find('.result').html();
    const copiedText = $(this).find('.copied_text');

    if (navigator.clipboard && navigator.clipboard.write) {
      try {
        await navigator.clipboard.write([
          new ClipboardItem({
            'text/html': new Blob([html], { type: 'text/html' }),
            'text/plain': new Blob([$(this).parent().find('.result').text()], {
              type: 'text/plain',
            }),
          }),
        ]);
        copiedText.addClass('show');
        setTimeout(() => copiedText.removeClass('show'), 1000);
        return;
      } catch (err) {
        console.error('Clipboard API error:', err);
      }
    }

    // Fallback for non-secure/unsupported environments
    const tempEl = document.createElement('div');
    tempEl.contentEditable = true;
    tempEl.innerHTML = html;
    document.body.appendChild(tempEl);

    const range = document.createRange();
    range.selectNodeContents(tempEl);

    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);

    try {
      document.execCommand('copy');
      console.log('Fallback copied');
    } catch (err) {
      console.error('Fallback copy failed:', err);
    }

    selection.removeAllRanges();
    document.body.removeChild(tempEl);
    copiedText.addClass('show');
    setTimeout(() => copiedText.removeClass('show'), 1000);
  });

  async function generateDomainConfigure(json) {
    try {
      const formattedOptions = JSON?.parse(json);
      const result = {
        blocking_cookies: formattedOptions
          ?.filter((p) => p.domain)
          ?.filter((p) => p.blocked)
          .map((p) => p.domain),
        blocking_cookies_category: formattedOptions
          ?.filter((p) => p.domain)
          .filter((p) => p.blocked)
          .map((p) => p.category?.toLowerCase()),

        blocking_cookies_plugins: formattedOptions
          ?.filter((p) => !p.domain)
          .filter((p) => p.blocked)
          .map((p) => p.value),

        blocking_cookies_plugins_category: formattedOptions
          ?.filter((p) => !p.domain)
          .reduce((acc, p) => {
            acc[p.value] = { [p.name]: p.category.toLowerCase() };
            return acc;
          }, {}),
      };

      await updateAesirxPluginsOptions(result);
      return true;
    } catch (error) {
      console.error('Failed to update options', error);
      return false;
    }
  }

  async function updateAesirxOptions(options) {
    const cookie_declaration = aesirx_ajax.cookie_declaration;
    const privacy_policy = aesirx_ajax.privacy_policy;
    const consent_request = aesirx_ajax.consent_request;
    const domain_categorization = aesirx_ajax.domain_categorization;
    const formattedOptions = {
      ...(cookie_declaration ? { cookie_declaration: cookie_declaration } : {}),
      ...(privacy_policy ? { privacy_policy: privacy_policy } : {}),
      ...(consent_request ? { consent_request: consent_request } : {}),
      ...(domain_categorization ? { domain_categorization: domain_categorization } : {}),
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

async function updateAesirxPluginsOptions(options) {
  jQuery.ajax({
    url: aesirx_ajax.ajax_url,
    method: 'POST',
    data: {
      action: 'update_aesirx_plugins_options',
      security: aesirx_ajax.nonce,
      options: JSON.stringify(options), // stringify whole options object
    },
    success(response) {
      if (response.success) {
        console.log('Options updated successfully');
      } else {
        console.error('Failed to update options');
      }
    },
    error(error) {
      console.error('AJAX error:', error);
    },
  });
}
