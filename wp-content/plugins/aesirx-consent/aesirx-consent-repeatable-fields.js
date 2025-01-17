jQuery(document).ready(function($) {
    $('#aesirx-consent-add-cookies-row').on('click', function(e) {
        e.preventDefault();
        var row = $('table#aesirx-consent-blocking-cookies tr:last').clone();
        row.find('input').val('');
        $('table#aesirx-consent-blocking-cookies').append(row);
    });

    $(document).on('click', '.aesirx-consent-remove-cookies-row', function(e) {
        e.preventDefault();
        $(this).parents('tr.aesirx-consent-cookie-row').remove();
    });
});