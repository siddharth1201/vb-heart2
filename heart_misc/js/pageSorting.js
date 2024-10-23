
jQuery(document).once().ajaxComplete(function(event){
    let count = 1;

    if (event.currentTarget.documentURI.includes('#all-users-tab')) {
        jQuery(".views-exposed-form-manage-users-block-1 .flex-view-exposed .sort_by .fieldset-wrapper .form-radios").once().find(".form-item").each(function(){
            let val = jQuery(this).find("input").prop('checked');

            if(count == 1 && val){

            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").show();
            }
            if(count == 2 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            }
            if(count == 3 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").show();
            }
            if(count == 4 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            }
            count++;
        });
        jQuery(".views-exposed-form-manage-users-block-1").once().attr('action', '/manage-account#all-users-tab');
        jQuery('.view-manage-users').once().on('click', 'a.edit-this-user', function (e) {
            // Get current langcode.
            var langCode = drupalSettings.path.currentLanguage;
            // Get the data from the button.
            let userId = jQuery(this).data('userid');
            if (langCode == 'en') {
                var Url = '/user-profile-data/' + userId + '/edit';
            } else {
                var Url = '/' + langCode + '/user-profile-data/' + userId + '/edit';
            }
            console.log(Url);
            jQuery.ajax({
                url: Url,
                type: "POST",
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function (response) {
                //Remove class view and show manage class form and learner view.
                jQuery('.all-user-view').addClass('visually-hidden');
                if (jQuery('.manage-profile-form').hasClass('visually-hidden')) {
                    jQuery('.manage-profile-form').removeClass('visually-hidden');
                }
                if (jQuery('.profile-and-classes-link').hasClass('visually-hidden')) {
                    jQuery('.profile-and-classes-link').removeClass('visually-hidden');
                    jQuery('#user-profile-link').attr('data-userid', userId);
                    jQuery('#user-classes-link').attr('data-userid', userId);
                }
                var jQuerywrapper = jQuery('.manage-profile-form');
                jQuerywrapper.html(response.form_html);
                // Ensure drupalSettings are merged correctly
                if (typeof drupalSettings === 'undefined') {
                    drupalSettings = {};
                }
                if (typeof response.settings !== 'undefined') {
                    jQuery.extend(drupalSettings, response.settings);
                }
                // // Reattach Drupal behaviors
                Drupal.attachBehaviors(jQuerywrapper[0], drupalSettings);
                },
                error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                }
            });
        });
    } else if (event.currentTarget.documentURI.includes('#manage-diocese-tab')) {

        jQuery(".views-exposed-form-managing-diocese-block-2 .flex-view-exposed .sort_by .fieldset-wrapper .form-radios").once().find(".form-item").each(function(){
            let val = jQuery(this).find("input").prop('checked');
            if(count == 1 && val){
                jQuery(this).find("label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(2) label").addClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(2) label").show();
            }
            if(count == 2 && val){
                jQuery(this).find("label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(2) label").removeClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(1) label").addClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
                jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
                jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            }
            if(count == 3 && val){
                jQuery(this).find("label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(1) label").removeClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(4) label").addClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
                jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(4) label").show();
            }
            if(count == 4 && val){
                jQuery(this).find("label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(4) label").removeClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(3) label").addClass('active-sort');
                jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
                jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
                jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            }
            count++;
        });

        jQuery(".heart-diocese-heart-diocese-invite").once().attr('action', '/manage-account#manage-diocese-tab');

    } else if (event.currentTarget.documentURI.includes('#parish-tab')) {
        jQuery(".views-exposed-form-manage-parish-default .flex-view-exposed .sort_by .fieldset-wrapper .form-radios").once().find(".form-item").each(function(){
            let val = jQuery(this).find("input").prop('checked');
            console.log(count, val)
            if(count == 1 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").show();
            }
            if(count == 2 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            }
            if(count == 3 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").show();
            }
            if(count == 4 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            }
            count++;
        });
        jQuery(".heart-parish-heart-parish-invite").once().attr('action', '/manage-account#parish-tab');
    } else {
        jQuery(".views-exposed-form-all-classes-default .flex-view-exposed .sort_by .fieldset-wrapper .form-radios").once().find(".form-item").each(function(){
            let val = jQuery(this).find("input").prop('checked');

            if(count == 1 && val){

            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").show();
            }
            if(count == 2 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").hide();
            }
            if(count == 3 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").show();
            }
            if(count == 4 && val){
            jQuery(this).find("label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(4) label").removeClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").addClass('active-sort');
            jQuery(this).parent().parent().find(".form-item:nth-child(1) label").show();
            jQuery(this).parent().parent().find(".form-item:nth-child(2) label").hide();
            jQuery(this).parent().parent().find(".form-item:nth-child(3) label").show();
            }
            count++;
        });
        jQuery(".views-exposed-form-manage-users-block-1").once().attr('action', '/manage-account#all-users-tab');
        jQuery('.view-manage-users').once().on('click', 'a.edit-this-user', function (e) {
            // Get current langcode.
            var langCode = drupalSettings.path.currentLanguage;
            // Get the data from the button.
            let userId = jQuery(this).data('userid');
            if (langCode == 'en') {
                var Url = '/user-profile-data/' + userId + '/edit';
            } else {
                var Url = '/' + langCode + '/user-profile-data/' + userId + '/edit';
            }
            console.log(Url);
            jQuery.ajax({
                url: Url,
                type: "POST",
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function (response) {
                //Remove class view and show manage class form and learner view.
                jQuery('.all-user-view').addClass('visually-hidden');
                if (jQuery('.manage-profile-form').hasClass('visually-hidden')) {
                    jQuery('.manage-profile-form').removeClass('visually-hidden');
                }
                if (jQuery('.profile-and-classes-link').hasClass('visually-hidden')) {
                    jQuery('.profile-and-classes-link').removeClass('visually-hidden');
                    jQuery('#user-profile-link').attr('data-userid', userId);
                    jQuery('#user-classes-link').attr('data-userid', userId);
                }
                var jQuerywrapper = jQuery('.manage-profile-form');
                jQuerywrapper.html(response.form_html);
                // Ensure drupalSettings are merged correctly
                if (typeof drupalSettings === 'undefined') {
                    drupalSettings = {};
                }
                if (typeof response.settings !== 'undefined') {
                    jQuery.extend(drupalSettings, response.settings);
                }
                // // Reattach Drupal behaviors
                Drupal.attachBehaviors(jQuerywrapper[0], drupalSettings);
                },
                error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                }
            });
        });
    }
});

// jQuery('.views-form-managing-diocese-block-2 .vbo-table th.select-all').click(function (e) {
//     e.preventDefault();
//     jQuery('.views-form-managing-diocese-block-2 .form-item-select-all input.vbo-select-all').click();
// })