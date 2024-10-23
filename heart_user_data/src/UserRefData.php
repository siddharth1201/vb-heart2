<?php

declare(strict_types=1);

namespace Drupal\heart_user_data;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface defining a user ref data entity type.
 */
final class UserRefData {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs the Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Connection $database
   *   Database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * Add user reference data.
   */
  public function userRefDataAdd($uid, $entity_type, $entity_id, $bundle = NULL): void {

    try {
      $custom_entity = $this->entityTypeManager->getStorage('heart_user_ref_data')->create([
        'user_id' => $uid,
        'ref_entity_type' => $entity_type,
        'ref_entity_id' => $entity_id,
        'ref_entity_bundle' => $bundle,
        'langcode' => 'en',
      ]);
      $custom_entity->save();
    }
    catch (\Exception $e) {
      throw $e;
    }

  }

  /**
   * Get user reference data.
   */
  public function userRefDataGet($uid = NULL, $entity_type = NULL, $bundle = NULL, $entity_id = NULL): array {

    try {
        $query = \Drupal::database()->select('heart_user_ref_data', 'urd');
        $query->fields('urd', ['id', 'user_id', 'ref_entity_type', 'ref_entity_id', 'ref_entity_bundle']);

        if (!empty($uid)) {
          $query->condition('urd.user_id', $uid);
        }

        if (!empty($entity_id)) {
          $query->condition('urd.ref_entity_id', $entity_id);
        }

        if (!empty($entity_type)) {
          $query->condition('urd.ref_entity_type', $entity_type);
        }

        if (!empty($bundle)) {
          $query->condition('urd.ref_entity_bundle', $bundle);
        }

        $results = $query->execute()->fetchAll();
        // user_ref_data.
        $user_ref_entities_data = [];
        foreach ($results as $key => $value) {
          $user_ref_entities_data[] = [
            'entity_id' => $value->id,
            'user_id' => $value->user_id,
            'entity_ref_entity_type' => $value->ref_entity_type,
            'entity_ref_entity_id' => $value->ref_entity_id,
            'entity_ref_entity_bundle' => $value->ref_entity_bundle,
          ];
        }
        return $user_ref_entities_data;

    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Delete user reference data.
   */
  public function userRefDataDelete($uid, $entity_type, $entity_id, $bundle = NULL): void {

    try {
      $query = \Drupal::database()->select('heart_user_ref_data', 'urd');
      $query->fields('urd', ['id']);
      $query->condition('urd.user_id', $uid);
      $query->condition('urd.ref_entity_type', $entity_type);
      $query->condition('urd.ref_entity_id', $entity_id);
      $result = $query->execute()->fetchAssoc();
      $entity_id = $result['id'];
      if (!empty($entity_id)) {
        $entity = $this->entityTypeManager->getStorage('heart_user_ref_data')->load($entity_id);
        $entity->delete();
      }
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

}
