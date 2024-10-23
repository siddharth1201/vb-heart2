<?php

namespace Drupal\heart_misc\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "contact_information",
 *   label = @Translation("Contact Information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class ContactInformation extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    // Display the summary of the email contact information.
    $order = $this->order;
    $email = $order->getEmail();
    $phone_number = '';
    if ($order->hasField('field_phone') && !($order->field_phone->isEmpty())) {
      $phone_number = $order->field_phone->getString();
    }
    return $this->t('Email: @email, Phone: @phone', ['@email' => $email, '@phone' => $phone_number]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $order = $this->order;
    $phone = '';

    if ($order->hasField('field_phone') && !($order->field_phone->isEmpty())) {
      $phone = $order->field_phone->getString();
    } else {
      $uid = User::load(\Drupal::currentUser()->id())->id();
      $user_profile_data = \Drupal::entityTypeManager()->getStorage('user_profile_data')->loadByProperties(['user_data' => $uid]);
      if (!empty($user_profile_data)) {
        foreach ($user_profile_data as $key => $value) {
          if ($value->hasField('phone') && !($value->phone->isEmpty())) {
            $phone = $value->phone->getString();
          }
        }
      }
    }

    $pane_form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#default_value' => $order->getEmail(),
      '#required' => TRUE,
    ];
    $pane_form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => $phone,
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $email = $form_state->getValue(['contact_information', 'email']);
    $phone = $form_state->getValue(['contact_information', 'phone']);
    // dump($this->order);
    // exit;
    $this->order->set('field_phone',$phone);
    $this->order->setEmail($email);
  }

}
