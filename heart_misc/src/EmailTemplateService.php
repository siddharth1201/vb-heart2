<?php

declare(strict_types=1);

namespace Drupal\heart_misc;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface defining a user ref data entity type.
 */
final class EmailTemplateService {
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
      $container->get('database')
    );
  }

  /**
   * Get user event registrants apart from the registered site users.
   */
  public function emailTemplateIdsByTermName($term_name): array {
    // Query to get the ID from taxonomy_term_field_data table.
    $query = $this->database->select('taxonomy_term_field_data', 'n')
      ->fields('n', ['tid'])
      ->condition('n.name', $term_name, '=')
      ->condition('n.status', 1, '=');

    // Execute the query and fetch the ID.
    $term_id = $query->execute()->fetchField();

    // Template IDs array.
    $email_template_ids = [];

    // Check if we have a valid term ID.
    if ($term_id) {
      // Query to get matching IDs from heart_email_template_field_data table.
      $template_query = $this->database->select('heart_email_template_field_data', 'h')
        ->fields('h', ['id'])
        ->condition('h.trigger_action', $term_id, '=');

      // Execute the query and fetch the matching template IDs.
      $email_template_ids = $template_query->execute()->fetchCol();

    }

    // Return the email template IDs.
    return $email_template_ids;
  }

}
