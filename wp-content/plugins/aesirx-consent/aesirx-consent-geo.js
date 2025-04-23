jQuery(document).ready(function ($) {
  $('#aesirx-consent-add-rules-row').on('click', function (e) {
    e.preventDefault();
    var row = $('#aesirx-consent-geo-rules .aesirx-consent-rule-row:last').clone();
    row.find('select').each(function () {
      $(this).val('');
    });
    $('#aesirx-consent-geo-rules').append(row);
  });

  $(document).on('click', '.aesirx-consent-remove-rules-row', function (e) {
    e.preventDefault();
    $(this).parents('.aesirx-consent-rule-row').remove();
  });
});
