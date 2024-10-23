<?php

declare(strict_types=1);

namespace Drupal\heart_misc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;


/**
 * Returns responses for Heart Misc routes.
 */
final class HeartMyVideoResourse extends ControllerBase {

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
   * The route match.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new HeartZoomEntityUpdateController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The route match.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UserRefData $userRefData, RequestStack $request, AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userRefData = $userRefData;
    $this->request = $request;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * Fetches the heart_video_resource entity by ID.
   *
   * @param int $product_id
   *   The ID of the product entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the entity data or an error message.
   */
  public function fetchHeartVideoResourceById($product_id) {

     // Load product.
     $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
     $product_entity_id = $product->id();
     $product_entity_bundle = $product->bundle();

     if ($product) {
      // Assume that the heart_video_resource is a referenced field in the commerce_product entity.
      $heartVideoResourceId = $product->get('field_referenced_video')->target_id;

      // Fetch the heart_video_resource entity.
      $heartVideoResource = $this->entityTypeManager->getStorage('heart_video_resource')->load($heartVideoResourceId);

      if ($heartVideoResource) {
        $video_src_url = $heartVideoResource->get('video_src_url')->value;
        $label = $heartVideoResource->get('label')->value;
        $visible_start_date = $heartVideoResource->get('visible_start_date')->value;
        $description = $heartVideoResource->get('description')->value;

        // Return the video_src_url in the JsonResponse.
        return [
          '#theme' => 'heart_my_video_resourse',
          '#video_src_url' => $video_src_url,
          '#label' =>  $label,
          '#visible_start_date' => $visible_start_date,
          '#description' => $description,
          '#title' => '',
          '#cache' => [
            'contexts' => ['user'],
          ],
        ];
      }
      else {
        return [
          '#markup' =>  $this->t('Webinar not found'),
          '#title' => '',
        ];
      }
    }
    else {
      return new JsonResponse(['error' => 'Product entity not found'], 404);
    }
  }
}
