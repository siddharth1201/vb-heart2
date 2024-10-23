<?php

namespace Drupal\heart_misc\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides the review pane.
 *
 * @CommerceCheckoutPane(
 *   id = "review_info_custom",
 *   label = @Translation("Review Information"),
 *   default_step = "review",
 *   wrapper_element = "fieldset",
 *   display_label = @Translation("Review Information"),
 * )
 */
class ReviewInformation extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */

    $order = $this->checkoutFlow->getOrder();
    // Get the payment method.
    $payment_method = $order->get('payment_method')->entity;
    // Format the payment method details.
    // Get the credit card type and details.
    $encryption_service = \Drupal::service('encryption');
    // Decrypt Credit Card Details.
    $decrypted_card_type = $encryption_service->decrypt($payment_method->encrypted_full_card_type->value);
    $decrypted_card_number = $encryption_service->decrypt($payment_method->encrypted_full_card_number->value);
    $decrypted_exp_month = $encryption_service->decrypt($payment_method->encrypted_full_card_exp_month->value);
    $decrypted_exp_year = $encryption_service->decrypt($payment_method->encrypted_full_card_exp_year->value);

    $last_four_digit = substr($decrypted_card_number, -4);
    // Calculate the totals.
    $subtotal = $order->getSubtotalPrice();
    $order_total = $order->getTotalPrice();
    $phone_number = '';
    $tax_rate = 0;

    if ($order->hasField('adjustments') && !($order->adjustments->isEmpty())) {
      $tax_rate = $order->adjustments->value->getAmount()->getNumber();
      $tax_rate = str_replace('+', '', $tax_rate);
    }

    if ($order->hasField('field_phone') && !($order->field_phone->isEmpty())) {
      $phone_number = $order->field_phone->getString();
    }


    // Use the correct formatter service.
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
    // Get the billing profile.
    $billing_profile = $order->getBillingProfile();
    $billing_address = '';
    if ($billing_profile) {
      // Get the billing address and phone number.
      $billing_address = $billing_profile->get('address')->first()->getValue();
      // $phone_number = $billing_profile->get('field_phone')->value;

      // Build the formatted billing address.
      $billing_address_output = implode("\n", [
        $billing_address['given_name'] . ' ' . $billing_address['family_name'],
        $billing_address['address_line1'],
        $billing_address['address_line2'],
        $billing_address['locality'] . ', ' . $billing_address['administrative_area'] . ' ' . $billing_address['postal_code'],
        $billing_address['country_code'],
      ]);
    }
    // Get the contact email.
    $email = $order->getEmail();

    // Build the HTML output.
    $html = '
      <div class="custom-payment-information">
        <div class="m-bottom-3">Your order is almost complete. Please review the details below and click \'Submit order\' if all the information is correct. You may use the \'Back\' button to make changes to your order if necessary.
        </div>
        <div class="border-top border-bottom py-3">
          <h2 class="text-green">Contact Information </h2>
          <div class="fs-row"><div class="fs-col-3"></div><div class="fs-col-9"><div class="d-flex payment-info-item"><strong class="w-25">Email: </strong><span>' . $email . '</span></div></div></div>';
        if ($phone_number) {
          $html .= '<div class="fs-row"><div class="fs-col-3"></div><div class="fs-col-9"><div class="d-flex payment-info-item"><strong class="w-25">Phone: </strong><span>' . $phone_number . '</span></div></div></div>';
        }
    $html .= '</div>
        <div class="border-bottom py-3">
          <h2 class="text-green">Billing Address </h2>
          <div class="fs-row"><div class="fs-col-3"></div><div class="fs-col-9"><div class="d-flex payment-info-item"><strong class="w-25">Address:  </strong><span>' . nl2br($billing_address_output) . '</span></div></div></div>
        </div>
        <div class="clerfix py-3">
          <h2 class="text-green">Payment Information </h2>
          <div class="fs-row"><div class="fs-col-3"></div><div class="fs-col-9">
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Subtotal:</strong><span>' . $currency_formatter->format($subtotal->getNumber(), $subtotal->getCurrencyCode()) . '</span></div>
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Tax:</strong><span> $'. $tax_rate .'</span></div>
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Order total:</strong><span>' . $currency_formatter->format($order_total->getNumber(), $order_total->getCurrencyCode()) . '</span></div>
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Paying by:</strong><span>' . ucfirst($decrypted_card_type) . '</span></div>
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Card number:</strong><span>(Last 4) ' . $last_four_digit . '</span></div>
            <div class="d-flex payment-info-item m-bottom-1"><strong class="w-25">Expiration:</strong><span>' . $decrypted_exp_month . "/" . $decrypted_exp_year . '</span></div>
          </div></div>
        </div>
      </div>
    ';

    // Include the HTML output in the pane form.
    $pane_form['review_details'] = [
      '#markup' => Markup::create($html),
    ];

    return $pane_form;
  }

}
