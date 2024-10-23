<?php

declare(strict_types=1);

namespace Drupal\heart_misc;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface defining a user ref data entity type.
 */
final class SendMailService {
  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
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
   * The mail manager.
   *
   * @var Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs the Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Connection $database
   *   Database connection.
   * @param Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, MailManagerInterface $mail_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('plugin.manager.mail'),
      $container->get('@messenger')
    );
  }

  /**
   * Your form submit function.
   */
  public function heartSendMail($email_template_entity_ids, $translate, $to) {
    foreach ($email_template_entity_ids as $email_template_id) {
      $email_template_entity = $this->entityTypeManager->getStorage('heart_email_template')->load($email_template_id);
      if ($email_template_entity) {
        // Get email template message by replacing template variables.
        $msg = strtr($email_template_entity->email_message->value, $translate);
        // Send email.
        $module = 'heart_webinar';
        // Replace with Your key.
        $key = 'heart_trigger_mail';
        $params['from'] = $email_template_entity->from_email->value;
        $params['subject'] = $email_template_entity->email_subject->value;
        $params['message'] = $msg;
        $langcode = 'en';
        $send = TRUE;
        // Send the mail and check if it was successful.
        $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

        if ($result['result']) {
          // Mark email as sent successfully.
          $email_sent = TRUE;
        }
        if ($email_sent) {
          \Drupal::messenger()->addStatus('Email sent successfully.');
        }
      }
    }
  }

}
