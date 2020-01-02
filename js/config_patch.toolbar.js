(function ($, Drupal) {

  Drupal.behaviors.config_patch_toolbar = {
    attach: function attach(context, settings) {
      if ($('.toolbar-icon-config-patch', context).length > 0) {
        if (!$('.toolbar-icon-config-patch', context).hasClass('ajax-processed')) {
          Drupal.ajax({url: settings.config_patch.toolbar.url}).execute();
          $('.toolbar-icon-config-patch', context).addClass('ajax-processed')
        }
      }
    }
  };

})(jQuery, Drupal);
