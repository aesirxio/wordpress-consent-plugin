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
  });

  const textConsent = `
    <p class='mt-0 mb-1 mb-lg-2 text-black fw-semibold'>
      Manage Your Consent Preferences
    </p>
    <p class='mt-0 mb-1 mb-lg-3'>
     ${
       $('.aesirx_analytics_consent_template_row #simple-mode').attr('checked')
         ? `Choose how we use your data: “Reject” data collection, allow tracking [“Consent”].`
         : `Choose how we use your data: “Reject” data collection, allow tracking [“Consent”], or use “Decentralized Consent” for more control over your personal data & rewards.`
     }
    </p>
    <div class='mb-1 mb-lg-3'>
      <p class='mb-1 mb-lg-2 text-black'>
        By consenting, you allow us to collect & use your data for:
      </p>
      <div class='d-flex align-items-start check-line'>
        <span>
          <img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'>
        </span>
        <div class='ms-10px'>
          <div>Analytics & Behavioral Data: To improve our services & personalize your experience.</div>
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span>
          <img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'>
        </span>
        <div class='ms-10px'>
          <div>Form Data: When you contact us.</div>
        </div>
      </div>
    </div>
    <div>
      <p class='mb-1 mb-lg-2 text-black'>Please note</p>
      <div class='d-flex align-items-start check-line'>
        <span>
          <img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'>
        </span>
        <div class='ms-10px'>
          <div>We do not share your data with third parties without your explicit consent.</div>
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span>
          <img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'>
        </span>
        <div class='ms-10px'>
          <div>You can opt-in later for specific features without giving blanket consent.</div>
        </div>
      </div>
      <div class='d-flex align-items-start check-line'>
        <span>
          <img src='/wp-content/plugins/aesirx-consent/assets/images-plugin/check_circle.svg' width='14px' height='15px'>
        </span>
        <div class='ms-10px'>
          For more details, refer to our <a class='text-success fw-semibold text-decoration-underline' href='https://aesirx.io/privacy-policy' target='_blank'>privacy policy.</a>
        </div>
      </div>
    </div>`;
  const Block = Quill.import('blots/block');
  const Inline = Quill.import('blots/inline');
  const Image = Quill.import('formats/image');

  class CustomImage extends Image {
    static create(value) {
      let node = super.create();
      node.setAttribute('src', value.src || value); // Ensure src is set correctly
      if (value.width) node.setAttribute('width', value.width);
      if (value.height) node.setAttribute('height', value.height);
      if (value.class) node.setAttribute('class', value.class);
      return node;
    }

    static formats(node) {
      return {
        src: node.getAttribute('src'), // Ensure src is preserved
        width: node.getAttribute('width'),
        height: node.getAttribute('height'),
        class: node.getAttribute('class'),
      };
    }

    format(name, value) {
      if (name === 'src' || name === 'width' || name === 'height' || name === 'class') {
        if (value) {
          this.domNode.setAttribute(name, value);
        } else {
          this.domNode.removeAttribute(name);
        }
      } else {
        super.format(name, value);
      }
    }
  }
  class CustomParagraph extends Block {
    static create(value) {
      let node = super.create();
      if (value) node.setAttribute('class', value);
      return node;
    }
    static formats(node) {
      return node.getAttribute('class') || '';
    }
    format(name, value) {
      if (name === 'class') {
        if (value) {
          this.domNode.setAttribute('class', value);
        } else {
          this.domNode.removeAttribute('class');
        }
      } else {
        super.format(name, value);
      }
    }
  }
  class CustomDiv extends Block {
    static create(value) {
      let node = super.create();
      if (value) node.setAttribute('class', value);
      return node;
    }
    static formats(node) {
      return node.getAttribute('class') || '';
    }
  }
  class CustomSpan extends Inline {
    static create(value) {
      let node = super.create();
      if (value) node.setAttribute('class', value);
      return node;
    }
    static formats(node) {
      return node.getAttribute('class') || '';
    }
  }
  CustomImage.blotName = 'custom-image';
  CustomImage.tagName = 'img';
  CustomParagraph.blotName = 'custom-p';
  CustomParagraph.tagName = 'p';
  CustomDiv.blotName = 'custom-div';
  CustomDiv.tagName = 'div';
  CustomSpan.blotName = 'custom-span';
  CustomSpan.tagName = 'span';

  Quill.register(CustomImage);
  Quill.register(CustomDiv);
  Quill.register(CustomSpan);
  Quill.register(CustomParagraph);

  const quill = new Quill('#datastream_consent', {
    theme: 'snow',
    formats: ['custom-div', 'custom-span', 'custom-p', 'custom-image'],
  });
  if (!$('#aesirx_analytics_datastream_consent').val()) {
    quill.clipboard.dangerouslyPasteHTML(`${textConsent}`);
  }
  quill.on('text-change', () => {
    let regex = /(<([^>]+)>)/gi;
    let content = $('.ql-editor').html();
    var modifiedHtml = content.replace(/"/g, "'");
    let hasText = !!content.replace(regex, '').length;
    if (hasText) {
      $(`#aesirx_analytics_datastream_consent`).val(modifiedHtml);
    } else {
      $(`#aesirx_analytics_datastream_consent`).val('');
    }
  });
  $(document).on('click', '.reset_consent_button', function (e) {
    quill.clipboard.dangerouslyPasteHTML(`${textConsent}`);
    $(`#aesirx_analytics_datastream_consent`).val('');
  });
});
