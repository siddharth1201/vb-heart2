<?php

declare(strict_types=1);

namespace Drupal\heart_zoom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a zoom profile block.
 *
 * @Block(
 *   id = "heart_zoom_profile",
 *   admin_label = @Translation("Zoom profile"),
 *   category = @Translation("Custom"),
 * )
 */
final class ZoomProfileBlock extends BlockBase implements
  ContainerFactoryPluginInterface {
  /**
   * Protected  Validation service.
   *
   * @var formBuilder
   */
  protected $formbuilder;

  /**
   * Dependency injection.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $formbuilder) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formbuilder = $formbuilder;

  }

  /**
   * Create container.
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    // Render the form for zoom profile.
    $form = $this->formbuilder->getForm('\Drupal\heart_zoom\Form\ZoomProfileForm');

    $build = [
      'content' => ['zoom_profile_form' => $form],
    ];

    return $build;
  }

}
