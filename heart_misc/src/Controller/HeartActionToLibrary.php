<?php

declare(strict_types=1);

namespace Drupal\heart_misc\Controller;

use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Heart Misc routes.
 */
final class HeartActionToLibrary extends ControllerBase {

  /**
   * The route match.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user reference data creation.
   *
   * @var \Drupal\heart_user_data\UserRefData
   */
  protected $userRefData;

  /**
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new HeartZoomEntityUpdateController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The route match.
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   * @param Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    UserRefData $userRefData,
    RequestStack $request,
    TimeInterface $date_time,
    AccountInterface $current_user,
    Connection $database
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userRefData = $userRefData;
    $this->request = $request;
    $this->dateTime = $date_time;
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('request_stack'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * Add or remove product from library.
   */
  public function actionToLibrary() {
    // Get the input values from the request.
    $product_id = $this->request->getCurrentRequest()->request->get('product_id');
    $uid = $this->request->getCurrentRequest()->request->get('user_id');
    $operation = $this->request->getCurrentRequest()->request->get('operation');

    if (!empty($product_id)) {
      // Load product.
      $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
      $product_entity_id = $product->id();
      $product_entity_bundle = $product->bundle();

      // Check if product already exist in user ref data table.
      $productExist = $this->userRefData->userRefDataGet($uid, 'commerce_product', $product_entity_bundle, $product_entity_id);
    }

    if ($operation == 'add' && !empty($uid) && !empty($product) && empty($productExist)) {
      $variations = $product->getVariations();
      $variation_id = $variations[0]->id();

      // Load the product variation.
      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);

      // Create an order item and save it.
      $order_item = $this->entityTypeManager
        ->getStorage('commerce_order_item')
        ->create([
          'type' => 'default',
          'purchased_entity' => $variation,
          'quantity' => 1,
          'field_complementary' => 1,
          'field_product_type' => $product_entity_bundle,
          'field_product' => $product,
          'unit_price' => new Price('0', 'USD'),
          'overridden_unit_price' => TRUE,
        ]);

      // Set the product title explicitly.
      $order_item->setTitle($product->getTitle());
      $order_item->save();

      // Create order and attach the previous order item generated.
      $order = $this->entityTypeManager
        ->getStorage('commerce_order')
        ->create([
          'type' => 'default',
          'mail' => $this->currentUser->getEmail(),
          'uid' => $this->currentUser->id(),
          'store_id' => 1,
          'order_items' => [$order_item],
          'placed' => $this->dateTime->getCurrentTime(),
          'payment_gateway' => '',
          'checkout_step' => 'complete',
          'state' => 'completed',
        ]);

      // Add order number and save (based on order id).
      $order->set('order_number', $order->id());
      $order->save();

      // Add product to user ref data table.
      $this->userRefData->userRefDataAdd($uid, 'commerce_product', $product_entity_id, $product_entity_bundle);
      return new JsonResponse("Added");
    }
    elseif ($operation == 'remove' && !empty($productExist)) {
      // Remove product from user ref data table.
      $this->userRefData->userRefDataDelete($uid, 'commerce_product', $product_entity_id, $product_entity_bundle);
      return new JsonResponse("Removed");
    }
    elseif (!empty($uid) && !empty($product) && !empty($productExist) && $operation == 'add') {
      // Product already saved to library.
      return new JsonResponse("Product already saved to library");
    }
    else {
      return new JsonResponse("No action");
    }
  }

  /**
   * Remove order item from my registered events.
   */
  public function removeFromLibrary($order_item_id, Request $request) {
    $current_user = $this->currentUser;
    // Get order item.
    $order_item = $this->entityTypeManager->getStorage('commerce_order_item')->load($order_item_id);

    // Check if field_product exist and external mail is empty.
    if ($order_item->field_product->target_id && $order_item->field_external_register_mail->value == '') {

      $product = $this->entityTypeManager->getStorage('commerce_product')->load($order_item->field_product->target_id);
      if ($product) {
        // Query the database for the user_ref ID.
        $query = $this->database->select('heart_user_ref_data', 'o')
          ->fields('o', ['id'])
          ->condition('o.uid', $current_user->id())
          ->condition('o.ref_entity_type', 'commerce_product');
        $result = $query->execute()->fetchField();

        // Load the entity using the ID and delete it.
        if ($result) {
          $user_ref_id = $result;
          // Load the user_ref entity.
          $user_ref = $this->entityTypeManager->getStorage('heart_user_ref_data')->load($user_ref_id);
          if ($user_ref) {
            // Delete the user_ref entity.
            $user_ref->delete();
          }
        }
      }
    }
    // Check if field_product exist and external mail is not empty.
    if ($order_item->field_product->target_id && $order_item->field_external_register_mail->value != '') {

      $product = $this->entityTypeManager->getStorage('commerce_product')->load($order_item->field_product->target_id);
      if ($product) {
        // Query the database for the heart_event_registrants ID.
        $query = $this->database->select('heart_event_registrants', 'o')
          ->fields('o', ['id'])
          ->condition('o.source_uid', $current_user->id())
          ->condition('o.registrant_email', $order_item->field_external_register_mail->value)
          ->condition('o.ref_entity_type', 'commerce_product')
          ->condition('o.ref_entity_id', $product->id())
          ->condition('o.ref_entity_bundle', 'events');
        $result = $query->execute()->fetchField();

        // If an ID is found, delete entry from the table.
        if ($result) {
          $this->database->delete('heart_event_registrants')
            ->condition('id', $result)
            ->execute();
        }

      }
    }
    // Check if order item exist.
    if ($order_item) {
      $order = $order_item->getOrder();
      $order_item->delete();

      // Check if the order is empty.
      $order_items = $order->getItems();
      if (empty($order_items)) {
        $order->delete();
      }
      // Success Response.
      return new JsonResponse(['success' => TRUE]);
    }
    else {

      return new JsonResponse(['success' => FALSE], 403);
    }
  }

  public function deleteOrderPaymentData(Request $request) {
    $uid = $this->request->getCurrentRequest()->request->get('user_id');
    $order_id = $this->request->getCurrentRequest()->request->get('order_id');

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);
    // Delete payment from order data.
    if ($order) {
      $payment = $order->get('payment_method')->referencedEntities();
      if ($payment) {
        reset($payment)->delete();
        $order->set('payment_method', NULL);
        $order->save();
      }
      return new JsonResponse("deleted");
    }
    else {
      // Return failed.
      return new JsonResponse('failed');
    }
  }
}
