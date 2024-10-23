<?php

declare(strict_types=1);

namespace Drupal\heart_zoom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a zoom webinars entity type.
 */
interface ZoomWebinarsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
