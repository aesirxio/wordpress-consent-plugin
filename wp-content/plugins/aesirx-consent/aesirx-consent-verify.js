jQuery(document).ready(function ($) {
  const textAgeCountry = $('.age_country_text');
  $('#allowed_countries').select2();
  $('#disallowed_countries').select2();

  $('#age_check').on('change', function () {
    showPreviewByAge();
  });
  $('#country_check').on('change', function () {
    showPreviewByCountry();
  });

  $('#minimum_age').on('change', function () {
    showPreviewByAge();
    if ($(this).val() > 0) {
      $('.minimum_age_text').text($(this).val());
      if ($('#age_check').prop('checked')) {
        $('.minimum_age_line').removeClass('hide');
      }
    } else {
      $('.minimum_age_line').addClass('hide');
    }
  });
  $('#maximum_age').on('change', function () {
    showPreviewByAge();
    if ($(this).val() > 0) {
      $('.maximum_age_text').text($(this).val());
      if ($('#age_check').prop('checked')) {
        $('.maximum_age_line').removeClass('hide');
      }
    } else {
      $('.maximum_age_line').addClass('hide');
    }
  });
  $('#allowed_countries').on('change', function (e) {
    showPreviewByCountry();
    if ($(this).val()?.length > 0) {
      const countries = $(this)
        .select2('data')
        ?.map((c) => c.text)
        .join(', ');
      $('.allow_country_text').text(countries);
      if ($('#country_check').prop('checked')) {
        $('.allow_country_line').removeClass('hide');
      }
    } else {
      $('.allow_country_line').addClass('hide');
    }
  });
  $('#disallowed_countries').on('change', function (e) {
    showPreviewByCountry();
    if ($(this).val()?.length > 0) {
      const countries = $(this)
        .select2('data')
        ?.map((c) => c.text)
        .join(', ');
      $('.disallow_country_text').text(countries);
      if ($('#country_check').prop('checked')) {
        $('.disallow_country_line').removeClass('hide');
      }
    } else {
      $('.disallow_country_line').addClass('hide');
    }
  });

  const showPreviewByAge = () => {
    const isAgeEnable =
      $('#age_check').prop('checked') &&
      ($('#minimum_age').val() > 0 || $('#maximum_age').val() > 0);
    const isCountryEnable =
      $('#country_check').prop('checked') &&
      ($('#allowed_countries').val()?.length > 0 || $('#disallowed_countries').val()?.length > 0);
    if (isAgeEnable) {
      if (isCountryEnable) {
        previewAgeCountry();
      } else {
        previewAge();
      }
    } else {
      if (isCountryEnable) {
        previewCountry();
      } else {
        previewDefault();
      }
    }
  };

  const showPreviewByCountry = () => {
    const isAgeEnable =
      $('#age_check').prop('checked') &&
      ($('#minimum_age').val() > 0 || $('#maximum_age').val() > 0);
    const isCountryEnable =
      $('#country_check').prop('checked') &&
      ($('#allowed_countries').val()?.length > 0 || $('#disallowed_countries').val()?.length > 0);
    if (isCountryEnable) {
      if (isAgeEnable) {
        previewAgeCountry();
      } else {
        previewCountry();
      }
    } else {
      if (isAgeEnable) {
        previewAge();
      } else {
        previewDefault();
      }
    }
  };

  const previewDefault = () => {
    $('.aesirx_consent_verify_preview .heading span').each(function () {
      $(this).removeClass('active');
    });
    textAgeCountry.text('[age] / [country] / [age & country]');
    textAgeCountry.addClass('updateable');
    $('.allow_country_text').addClass('hide');
    $('.disallow_country_text').addClass('hide');
    $('.minimum_age_line').addClass('hide');
    $('.maximum_age_line').addClass('hide');
    $('.age_country_title').addClass('active');
  };
  const previewAge = () => {
    $('.aesirx_consent_verify_preview .heading span').each(function () {
      $(this).removeClass('active');
    });
    textAgeCountry.text('age');
    textAgeCountry.removeClass('updateable');
    $('.allow_country_line').addClass('hide');
    $('.disallow_country_line').addClass('hide');
    $('.age_title').addClass('active');
    if ($('#age_check')?.prop('checked')) {
      if ($('#maximum_age').val() > 0) {
        $('.maximum_age_line').removeClass('hide');
      } else {
        $('.maximum_age_line').addClass('hide');
      }
      if ($('#minimum_age').val() > 0) {
        $('.minimum_age_line').removeClass('hide');
      } else {
        $('.minimum_age_line').addClass('hide');
      }
    }
  };
  const previewCountry = () => {
    $('.aesirx_consent_verify_preview .heading span').each(function () {
      $(this).removeClass('active');
    });
    textAgeCountry.text('country');
    textAgeCountry.removeClass('updateable');
    $('.minimum_age_line').addClass('hide');
    $('.maximum_age_line').addClass('hide');
    $('.country_title').addClass('active');
    if ($('#country_check')?.prop('checked')) {
      if ($('#allowed_countries').val()?.length > 0) {
        $('.allow_country_line').removeClass('hide');
      } else {
        $('.allow_country_line').addClass('hide');
      }
      if ($('#disallowed_countries').val()?.length > 0) {
        $('.disallow_country_line').removeClass('hide');
      } else {
        $('.disallow_country_line').addClass('hide');
      }
    }
  };
  const previewAgeCountry = () => {
    $('.aesirx_consent_verify_preview .heading span').each(function () {
      $(this).removeClass('active');
    });
    textAgeCountry.text('age & country');
    textAgeCountry.removeClass('updateable');
    $('.age_country_title').addClass('active');
    if ($('#country_check')?.prop('checked')) {
      if ($('#allowed_countries').val()?.length > 0) {
        $('.allow_country_line').removeClass('hide');
      } else {
        $('.allow_country_line').addClass('hide');
      }
      if ($('#disallowed_countries').val()?.length > 0) {
        $('.disallow_country_line').removeClass('hide');
      } else {
        $('.disallow_country_line').addClass('hide');
      }
    }
    if ($('#age_check')?.prop('checked')) {
      if ($('#maximum_age').val() > 0) {
        $('.maximum_age_line').removeClass('hide');
      } else {
        $('.maximum_age_line').addClass('hide');
      }
      if ($('#minimum_age').val() > 0) {
        $('.minimum_age_line').removeClass('hide');
      } else {
        $('.minimum_age_line').addClass('hide');
      }
    }
  };
});
