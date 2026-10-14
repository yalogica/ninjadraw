jQuery(function ($) {
  $(document).on('click', '.ninjadraw-admin-notice.is-dismissible .notice-dismiss', function () {
    $.post(ninjadraw_globals_admin_notice.ajaxurl, {
      action: 'ninjadraw_dismiss_admin_notice',
      nonce: ninjadraw_globals_admin_notice.nonce,
    });
  });
});