/**
 * @file
 */
(function ($, Drupal, drupalSettings) {

    'use strict';

     Drupal.behaviors.heart_zoom = {
      attach: function (context, settings) {

        $(document).ready(function(){
            $('#heart-zoom-zoom-profile .field-item .edit-btn').once().click(function() {
                $(this).parent().find('.zoom-profile-inline-form').toggle();
            });
            $('.zoom-profile-inline-form .save-zoom-profile-field').once().click( function() {
                let name = $(this).parent().find('input').attr('name');
                let val = '';
                if (name == 'time_format') {
                     val = $(this).parent().find("input[type='radio'].edit-zoom-profile-time_format:checked").val();
                }
                else if (name == 'authentication') {
                    val = $(this).parent().find("input[type='radio'].edit-zoom-profile-authentication:checked").val();
                    if (val == '0') {
                        $(this).parent().parent().find("a.edit-btn").text('Turn On');
                    }
                    if (val == '1') {
                        $(this).parent().parent().find("a.edit-btn").text('Turn Off');
                    }
                } else {
                     val = $(this).parent().find('input').val();
                }
                let instance = $(this);
                $.ajax({
                    type: "POST",
                    url: drupalSettings.path.baseUrl + "heart-zoom/heart-zoom-entity-update",
                    data: { input_val: val, name_val:name, lang:drupalSettings.path.currentLanguage },
                    dataType: "json",
                    cache: false,
                    success: function (result) {

                      var res = result;
                      if (res == 'success') {
                        if (name == 'authentication') {
                            if (val == 1) {
                                instance.parent().parent().find('.set-value').text('On');
                            } else {
                                instance.parent().parent().find('.set-value').text('Off');
                            }
                        } else {
                            instance.parent().parent().find('.set-value').text(val);
                        }
                      }
                      if (res == 'failed') {
                        instance.parent().parent().find('.set-value').text('Not Set');
                      }
                    },
                });
            });

            // Delete Zoom Webinar
            $('a.delete-webinar-btn').once().click(function(event) {
                event.preventDefault();
                var webinar_id = $(this).attr('data-webinarid');
                var entity_id = $(this).attr('data-heartid');

                $("#dialog-webinar-remove-prompt").dialog({
                    width: 393, // Specify the width of the dialog box
                    height: 155, // Specify the height of the dialog box
                    modal: true
                  });
                $("a.remove-btn-zoom-webinar").attr('data-webinarid', webinar_id);
                $("a.remove-btn-zoom-webinar").attr('data-heartid', entity_id);
            });

            $('a.remove-btn-zoom-webinar').once().click(function(event) {
                event.preventDefault();
                var webinar_id = $(this).attr('data-webinarid');
                var entity_id = $(this).attr('data-heartid');

                if (webinar_id != 0 && entity_id != null) {
                    $.ajax({
                        type: "POST",
                        url:
                            drupalSettings.path.baseUrl +
                            "heart-zoom/heart-zoom-delete-webinar",
                        data: {
                            webinar_id: webinar_id,
                            entity_id: entity_id,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            if (result == 'deleted') {
                                $("#dialog-webinar-remove-prompt").dialog('close');
                                location.reload();
                            }
                        },
                    });

                }
            });

            $('.heart-zoom-panelist-webinar-form button.remove-panelist.remove-set-button').once().click(function(event) {
                event.preventDefault();
                var panelist_id = $(this).attr('data-panelist-id');
                var entity_id = $(this).attr('data-heart-zoom-id');
                var webinar_id = $(this).attr('data-webinar-id');

                if (panelist_id != 0 && entity_id != null) {
                    $.ajax({
                        type: "POST",
                        url:
                            drupalSettings.path.baseUrl +
                            "heart-zoom/heart-zoom-delete-panelist",
                        data: {
                            panelist_id: panelist_id,
                            entity_id: entity_id,
                            webinar_id: webinar_id,
                        },
                        dataType: "json",
                        cache: false,
                        success: function (result) {
                            if (result == 'deleted') {
                                location.reload();
                            }
                        },
                    });

                } else {
                    $(this).parent().remove();
                }
            });

            $(".download-webinar-report").click(function() {
                if (window.location.hash === "#reports-tab" && $(".region--highlighted .download-webinar-report").length > 0) {
                    // Simulate click on the download link
                    setTimeout(function() {
                        var file_link = $('.region--highlighted a.download-webinar-report').attr('href');
                        // window.location.href = '/' + file_link;

                        setTimeout(function() {
                            if (file_link) {
                                $.ajax({
                                    type: "POST",
                                    url:
                                        drupalSettings.path.baseUrl +
                                        "heart-zoom/heart-zoom-delete-report",
                                    data: {
                                        file_name: file_link,
                                    },
                                    dataType: "json",
                                    cache: false,
                                    success: function (result) {
                                        if (result == 'deleted') {
                                            $('a.download-webinar-report').parent().find('button.btn-link.close').click();
                                            // location.reload();
                                        }
                                    },
                                });
                            }
                        }, 5000);

                    }, 3000);
                }
            });

            // Event listener for the select change
            $('#vbo-action-form-wrapper .form-item-action select.form-select').change(function() {
                const selectedValue = $('#vbo-action-form-wrapper .form-item-action select#edit-action').val();
                if (selectedValue === '0') {
                    $('.views-form-upcoming-webinars-block-3 fieldset.form-composite').show();
                } else {
                    $('.views-form-upcoming-webinars-block-3 fieldset.form-composite ').hide();
                }
            });
        })
      }
    };
  })(jQuery, Drupal, drupalSettings);
