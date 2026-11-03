jQuery(function ($) {
  $(document).on('click', '.ninjadraw-admin-notice.is-dismissible .notice-dismiss', function () {
    $.post(yalogica_ninjadraw_global_admin_notice.ajaxurl, {
      action: 'ninjadraw_dismiss_admin_notice',
      nonce: yalogica_ninjadraw_global_admin_notice.nonce,
    });
  });
});