/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.heart_misc = {
    attach: function (context, settings) {
      $(document).ready(function () {

        $(".button--action-to-library")
          .once()
          .click(function (event) {
            event.preventDefault();

            let uid = drupalSettings.user.uid;
            let productId = $(this).data("pid");
            let action = $(this).data("op");
            let instance = $(this);
            if (action == "remove" && uid != 0 && productId != 0) {
              $("#dialog-confirm-rm-prompt").attr("data-pid", productId);
              $("#dialog-confirm-rm-prompt").dialog();
            }
            if (uid == 0) {
              $("#dialog-login-prompt").dialog({
                width: 393, // Specify the width of the dialog box
                height: 155, // Specify the height of the dialog box
                modal: true
              });
            } else if (action == "add") {
              $.ajax({
                type: "POST",
                url:
                  drupalSettings.path.baseUrl +
                  "heart-misc/heart-action-to-library",
                data: {
                  product_id: productId,
                  user_id: uid,
                  operation: action,
                  lang: drupalSettings.path.currentLanguage,
                },
                dataType: "json",
                cache: false,
                success: function (result) {
                  var res = result;
                  if (res == "Added") {
                    if (action == "add") {
                      // Update button text and class
                      instance.html('<span class="fa fa-check m-right-2 v-align-middle"></span>saved to my library')
                        .removeClass("button--primary save-to-my-library")
                        .addClass("saved-to-my-library btn-green btn");
                      // Force re-render
                      instance.hide().show(0);
                    }
                  }
                },
                error: function (xhr, status, error) {
                  // Handle error here
                  console.error(xhr.responseText);
                },
              });
            }
          });

          $("#dialog-confirm-rm-prompt a")
          .once()
          .click(function (event) {
            event.preventDefault();
            let confirmation = $(this).data("confirm");

            if (confirmation == "no") {
              $("#dialog-confirm-rm-prompt").dialog("close");
            }
            if (confirmation == "yes") {
              let uid = drupalSettings.user.uid;
              let productId = $("#dialog-confirm-rm-prompt").data("pid");

              let instance = $(
                'a.button--action-to-library[data-pid="' + productId + '"]'
              );
              console.log(instance);
              $.ajax({
                type: "POST",
                url:
                  drupalSettings.path.baseUrl +
                  "heart-misc/heart-action-to-library",
                data: {
                  product_id: productId,
                  user_id: uid,
                  operation: "remove",
                  lang: drupalSettings.path.currentLanguage,
                },
                dataType: "json",
                cache: false,
                success: function (result) {
                  var res = result;
                  if (res == "Removed") {
                    $("#dialog-confirm-rm-prompt").dialog("close");
                    $("#dialog-confirm-rm-prompt").attr("data-pid", "");
                    instance.parent().remove();
                  }
                  if (res == "No action") {
                    $("#dialog-confirm-rm-prompt").dialog("close");
                  }
                },
              });
            }
          });

          $(".commerce-checkout-flow-multistep-default .layout-region-checkout-secondary .checkout-actions a.link--previous")
          .once()
          .click(function (event) {
            event.preventDefault();

            let uid = drupalSettings.user.uid;
            let back_url = $(this).attr("href");
            let orderId = back_url.match(/\/checkout\/(\d+)\/order_information/)[1];

            $.ajax({
              type: "POST",
              url:
                drupalSettings.path.baseUrl +
                "heart-misc/delete-order-payment-data",
              data: {
                user_id: uid,
                lang: drupalSettings.path.currentLanguage,
                order_id: orderId,
              },
              dataType: "json",
              cache: false,
              success: function (result) {
                var res = result;
                if (res == "deleted") {
                  window.location.href = back_url;
                }
              },
            });
          });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
(function (Drupal, $) {
  Drupal.behaviors.changeTax = {
    attach: function (context, settings) {
      // Get the current path.
      var currentPath = window.location.pathname;

      // Check if the current path ends with /checkout/*/order_information.
      if (currentPath.match(/\/checkout\/\d+\/order_information$/)) {
        $('.order-total-line__adjustment--tax .order-total-line-value', context).once('changeTax').each(function () {
          $(this).text('Calculated in next step');
        });
      }
    }
  };
})(Drupal, jQuery);
(function ($, Drupal) {
  Drupal.behaviors.removeOrderItem = {
    attach: function (context, settings) {
      $('.remove-order-item', context).once('removeOrderItem').click(function (e) {
        e.preventDefault();
        var orderItemId = $(this).data('order-item-id');
        var productName = $(this).data('product-name');
        console.log(productName);
        // Get the current URL path
        var currentPath = window.location.pathname;
        var message = Drupal.t(`Are you sure you would like to remove "${productName}" from your library?`)

        console.log(currentPath);
        $('<div>' + message + '</div>').dialog({
          title: Drupal.t('Confirm Removal'),
          modal: true,
          buttons: {
            [Drupal.t('Yes')]: function () {
              $.ajax({
                url: Drupal.url('remove-order-item/' + orderItemId),
                type: 'POST',
                data: {
                  order_item_id: orderItemId
                },
                success: function (response) {
                  if (response.success) {
                    // Optionally, remove the item from the DOM or refresh the page
                    location.reload();
                  } else {
                    alert('Failed to remove the item.');
                  }
                },
                error: function () {
                  alert('An error occurred while removing the item.');
                }
              });
              $(this).dialog('close');
            },
            [Drupal.t('No')]: function () {
              $(this).dialog('close');
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal);

