(function ($, Drupal) {

  "use strict";

  /**
   * Google Spreadsheets.
   */
  Drupal.behaviors.xlsxGooglePicker = {
    attach: function (context, settings) {

      $('.google-picker-button a', context).unbind().click(function(e) {
        var clientId = settings.google_client_id;
        var appId = settings.google_app_id;
        var scope = [
          'https://www.googleapis.com/auth/spreadsheets.readonly',
          'https://www.googleapis.com/auth/drive.file',
          'https://www.googleapis.com/auth/drive.readonly'
        ];
        var pickerApiLoaded = false;
        var oauthToken;

        function onAuthApiLoad() {
          window.gapi.auth.authorize({
              'client_id' : clientId,
              'scope'     : scope,
              'immediate' : false
            },
            handleAuthResult
          );
        }

        function onPickerApiLoad() {
          pickerApiLoaded = true;
          createPicker();
        }

        function handleAuthResult(authResult) {
          if (authResult && !authResult.error) {
            oauthToken = authResult.access_token;
          }
          createPicker();
        }

        function createPicker() {
          if (pickerApiLoaded && oauthToken) {
            var view = new google.picker.View(google.picker.ViewId.SPREADSHEETS);
            var _max_filesize = $('.google-picker-wrapper a').data('max-filesize');
            var picker = new google.picker.PickerBuilder()
              .enableFeature(google.picker.Feature.NAV_HIDDEN)
              .setAppId(appId)
              .setOAuthToken(oauthToken)
              .addView(view)
              .setMaxItems(1)
              .setOrigin(window.location.protocol + '//' + window.location.host)
              .addView(new google.picker.DocsUploadView())
              .setCallback(function(data) {
                console.log(data);
                if (data.action == google.picker.Action.PICKED) {
                  if (data.docs[0].sizeBytes > _max_filesize) {
                    alert(Drupal.t('File size is too big.'));
                  }
                  else {
                    $('.google-picker-wrapper input[name=remote_file]').val(data.docs[0].id + '@@@' + data.docs[0].name + '@@@' + oauthToken);
                    $('#google-picked-file').html(Drupal.t('<strong>Selected spreadsheet:</strong> @file', {'@file' : data.docs[0].name}));
                  }
                }
              })
              .build();
            picker.setVisible(true);
          }
        }

        gapi.load('auth', {'callback': onAuthApiLoad});
        if (pickerApiLoaded === false) {
          gapi.load('picker', {'callback': onPickerApiLoad});
        }

        e.preventDefault();

      });

    }
  };

}(jQuery, Drupal));
