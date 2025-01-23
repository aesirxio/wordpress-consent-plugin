jQuery(document).ready(function ($) {
  $('#aesirx-consent-add-cookies-row').on('click', function (e) {
    e.preventDefault();
    var row = $('table#aesirx-consent-blocking-cookies tr:last').clone();
    row.find('input').val('');
    $('table#aesirx-consent-blocking-cookies').append(row);
  });

  $(document).on('click', '.aesirx-consent-remove-cookies-row', function (e) {
    e.preventDefault();
    $(this).parents('tr.aesirx-consent-cookie-row').remove();
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
      if (event.origin !== 'https://signup.aesirx.io') return;
      if (event.data) {
        $('#aesirx_analytics_license').val(event.data);
      }
    },
    false
  );
});
