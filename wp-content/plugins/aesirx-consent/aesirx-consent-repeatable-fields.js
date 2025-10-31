jQuery(document).ready(function ($) {
  $('#aesirx-consent-add-cookies-row').on('click', function (e) {
    e.preventDefault();
    var row = $('#aesirx-consent-blocking-cookies .aesirx-consent-cookie-row:last').clone();
    row.find('input').val('');
    $('#aesirx-consent-blocking-cookies').append(row);
  });

  $(document).on('click', '.aesirx-consent-remove-cookies-row', function (e) {
    e.preventDefault();
    $(this).parents('.aesirx-consent-cookie-row').remove();
  });

  $(document).on('click', '#sign-up-button, .sign-up-link', function (e) {
    e.preventDefault();
    $('#wpbody-content').append('<div class="aesirx-modal-backdrop"></div>');
    $('.aesirx_signup_modal').addClass('show');
  });

  $(document).on('click', '.aesirx-modal-backdrop', function (e) {
    e.preventDefault();
    $(this).remove();
    $('.aesirx_signup_modal').removeClass('show');
    if (!$('#aesirx_analytics_first_time_access').val()) {
      $('#aesirx_analytics_first_time_access').val('1');
    }
  });

  $(document).on('click', '.verify_domain', function (e) {
    e.preventDefault();
    $('#aesirx_analytics_verify_domain').val(new Date().getTime());
    $('.aesirx_consent_wrapper form').submit();
  });

  if (!$('#aesirx_analytics_first_time_access').val()) {
    $('#sign-up-button').trigger('click');
  }

  window.addEventListener(
    'message',
    (event) => {
      if (event.origin !== 'https://cmp.signup.aesirx.io') return;
      if (event.data) {
        const [key, value] = event.data.split('=');
        switch (key) {
          case 'license':
            jQuery('#aesirx_analytics_license').val(value);
            break;
          case 'client_id':
            jQuery('#aesirx_analytics_clientid').val(value);
            break;
          case 'client_secret':
            jQuery('#aesirx_analytics_secret').val(value);
            break;
          case 'copy':
            navigator.clipboard.writeText(value);
            break;
          default:
            console.warn('Unknown message type:', key);
        }
      }
    },
    false
  );

  $(document).on('click', '.aesirx_consent_template_item', function (e) {
    $(this).parent().find('.aesirx_consent_template_item').removeClass('active');
    $(this).addClass('active');
    $('#datastream_template_hidden').remove();
  });

  const textConsent = `
    <p class='mt-0 mb-1 mb-lg-2 text-black fw-semibold'>
      ${window?.aesirx_analytics_translate?.txt_manage_your_consent}
    </p>
    <p class='mt-0 mb-1 mb-lg-3'>
      ${
        $('.aesirx_analytics_consent_template_row #simple-mode').attr('checked')
          ? `${window?.aesirx_analytics_translate?.txt_choose_how_we_use_simple}`
          : `${window?.aesirx_analytics_translate?.txt_choose_how_we_use}`
      }
    </p>
    <div class='mb-1 mb-lg-3'>
      <p class='mb-1 mb-lg-2 text-black'>
        ${window?.aesirx_analytics_translate?.txt_by_consenting}
      </p>
      <div class='d-flex align-items-start check-line'>
        <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
        <div class='ms-10px'>
          ${window?.aesirx_analytics_translate?.txt_analytics_behavioral}
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
        <div class='ms-10px'>
          ${window?.aesirx_analytics_translate?.txt_form_data}
        </div>
      </div>
    </div>
    <div>
      <p class='mb-1 mb-lg-2 text-black'>${window?.aesirx_analytics_translate?.txt_please_note}</p>
      <div class='d-flex align-items-start check-line'>
        <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
        <div class='ms-10px'>
          ${window?.aesirx_analytics_translate?.txt_we_do_not_share}
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
        <div class='ms-10px'>
          ${window?.aesirx_analytics_translate?.txt_you_can_opt_in}
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
        <div class='ms-10px'>
          ${window?.aesirx_analytics_translate?.txt_for_more_details}
        </div>
      </div>
    </div>`;
  const textDetail = `
  <p class="mt-0 mb-1 mb-lg-2 text-black fw-semibold">
    ${window?.aesirx_analytics_translate?.txt_manage_your_consent}
  </p>
  <p class="mt-0 mb-1 mb-lg-3">
    ${
      $('.aesirx_analytics_consent_template_row #simple-mode').attr('checked')
        ? `${window?.aesirx_analytics_translate?.txt_choose_how_we_use_simple}`
        : `${window?.aesirx_analytics_translate?.txt_choose_how_we_use}`
    }
  </p>
  <div class="mb-1 mb-lg-3">
    <p class="mb-1 mb-lg-2 text-black fw-semibold">
      ${window?.aesirx_analytics_translate?.txt_benefit}
    </p>
    <div class="d-flex align-items-start check-line">
      <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
      <div class="ms-10px">
      ${window?.aesirx_analytics_translate?.txt_control_your_data}
      </div>
    </div>
    <div class="d-flex align-items-start check-line">
      <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
      <div class="ms-10px">
      ${window?.aesirx_analytics_translate?.txt_transparent_data}
      </div>
    </div>
  </div>
  <div class="mb-1 mb-lg-3">
    <p class="mb-1 mb-lg-2 text-black fw-semibold">
      ${window?.aesirx_analytics_translate?.txt_understanding_your_privacy}
    </p>
    <div class="d-flex align-items-start check-line">
      <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
      <div class="ms-10px">
        ${window?.aesirx_analytics_translate?.txt_reject_no_data}
      </div>
    </div>
    <div class="d-flex align-items-start check-line">
      <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
      <div class="ms-10px">
        ${window?.aesirx_analytics_translate?.txt_consent_first_third_party}
      </div>
    </div>
    ${
      $('.aesirx_analytics_consent_template_row #simple-mode').attr('checked')
        ? ``
        : `
        <div class="d-flex align-items-start check-line">
          <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
          <div class="ms-10px">
            ${window?.aesirx_analytics_translate?.txt_decentralizered_consent_choose}
          </div>
        </div>
        `
    }
  </div>
  `;

  const textReject = `
  <p class="mt-0 pt-4 mb-2">
    ${window?.aesirx_analytics_translate?.txt_you_have_chosen}
  </p>
  <p class="mt-2 mb-3">
    ${window?.aesirx_analytics_translate?.txt_only_anonymized}
  </p>
  <div class="d-flex align-items-start check-line">
    <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
    <div class="ms-10px">
    ${window?.aesirx_analytics_translate?.txt_consent_allow_data}
    </div>
  </div>
  <div class="d-flex align-items-start check-line">
    <span><img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'></span>
    <div class="ms-10px">
    ${window?.aesirx_analytics_translate?.txt_decentralized_consent_allow_data}
    </div>
  </div>`;
  const editors = [
    {
      id: 'datastream_consent',
      inputId: 'aesirx_consent_modal_datastream_consent',
      text: textConsent,
      resetClass: 'reset_consent_button',
    },
    {
      id: 'datastream_cookie',
      inputId: 'aesirx_consent_modal_datastream_cookie',
      text: '',
      resetClass: 'reset_cookie_button',
    },
    {
      id: 'datastream_detail',
      inputId: 'aesirx_consent_modal_datastream_detail',
      text: textDetail,
      resetClass: 'reset_detail_button',
    },
    {
      id: 'datastream_reject',
      inputId: 'aesirx_consent_modal_datastream_reject',
      text: textReject,
      resetClass: 'reset_reject_button',
    },
  ];
  const {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Mention,
    Paragraph,
    Undo,
    GeneralHtmlSupport,
    Image,
    ImageToolbar,
    ImageCaption,
    ImageStyle,
    ImageResize,
    LinkImage,
    SourceEditing,
    Link,
    AutoLink,
    Table,
    TableToolbar,
    Heading,
    CodeBlock,
  } = CKEDITOR;
  editors.forEach(({ id, inputId, text, resetClass }) => {
    // Initialize CKEditor for each editor
    if (document.querySelector(`#${id}`)) {
      ClassicEditor.create(document.querySelector(`#${id}`), {
        licenseKey: 'GPL',
        toolbar: {
          items: [
            'sourceEditing',
            'undo',
            'redo',
            '|',
            'bold',
            'italic',
            'link',
            'insertImage',
            'insertTable',
            'codeBlock',
          ],
        },
        plugins: [
          Bold,
          Essentials,
          Italic,
          Mention,
          Paragraph,
          Undo,
          GeneralHtmlSupport,
          Image,
          ImageToolbar,
          ImageCaption,
          ImageStyle,
          ImageResize,
          LinkImage,
          SourceEditing,
          Link,
          AutoLink,
          Table,
          TableToolbar,
          Heading,
          CodeBlock,
        ],
        htmlSupport: {
          allow: [{ name: /.*/, attributes: true, classes: true, styles: true }],
        },
      })
        .then((editor) => {
          // Set the initial content if the input field is empty
          if (!$(`#${inputId}`).val()) {
            editor.setData(text);
          } else {
            editor.setData($(`#${inputId}`).val());
          }

          // Listen for changes and update the input field accordingly
          editor.model.document.on('change:data', () => {
            let content = editor.getData();
            content = content.replace(/="([^"]*)"/g, "='$1'");
            const hasText = content.replace(/(<([^>]+)>)/gi, '').length;
            $(`#${inputId}`).val(hasText ? content : '');
          });

          // Reset content when the reset button is clicked
          $(document).on('click', `.${resetClass}`, function () {
            editor.setData(text);
            $(`#${inputId}`).val('');
          });

          console.log(`${id} initialized successfully!`);
        })
        .catch((error) => {
          console.error(`Error initializing CKEditor for ${id}:`, error);
        });
    }
  });
});
