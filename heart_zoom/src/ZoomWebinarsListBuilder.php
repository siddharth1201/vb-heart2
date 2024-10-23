<?php

declare(strict_types=1);

namespace Drupal\heart_zoom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the zoom webinars entity type.
 */
final class ZoomWebinarsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

      
      

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink($entity->label())->toString();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $row['uid'] = $this->t('<a href="@url">@name</a>', [
      '@url' => $entity->getOwner()->toUrl()->toString(),
      '@name' => $entity->getOwner()->getAccountName(),
    ]);
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value);
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->get('changed')->value);
    return $row + parent::buildRow($entity);
  }

}





