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

    $(document).on('click', '#sign-up-button, .sign-up-link', function(e) {
        e.preventDefault();
        $('#wpbody-content').append('<div class="aesirx-modal-backdrop"></div>');
        $('.aesirx_signup_modal').addClass('show');
    });

    $(document).on('click', '.aesirx-modal-backdrop', function(e) {
        e.preventDefault();
        $(this).remove();
        $('.aesirx_signup_modal').removeClass('show');
    });
});