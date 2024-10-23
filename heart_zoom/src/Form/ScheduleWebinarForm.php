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
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a Heart zoom form.
 */
final class ScheduleWebinarForm extends FormBase
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
     * The route match.
     *
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, AccountInterface $current_user, RouteMatchInterface $route_match, MessengerInterface $message, SessionInterface $session, RequestStack $request)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentPath = $current_path;
        $this->currentUser = $current_user;
        $this->routematch = $route_match;
        $this->message = $message;
        $this->session = $session;
        $this->request = $request;
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
            $container->get('session'),
            $container->get('request_stack'),
            $container->get('current_route_match'),
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'heart_zoom_schedule_webinar_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {

        $client = \Drupal::service('zoomapi.client');

        // Get the input values from the request.
        $webinar_id = \Drupal::routeMatch()->getParameter('webinar_id');
        $entity_id = \Drupal::routeMatch()->getParameter('entity_id');

        // current route.
        $current_route = \Drupal::routeMatch()->getRouteName();

        // Declare variables.
        $agenda = '';
        $duration = '';
        $allow_multiple_devices = '';
        $show_share_button = '';
        $registrants_restrict_number = '';
        $practice_session = '';
        $start_time = '';
        $timezone = '';
        $topic = '';
        $duration_hrs = '';
        $duration_mins = '';
        $show_join_info = '';
        $panelist_status = false;

        // Get the webinar details.
        if (!empty($webinar_id)) {
            $response = $client->get('/webinars/' . $webinar_id);
            $panelist = $client->get('/webinars/' . $webinar_id . '/panelists');
            if ($response) {
                $agenda = $response["agenda"];
                $duration = $response["duration"];
                $allow_multiple_devices = $response["settings"]["allow_multiple_devices"];
                $show_share_button = $response["settings"]["show_share_button"];
                $registrants_restrict_number = $response["settings"]["registrants_restrict_number"];
                $practice_session = $response["settings"]["practice_session"];
                $start_time = $response["start_time"];
                $timezone = $response["timezone"];
                $topic = $response["topic"];
                $registration_req = $response["topic"];
                $show_join_info = $response["settings"]["show_join_info"];
            }
            if (isset($panelist['total_records']) && $panelist['total_records'] > 0) {
                $panelist_status = true;
            }
            // Get duration in hrs and mins.
            if ($duration) {
                $duration_hrs = floor($duration / 60);
                $duration_mins = $duration % 60;
            }
            if ($start_time) {
                $start_time = DrupalDateTime::createFromTimestamp(strtotime($start_time), $timezone);
            }
        }

        // Call theme for thia form.

        $form['#theme'] = 'heart_zoom_schedule_webinar_form';
        $form['#cache'] = ['max-age' => 0];

        $form['topic'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Topic'),
          '#default_value' => $topic,
          '#required' => true,
        ];

        $form['description'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Description'),
          '#default_value' => $agenda,
        ];

        if ($current_route == 'heart_zoom.edit_heart_schedule') {
          $form['when'] = [
            '#type' => 'datetime',
            '#title' => $this->t('When'),
            '#default_value' => $start_time,
            '#date_date_format' => 'Y-m-d',
            '#date_time_format' => 'H:i:s',
            '#attributes' => ['step' => 0],
            '#date_increment' => 15,
            '#required' => true,
            '#date_timezone' => $timezone,
            //  '#prefix' => '<div class="duration-select form-item form-inline"><span class="d-inline-block font-size-18 label">' .$this->t('When'). '</span>',
            // '#suffix' => '</div>',
          ];
        }
        else {
          // Wrapper for AJAX update.

          // $form['datetime_wrapper'] = [
          //     '#type' => 'container',
          //     '#attributes' => ['id' => 'webinar-datetime-wrapper'],
          // ];

          // Start time field inside the wrapper.
          $form['when'] = [
            '#type' => 'datetime',
            //'#title' => $this->t('When'),
            '#default_value' => $start_time,
            '#date_date_format' => 'Y-m-d',
            '#date_time_format' => 'H:i:s',
            '#attributes' => ['step' => 0],
            '#date_increment' => 15,
            '#required' => true,
            '#prefix' => '<div class="duration-select form-item form-inline"><span class="d-inline-block font-size-18 label">' .$this->t('When'). '</span>',
            '#suffix' => '</div>',
          ];
        }

        $hours = [];

        for ($i = 0; $i <= 24; $i++) {
            $hours[$i] = $i;
        }
        if ($current_route == 'heart_zoom.edit_heart_schedule') {
          $form['duration_wrapper']['duration_hr'] = [
            '#type' => 'select',
            '#title' => $this->t('Duration'),
            '#default_value' => $duration_hrs,
            '#options' => $hours,
            '#required' => true,
            '#description' => $this->t('<span class="font-size-18">hr</span>'),
          ];

          $form['duration_wrapper']['duration_mins'] = [
            '#type' => 'select',
            '#default_value' => $duration_mins,
            '#options' => [
                '0' => '0',
                '15' => '15',
                '30' => '30',
                '45' => '45',
            ],
            '#description' => $this->t('<span class="font-size-18">min</span>'),
          ];
        }
        else {
          $form['duration_wrapper'] = [
            '#prefix' => '<div class="duration-select form-item form-inline"><span class="d-inline-block font-size-18 label">' .$this->t('Duration'). '</span>',
            '#suffix' => '</div>',
          ];
          $form['duration_wrapper']['duration_hr'] = [
            '#type' => 'select',
            '#default_value' => $duration_hrs,
            '#options' => $hours,
            '#required' => true,
            '#description' => $this->t('<span class="font-size-18">hr</span>'),
          ];

          $form['duration_wrapper']['duration_mins'] = [
            '#type' => 'select',
            '#default_value' => $duration_mins,
            '#options' => [
                '0' => '0',
                '15' => '15',
                '30' => '30',
                '45' => '45',
            ],
            '#description' => $this->t('<span class="font-size-18">min</span>'),
          ];
        }

        // Get the list of timezones.
        $timezones = system_time_zones();

        // Remove the USA timezone from the list.
        // You can change this to the appropriate USA timezone.
        $usa_timezone = 'America/New_York';
        unset($timezones[$usa_timezone]);

        // Add the USA timezone to the top of the list.
        $timezones = [$usa_timezone => $usa_timezone] + $timezones;

        if ($current_route == 'heart_zoom.edit_schedule_webinar') {
          $form['timezone'] = [
            '#type' => 'select',
            '#title' => $this->t('Timezone'),
            '#options' => $timezones,
            '#chosen' => true,
            '#default_value' => $timezone,
            '#required' => true,
            ];
        } else {
          $form['timezone'] = [
            '#type' => 'select',
            '#title' => $this->t('Timezone'),
            '#options' => $timezones,
            '#default_value' => $timezone,
            '#required' => true,
            // '#ajax' => [
            //     'callback' => '::updateDatetimeTimezone',
            //     'event' => 'change',
            //     'wrapper' => 'webinar-datetime-wrapper',
            // ],
          ];
        }
        // $form['other_options_registrants'] = [
        //   '#type' => 'checkbox',
        //   '#title' => $this->t('Restrict number of registrants'),
        //   '#default_value' => empty($registrants_restrict_number) ? 0 : 1,
        // ];

        // $form['other_options_registrants_limit'] = [
        //   '#type' => 'textfield',
        //   '#title' => $this->t('Registrants limit'),
        //   '#default_value' => $registrants_restrict_number,
        //   '#states' => [
        //     'visible' => [
        //       ':input[name="other_options_registrants"]' => ['checked' => true],
        //     ],
        //   ],
        // ];

        $form['other_options_multiple_devices'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow panelists and attendees to join from multiple devices'),
          '#default_value' => $allow_multiple_devices,
        ];
        // $form['other_options_resitration_required'] = [
        //   '#type' => 'checkbox',
        //   '#title' => $this->t('Registration required'),
        //   '#default_value' => $allow_multiple_devices,
        //   ];
        $form['other_options_practice_session'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable practice session'),
          '#default_value' => $practice_session,
        ];

        // $form['other_options_join_info'] = [
        //   '#type' => 'checkbox',
        //   '#title' => $this->t('Show join info on registration confirmation page'),
        //   '#default_value' => $show_join_info,
        // ];

        $form['other_options_socialshare'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show social share buttons on registration page'),
          '#default_value' => $show_share_button,
        ];

        // $form['other_options_disclaimer'] = [
        //   '#type' => 'checkbox',
        //   '#title' => $this->t('Enable Disclaimer'),
        // ];

        if ($current_route != 'heart_zoom.edit_heart_schedule') {

            $templates = $client->get('users/me/webinar_templates');

            $template_options = [];

            if (isset($templates['templates'])) {
                foreach ($templates['templates'] as $template) {
                    $template_options[$template['id']] = $template['name'];
                }
            }

            $form['webinar_templates'] = [
            '#type' => 'select',
            '#title' => $this->t('Templates'),
            '#options' => ['None'] + $template_options,
            '#chosen' => true,
            '#default_value' => '',
          ];
        }

        $passcode_title = '';
        if ($current_route == 'heart_zoom.edit_heart_schedule') {
            $passcode_title = $this->t('Edit Passcode?');
        } else {
            $passcode_title = $this->t('Require webinar passcode');
        }
        $form['other_options_passcode_require'] = [
          '#type' => 'checkbox',
          '#title' => $passcode_title,
        ];

        $form['other_options_passcode_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Passcode'),
          '#maxlength' => 10,
          '#states' => [
            'visible' => [
              ':input[name="other_options_passcode_require"]' => ['checked' => true],
            ],
          ],
        ];

        $form['other_options_add_panelists'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Add panelists'),
          '#default_value' => $panelist_status,
        ];

        if ($current_route == 'heart_zoom.edit_heart_schedule') {
          $submit_button = 'Update zoom webinar';
        } else {
          $submit_button = 'Create zoom webinar and go to schedule HEART event';
        }
        $form['actions'] = [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t(''.$submit_button.''),
            '#help' => $this->t('If the add panelists checkbox is checked, the page will be redirected to the add panelists page.'),
          ],
          'cancel' => [
            '#markup' => t('<a class="btn btn-secondary" href="/manage-content/zoom-webinars">Cancel</a>'),
          ]
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

        if (empty($values['when']) && empty($values['timezone'])) {
            $form_state->setErrorByName('when', $this->t('Please provide a date time and timezone.'));
        }

        if ($values['when']->getTimestamp() < time()) {
            $form_state->setErrorByName('when', $this->t('Please provide a future date time.'));
        }

        if (empty($values['topic'])) {
            $form_state->setErrorByName('topic', $this->t('Please provide a topic.'));
        }

        // if ($values['other_options_registrants'] && empty($values['other_options_registrants_limit'])) {
        //     $form_state->setErrorByName('other_options_registrants_limit', $this->t('Please provide a registrants limit.'));
        // }

        if (empty($values['duration_hr']) && empty($values['duration_mins'])) {
            $form_state->setErrorByName('duration_hr', $this->t('Please provide a duration.'));
        }

        if (isset($values['other_options_passcode_require']) && $values['other_options_passcode_require'] == true && empty($values['other_options_passcode_text'])) {
            $form_state->setErrorByName('other_options_passcode_text', $this->t('Please provide a passcode.'));
        }

        if (strlen($values['other_options_passcode_text']) > 10) {
            $form_state->setErrorByName('other_options_passcode_text', $this->t('Passcode must be less than or equal to 10 characters.'));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {

        $uid = $this->currentUser->id();

        $values = $form_state->getValues();
        $client = \Drupal::service('zoomapi.client');
        $registration_req = false;
        $registration_url = '';

        // current route.
        $current_route = \Drupal::routeMatch()->getRouteName();

        $webinar_id = \Drupal::routeMatch()->getParameter('webinar_id');
        $entity_id = \Drupal::routeMatch()->getParameter('entity_id');
        $webinar_templates = '';
        $topic = $values['topic'];
        $description = $values['description'];
        $when = $values['when'];
        $duration = $values['duration_hr']*60 + $values['duration_mins'];
        $timezone = $values['timezone'];
        // $other_options_registrants = $values['other_options_registrants'];
        // $other_options_registrants_limit = $values['other_options_registrants_limit'] ?: 0;
        $other_options_multiple_devices = $values['other_options_multiple_devices'];
        // $other_options_join_info = $values['other_options_join_info'];
        $other_options_socialshare = $values['other_options_socialshare'];
        // $other_options_disclaimer = $values['other_options_disclaimer'];
        if ($current_route != 'heart_zoom.edit_heart_schedule') {
            $webinar_templates = $values['webinar_templates'] ?: '';
        }

        $other_options_passcode_require = $values['other_options_passcode_require'];
        $other_options_passcode_text = $values['other_options_passcode_text'];
        $other_options_add_panelists = $values['other_options_add_panelists'];
        $other_options_practice_session = $values['other_options_practice_session'];

        // Initialize the data array.
        $data = [
        'json' =>
        [
          "agenda" => $description,
          "duration" => $duration,
          "password" => $other_options_passcode_require ? $other_options_passcode_text : null,
          "settings"=> [
            "allow_multiple_devices"=> $other_options_multiple_devices,
            "auto_recording"=> "cloud",
            "show_share_button"=> $other_options_socialshare,
            "survey_url"=> \Drupal::request()->getSchemeAndHttpHost(),
            // "registrants_restrict_number"=> $other_options_registrants_limit,
            "practice_session" => $other_options_practice_session,
          ],
          "start_time"=> $when->format('Y-m-d\TH:i:s'),
          "template_id"=> $webinar_templates,
          "timezone"=> $timezone,
          "topic"=> $topic,
        ],
        ];
        // Check route.
        if ($current_route == 'heart_zoom.edit_heart_schedule') {
            $response = $client->patch("/webinars/$webinar_id", $data);

            if (isset($response['registration_url']) && $response['registration_url']) {
              $registration_req = true;
              $registration_url = $response['registration_url'];
            }

            if ($entity_id) {
                $heart_zoom_webinars_entity = $this->entityTypeManager->getStorage('heart_zoom_webinars')->load($entity_id);

                $heart_zoom_webinars_entity->set('label', $topic);
                $heart_zoom_webinars_entity->set('start_time', $when->getTimestamp());
                $heart_zoom_webinars_entity->set('duration', $duration);
                $heart_zoom_webinars_entity->set('start_date', $when);
                $heart_zoom_webinars_entity->set('start_only_date', $when->format('Y-m-d'));
                $heart_zoom_webinars_entity->set('timezone', $timezone);
                $heart_zoom_webinars_entity->set('agenda', $description);
                $heart_zoom_webinars_entity->set('passcode', $other_options_passcode_text);
                $heart_zoom_webinars_entity->set('registration_required', $registration_req);
                $heart_zoom_webinars_entity->set('registration_url', $registration_url);
                $heart_zoom_webinars_entity->set('uid', $uid);
                $heart_zoom_webinars_entity->save();

                $this->message->addMessage('Webinar has been updated.');

                if ($other_options_add_panelists == true) {
                    $parameters = [
                    'zoom_id' => $webinar_id,
                    'webinar_id' => $entity_id,
                    ];
                    $form_state->setRedirect('heart_zoom.edit_heart_panelist', $parameters);
                } else {
                  // Define the URL with the fragment (hash) part.
                  $url = Url::fromUserInput('/manage-content?id=' . $webinar_id . '#calender-event-tab');

                  // Redirect the form after submission to the URL.
                  $form_state->setRedirectUrl($url);
                }
            }

        } else {
            $response = $client->post("users/me/webinars", $data);
            if (isset($response['id'])) {
                if (!empty($when)) {
                  $set_time = new DrupalDateTime($when, new \DateTimeZone('UTC'));
                  $set_time = $set_time->setTimezone(new \DateTimeZone($timezone));
                }

                if (isset($response['registration_url']) && $response['registration_url']) {
                  $registration_req = true;
                  $registration_url = $response['registration_url'];
                }

                $heart_zoom_webinars_entity = $this->entityTypeManager
                    ->getStorage('heart_zoom_webinars')
                    ->create(
                        [
                        'label' => $response['topic'] ?: '',
                        'zoom_uuid' => $response['uuid'] ?: '',
                        'zoom_id' => $response['id'] ?: '',
                        'host_id' => $response['host_id'] ?: '',
                        'host_email' => $response['host_email'] ?: '',
                        'start_time' => $when->getTimestamp() ?: '',
                        'duration' => $response['duration'] ?: '',
                        'start_date' => $set_time ?: '',
                        'start_only_date' => $when->format('Y-m-d') ?: '',
                        'timezone' => $response['timezone'] ?: '',
                        'agenda' => isset($response['agenda_on']) ? $response['agenda_on'] : '',
                        'start_url' => $response['start_url'] ?: '',
                        'join_url' => $response['join_url'] ?: '',
                        'registration_required' => $registration_req,
                        'registration_url' => $registration_url ?: '',
                        'passcode' => $other_options_passcode_text ?: '',
                        'panelist_data' => '',
                        'uid' => $uid,
                        ]
                    );
                $heart_zoom_webinars_entity->save();

                $this->message->addMessage('Webinar has been created.');

                if ($other_options_add_panelists == true) {
                    $parameters = [
                    'zoom_id' => $response['id'],
                    'webinar_id' => $heart_zoom_webinars_entity->id(),
                    ];
                    $form_state->setRedirect('heart_zoom.heart_panelist', $parameters);
                } else {
                  // Define the URL with the fragment (hash) part.
                  $url = Url::fromUserInput('/manage-content#calender-event-tab');

                  // Redirect the form after submission to the URL.
                  $form_state->setRedirectUrl($url);
                }
            } else {
                $this->message->addError('Webinar has not been created.');
            }
        }


    }

  /**
   * AJAX callback to update the datetime timezone.
   */
  public function updateDatetimeTimezone(array &$form, FormStateInterface $form_state) {
    return $form['datetime_wrapper'];
  }
}
