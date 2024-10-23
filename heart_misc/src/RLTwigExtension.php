<?php

namespace Drupal\heart_misc;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\heart_user_data\UserRefData;
use Drupal\heart_webinar\EventRegistrantsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class RLTwigExtension extends AbstractExtension {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
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
   * The route match.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The EventRegistrantsService.
   *
   * @var Drupal\heart_webinar\EventRegistrantsService
   */
  protected $eventRegistrantsService;

  /**
   * Constructs a new RLTwigExtension object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UserRefData $userRefData, AccountProxyInterface $currentUser, EventRegistrantsService $event_registrants_service) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userRefData = $userRefData;
    $this->currentUser = $currentUser;
    $this->eventRegistrantsService = $event_registrants_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('current_user'),
      $container->get('heart_webinar.event_registrants_service')
    );
  }

  /**
   * In this function we can declare the extension function.
   */
  public function getFunctions() {

    return [
      new TwigFunction('rl_render_button', [$this, 'rlRenderButton'], ['is_safe' => ['html']]),
      new TwigFunction('upcoming_event_button', [$this, 'upcompingEventRegistered'], ['is_safe' => ['html']]),
      new TwigFunction('upcoming_event_button_calendar', [$this, 'upcompingEventRegisteredCalendar'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Render button according to user.
   */
  public function rlRenderButton($entity_id, $item_cost) {

    // Get the user id.
    $user_id = $this->currentUser->id();

    // Get the product id from string.
    $pid = (int) $entity_id->__toString();
    $item_cost_val = $item_cost->__toString();

    // Load product.
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($pid);
    $product_entity_id = $product->id();
    $product_entity_bundle = $product->bundle();

    // Check if product already exists in user ref data table.
    if ($user_id) {
      $productExist = $this->userRefData->userRefDataGet($user_id, 'commerce_product', $product_entity_bundle, $product_entity_id);
    }
    if (strtolower($item_cost_val) == 'complimentary' && empty($productExist)) {
      return '<a href="/heart-misc/heart-action-to-library" data-op="add" data-pid="' . $pid . '" class="button--action-to-library save-to-my-library button button--primary">' . t('save to my library') . '</a>';
    }
    elseif (!empty($productExist)) {
      if (isset($productExist[0]['user_id']) && $productExist[0]['user_id'] == $user_id) {
        return '<a href="/heart-misc/heart-action-to-library" data-pid="' . $pid . '" class="button--action-to-library saved-to-my-library button btn btn-green default-cursor"><span class="fa fa-check m-right-2 v-align-middle"></span>' . t('saved to my library') . '</a>';
      }
    }
  }

  /**
   * Check if event is registered by the user.
   */
  public function upcompingEventRegistered($entity_id) {
    // Get the user id.
    $user_id = $this->currentUser->id();
    $pid = (int) $entity_id->__toString();
    // Load product.
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($pid);

    // Check if event is registered for the user.
    if ($user_id) {
      $productExist = $this->userRefData->userRefDataGet($user_id, 'commerce_product', $product->bundle(), $product->id());
    }
    else {
      return '<a href="/webinar/' . $pid . '">' . t('View Event') . '</a>';
    }

    if (empty($productExist)) {
      return '<a href="/webinar/' . $pid . '" class="btn btn-secondary btn-small">' . t('Register Now!') . '</a>';
    }
    elseif (!empty($productExist)) {
      if (isset($productExist[0]['user_id']) && $productExist[0]['user_id'] == $user_id) {
        return '<a href="#" onclick="return false;" class="button btn btn-green btn-small"><span class="fa fa-check m-right-2 v-align-middle"></span>' . t('Registered!') . '</a>';
      }
    }
  }

  /**
   * Check if event is registered by the user.
   */
  public function upcompingEventRegisteredCalendar($entity_id) {
    // Get the user id.
    $user_id = $this->currentUser->id();
    $pid = (int) $entity_id->__toString();
    // Load product.
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($pid);
    $registered = FALSE;
    // Check if event is registered for the user.
    if ($user_id) {
      $productExist = $this->userRefData->userRefDataGet($user_id, 'commerce_product', $product->bundle(), $product->id());
      // Product registered by user using other email.
      $externalRegisterExist = $this->eventRegistrantsService->eventRegistrantsGet($user_id, 'commerce_product', $product->bundle(), $product->id());
      if ($productExist || $externalRegisterExist) {
        $registered = TRUE;
      }
    }
    else {
      return '<a href="/webinar/' . $pid . '">' . $this->t('View event') . '</a>';
    }

    if (!$registered) {
      return '<a href="/webinar/' . $pid . '" class="btn btn-primary">' . t('view event to register') . '</a>';
    }
    elseif ($registered) {
      return '<a href="#" onclick="return false;" class="button btn btn-green"><span class="fa fa-check m-right-2 v-align-middle"></span>' . t('Registered!') . '</a>';
    }
  }

}
