<?php

declare(strict_types=1);

namespace Drupal\heart_misc\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Heart Misc settings for this site.
 */
final class DioceseImportForm extends ConfigFormBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DioceseImportForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, FileSystemInterface $file_system, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityRepository = $entity_repository;
    $this->fileSystem = $file_system;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('file_system'),
      $container->get('language_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_misc_diocese_import';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['heart_misc.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Add an upload field.
    $form['excel_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Diocese List'),
      '#description' => $this->t('Upload Diocese List Excel document.'),
      '#upload_location' => 'public://uploads/excel',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
      // '#default_value' => $this->config('heart_misc.settings')->get('excel_upload'),
    ];

    // Add sample document.
    $form['sample_document'] = [
      '#type' => 'link',
      '#title' => $this->t('Download Sample'),
      '#url' => Url::fromUri('internal:/modules/custom/heart_misc/sample_files/SampleDiocese.csv'),
      '#attributes' => [
        'download' => 'sample_document.csv',
      ],
      '#options' => [
        'attributes' => ['target' => '_blank'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Validate uploaded file.
    $file = $form_state->getValue('excel_upload');
    if (!empty($file)) {
      // Load the file entity.
      /** @var \Drupal\file\Entity\FileInterface $file_entity */
      $file_entity = $this->entityTypeManager->getStorage('file')->load($file[0]);
      $file_path = $file_entity->getFileUri();
      $headers = [
        'DioceseName',
        'DioceseAddress',
        'DioceseAddress2',
        'DioceseCity',
        'DioceseState',
        'DiocesePostal',
        'DioceseCountry',
        'DioceseWebsite',
      ];

      // File must have proper headers.
      $row = 0;
      if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
          if ($row == 0) {
            if ($headers !== $data) {
              $form_state->setErrorByName('excel_upload', $this->t('Invalid header row in the uploaded file.'));
            }
          }
          $row++;
        }
        fclose($handle);
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Save uploaded file.
    $lang_code = $this->languageManager->getCurrentLanguage()->getId();
    $file = $form_state->getValue('excel_upload');

    // Read the CSV file and create entities.
    if (!empty($file)) {
      // Load the file entity.
      $file_entity = $this->entityTypeManager->getStorage('file')->load($file[0]);
      $file_path = $file_entity->getFileUri();
      $row = 0;
      if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
          if ($row != 0) {
            $country = $this->getCountryCode(strtoupper($data[6]));
            $custom_entity = $this->entityTypeManager
              ->getStorage('heart_diocese_data')
              ->create([
                'label' => !empty($data[0]) ? $data[0] : '',
                'diocese_address' => [
                  'country_code' => !empty($country) ? $country : '',
                  'address_line1' => !empty($data[1]) ? $data[1] : '',
                  'address_line2' => !empty($data[2]) ? $data[2] : '',
                  'locality' => !empty($data[3]) ? $data[3] : '',
                  'administrative_area' => !empty($data[4]) ? $data[4] : '',
                  'postal_code' => !empty($data[5]) ? $data[5] : '',
                ],
                'diocese_website' => !empty($data[7]) ? $data[7] : '',
                'langcode' => $lang_code,
              ]);
            $custom_entity->save();
          }
          $row++;
        }
        fclose($handle);
      }
    }
    $this->config('heart_misc.settings')
      ->set('excel_upload', $form_state->getValue('excel_upload'))
      ->save();
    $this->messenger()->addMessage($this->t("Diocese Created Successfully."));
    parent::submitForm($form, $form_state);
  }

  /**
   * Return country code.
   */
  public function getCountryCode($countryName) {
    $countryCode = [
      "CANADA" => "CA",
      "USA" => "US",
    ];
    return $countryCode[$countryName];
  }

}
