/**
 * wpdkAlert
 *
 * @class           wpdkAlert
 * @author          =undo= <info@wpxtre.me>
 * @copyright       Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date            2014-07-22
 * @version         3.1.1
 * @note            Base on bootstrap: alert.js v3.1.0
 *
 * - Rename namespace popover with wpdkAlert
 * - Rename namespace "bs" with "wpdk"
 * - Rename namespace "bs.alert" with "wpdk.wpdkAlert"
 *
 */

// Check for jQuery
if (typeof jQuery === 'undefined') { throw new Error('jQuery is not loaded or missing!') }

// One time
if( typeof( jQuery.fn.wpdkAlert ) === 'undefined' ) {

  /* ========================================================================
   * Bootstrap: alert.js v3.1.0
   * http://getbootstrap.com/javascript/#alerts
   * ========================================================================
   * Copyright 2011-2014 Twitter, Inc.
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
   * ======================================================================== */


  +function ($) {
    'use strict';

    // ALERT CLASS DEFINITION
    // ======================

    var dismiss = '[data-dismiss="wpdkAlert"]'
    var Alert   = function (el) {
      $(el).on('click', dismiss, this.close)
    }

    Alert.prototype.close = function (e) {
      var $this    = $(this)
      var selector = $this.attr('data-target')

      if (!selector) {
        selector = $this.attr('href')
        selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
      }

      var $parent = $(selector)

      if (e) e.preventDefault()

      if (!$parent.length) {
        $parent = $this.hasClass('wpdk-alert') ? $this : $this.parent()
      }

      $parent.trigger(e = $.Event('close.wpdk.wpdkAlert'))

      if (e.isDefaultPrevented()) return

      $parent.removeClass('in')

      function removeElement() {
        $parent.trigger('closed.wpdk.wpdkAlert').remove()
      }

      $.support.transition && $parent.hasClass('fade') ?
        $parent
          .one($.support.transition.end, removeElement)
          .emulateTransitionEnd(150) :
        removeElement()
    }


    // ALERT PLUGIN DEFINITION
    // =======================

    var old = $.fn.wpdkAlert

    $.fn.wpdkAlert = function (option) {
      return this.each(function () {
        var $this = $(this)
        var data  = $this.data('wpdk.wpdkAlert')

        if (!data) $this.data('wpdk.wpdkAlert', (data = new Alert(this)))
        if (typeof option == 'string') data[option].call($this)
      })
    }

    $.fn.wpdkAlert.Constructor = Alert


    // ALERT NO CONFLICT
    // =================

    $.fn.wpdkAlert.noConflict = function () {
      $.fn.wpdkAlert = old
      return this
    }

    // ALERT DATA-API
    // ==============

    $( document ).on( 'click.wpdk.wpdkAlert.data-api', dismiss, Alert.prototype.close)

    // Auto init
    $( '.wpdk-alert' ).wpdkAlert();

    // Refresh by event
    $( document ).on( WPDKUIComponents.REFRESH_ALERT, function() {
      $( '.wpdk-alert' ).wpdkAlert();
    } );

    // @deprecated since 1.5.2 use WPDKUIComponents.REFRESH_ALERT ('refresh.wpdk.wpdkAlert') instead
    $( document ).on( 'wpdk-alert', function() {
      $( '.wpdk-alert' ).wpdkAlert();
    } );

    // Extends with Permanent dismiss
    $( document ).on( 'click', '.wpdk-alert button.close.wpdk-alert-permanent-dismiss', function( e ) {

      e.preventDefault();

      var $this    = $(this);
      var alert_id = $this.parent().attr( 'id' );

      // Check for empty id
      if( empty( alert_id ) ) {
        return false;
      }

      // Ajax
      $.post( wpdk_i18n.ajaxURL, {
          action      : 'wpdk_action_alert_dismiss',
          alert_id    : alert_id
        }, function ( data )
        {
          var response = new WPDKAjaxResponse( data );

          if ( empty( response.error ) ) {
            // Process response

          }
          // An error return
          else {
            alert( response.error );
          }
        }
      );
    } );

  }(jQuery);

}
