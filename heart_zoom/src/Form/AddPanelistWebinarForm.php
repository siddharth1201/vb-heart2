<?php

declare(strict_types=1);

namespace Drupal\heart_zoom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\apitools\Api\Client\ClientBase;
use Drupal\apitools\ClientManagerInterface;
use Drupal\apitools\ClientResourceManagerInterface;
use Drupal\zoomapi\Plugin\ApiTools;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Url;

/**
 * Provides a Heart zoom form.
 */
final class AddPanelistWebinarForm extends FormBase
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The current path.
     *
     * @var \Drupal\Core\Path\CurrentPathStack
     */
    protected $currentPath;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * Get route parameter.
     *
     * @var Drupal\Core\Routing\RouteMatchInterface
     */
    protected $routematch;

    /**
     * Protected @var message message service.
     *
     * @var message
     */
    protected $message;

    protected $session;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, AccountInterface $current_user, RouteMatchInterface $route_match, MessengerInterface $message, SessionInterface $session)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentPath = $current_path;
        $this->currentUser = $current_user;
        $this->routematch = $route_match;
        $this->message = $message;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('path.current'),
            $container->get('current_user'),
            $container->get('current_route_match'),
            $container->get('messenger'),
            $container->get('session')
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'heart_zoom_panelist_webinar_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        // Client Manager.
        $client = \Drupal::service('zoomapi.client');

        // current route.
        $current_route = \Drupal::routeMatch()->getRouteName();

        // Get the input values from the request.
        $webinar_id = \Drupal::routeMatch()->getParameter('zoom_id');
        $entity_id = \Drupal::routeMatch()->getParameter('webinar_id');

        $count_panelist_data_paragraphs = '';
        $initial_panelist_data = [];

        if (!empty($webinar_id) && !empty($entity_id)) {
            $form_state->set('webinar_id', $webinar_id);
            $form_state->set('entity_id', $entity_id);

            if ($current_route == 'heart_zoom.edit_heart_panelist') {
                $response = $client->get('/webinars/' . $webinar_id . '/panelists');

                if ($response) {

                    // Check if panelist paragraph exist.
                    if (isset($response['panelists']) && !empty($response['panelists'])) {
                        $count_panelist_data_paragraphs = $response['total_records'];
                        $form_state->set('number_of_items', $count_panelist_data_paragraphs);
                        // Store initial paragraph IDs to delete if them if removed.
                        foreach ($response['panelists'] as $paragraph) {

                            $initial_panelist_data[] = [
                                'name' => $paragraph['name'],
                                'email' => $paragraph['email'],
                                'panelist_id' => $paragraph['id'],
                            ];
                        }
                        $form_state->set('initial_panelist_data', $initial_panelist_data);
                    }
                }
            }

        }
        $form['#cache'] = ['max-age' => 0];
        $form['fieldset_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'fieldset-wrapper'],
        ];

        $number_of_items = $form_state->get('number_of_items');
        if (empty($number_of_items)) {
            $number_of_items = 1;
            $form_state->set('number_of_items', $number_of_items);
        }
        for ($i = 0; $i < $number_of_items; $i++) {
            $form['fieldset_wrapper']['fieldset'][$i] = [
                '#type' => 'container',
                '#tree' => true,
            ];

            if ($current_route == 'heart_zoom.edit_heart_panelist' && !empty($initial_panelist_data)) {
                $form['fieldset_wrapper']['fieldset'][$i]['name'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Name'),
                    '#default_value' => isset($initial_panelist_data[$i]['name']) ? $initial_panelist_data[$i]['name'] : '',
                    '#required' => true,
                    '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
                ];

                $form['fieldset_wrapper']['fieldset'][$i]['email'] = [
                    '#type' => 'email',
                    '#title' => $this->t('Email'),
                    '#default_value' => isset($initial_panelist_data[$i]['email']) ? $initial_panelist_data[$i]['email'] : '',
                    '#required' => true,
                    '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled']
                ];
            } else {
                $form['fieldset_wrapper']['fieldset'][$i]['name'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Name'),
                    '#default_value' => isset($initial_panelist_data[$i]['name']) ? $initial_panelist_data[$i]['name'] : '',
                    '#required' => true,
                ];

                $form['fieldset_wrapper']['fieldset'][$i]['email'] = [
                    '#type' => 'email',
                    '#title' => $this->t('Email'),
                    '#default_value' => isset($initial_panelist_data[$i]['email']) ? $initial_panelist_data[$i]['email'] : '',
                    '#required' => true,
                ];
            }


            // Add remove button for each fieldset.
            if ($current_route == 'heart_zoom.edit_heart_panelist') {
                $form['fieldset_wrapper']['fieldset'][$i]['remove'] = [
                    '#type' => 'button',
                    '#value' => $this->t('Remove'),
                    '#name' => 'remove_' . $i,
                    '#attributes' => [
                        'class' => ['remove-panelist', 'remove-set-button'],
                        'data-panelist-id' => isset($initial_panelist_data[$i]['panelist_id']) ? $initial_panelist_data[$i]['panelist_id'] : '',
                        'data-heart-zoom-id' => isset($entity_id) ? $entity_id : '',
                        'data-webinar-id' => isset($webinar_id) ? $webinar_id : '',
                    ],
                ];
            } else {
                $form['fieldset_wrapper']['fieldset'][$i]['remove'] = [
                    '#type' => 'button',
                    '#value' => $this->t('Remove'),
                    '#name' => 'remove_' . $i,
                    '#attributes' => [
                        'class' => ['remove-panelist-visually', 'remove-set-button'],
                    ],
                ];
            }

        }

        $form['fieldset_wrapper']['add_more'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add more'),
            '#submit' => ['::addMore'],
            '#ajax' => [
                'callback' => '::addMoreCallback',
                'wrapper' => 'fieldset-wrapper',
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => $this->t('Save Panelist'),
            ],
        ];
        $form['back_to_previous'] = [
            '#type' => 'markup',
            '#title' => $this->t('Back to previous page'),
            '#markup' => '<a href="/zoom-webinar#upcoming-webinars-tab">' . $this->t('Back to upcoming webinars') . '</a>',
        ];

        return $form;
    }



    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        // @todo Validate the form here.
        parent::validateForm($form, $form_state);
        $values = $form_state->getValues();

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $values = $form_state->getValues();
        // Initialize the panelists data array.
        $panelists = [];
        // Collect all panelists' data.
        foreach ($values as $key => $value) {
            if (is_int($key) && !empty($value)) {
                $panelists[] = [
                'email' => $value['email'],
                'name' => $value['name'],
                ];
            }
        }

        // Construct the data payload.
        $data = [
        'json' => [
            'panelists' => $panelists,
        ],
        ];

        // Assuming $client is properly initialized and $webinar_id is defined.
        $client = \Drupal::service('zoomapi.client');
        $webinar_id = $this->routematch->getParameter('zoom_id');
        $webinar_entity_id = $this->routematch->getParameter('webinar_id');
        $heart_zoom_invite_panelists = [];

        // Send the API request.
        try {
            $response = $client->post("webinars/" .$webinar_id ."/panelists", $data);
            // Process the response as needed.
            if (isset($response['id']) && !empty($response['updated_at'])) {
                $heart_zoom_webinars = $this->entityTypeManager->getStorage('heart_zoom_webinars')->load($webinar_entity_id);

                foreach ($panelists as $panelist) {
                    $zoom_invite_panelists = $this->entityTypeManager->getStorage('paragraph')->create(
                        [
                        'type' => 'zoom_invite_panelists',
                        'field_email' => $panelist['email'],
                        'field_name' => $panelist['name'],
                        'field_ref_entity_id' => $webinar_entity_id,
                        ]
                    );

                    // Attach paragraph to heart_course entity.
                    $zoom_invite_panelists->setParentEntity($heart_zoom_webinars, 'panelist_data');
                    $zoom_invite_panelists->save();

                    $heart_zoom_invite_panelists[] = $zoom_invite_panelists->id();
                }

                $heart_zoom_webinars = $this->entityTypeManager->getStorage('heart_zoom_webinars')->load($webinar_entity_id);
                $heart_zoom_webinars->set('panelist_data', $heart_zoom_invite_panelists);
                $heart_zoom_webinars->save();

                $this->message->addMessage($this->t('Panelists have been added successfully.'));

                $url = Url::fromUserInput('/manage-content#calender-event-tab');

                // Redirect the form after submission to the URL.
                $form_state->setRedirectUrl($url);
            } else {
                $this->message->addError($this->t('Failed to add panelists.'));
            }
        } catch (\Exception $e) {
            $this->message->addError($this->t('An error occurred: @message', ['@message' => $e->getMessage()]));
        }
    }


    /**
     * Ajax callback to rebuild the form.
     */
    public function addMoreCallback(array &$form, FormStateInterface $form_state)
    {
        return $form['fieldset_wrapper'];
    }

    /**
     * Custom submit handler for the "Add more" button.
     */
    public function addMore(array &$form, FormStateInterface $form_state)
    {
        \Drupal::logger('heart_zoom')->notice('addMore triggered');

        $number_of_items = $form_state->get('number_of_items');
        $form_state->set('number_of_items', $number_of_items + 1);

        // Rebuild the form after increasing the number of items.
        $form_state->setRebuild();
    }

}
