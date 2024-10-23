<?php

declare(strict_types=1);

namespace Drupal\heart_zoom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Heart zoom routes.
 */
final class HeartZoomEntityUpdateController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The route match.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new HeartZoomEntityUpdateController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The route match.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, RequestStack $request, AccountProxyInterface $currentUser) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->request = $request;
    $this->currunt_user = $currentUser;
  }

  /**
   * Popup data custom.
   */
  public function updateZoomProfileEntity() {

    // Get the input values from the request.
    $input_val = $this->request->getCurrentRequest()->request->get('input_val');
    $name_val = $this->request->getCurrentRequest()->request->get('name_val');
    $lang_code = $this->request->getCurrentRequest()->request->get('lang');

    // Get the user id.
    $user_id = $this->currunt_user->id();
    // Get the entity id.
    $query = $this->database->select('zoom_profile_data_field_data', 'pd');
    $query->fields('pd', ['id']);
    $query->condition('pd.user_data', $user_id);
    $result = $query->execute()->fetchAssoc();
    $entity_id = $result['id'];

    // Update the entity.
    if (!empty($entity_id) && !empty($name_val)) {
      $custom_entity = $this->entityTypeManager
        ->getStorage('zoom_profile_data')
        ->load($entity_id);
      $custom_entity->set($name_val, $input_val);
      $custom_entity->set('langcode', $lang_code);
      $custom_entity->save();
      return new JsonResponse("success");
    }
    elseif (empty($entity_id) && !empty($name_val)) {
      $custom_entity = $this->entityTypeManager
        ->getStorage('zoom_profile_data')
        ->create([
          'user_data' => $user_id,
          $name_val => !empty($input_val) ? $input_val : '',
          'langcode' => $lang_code,
        ]);
      $custom_entity->save();

      // Return success.
      return new JsonResponse("success");
    }
    else {
      // Return failed.
      return new JsonResponse('failed');
    }
  }

}
