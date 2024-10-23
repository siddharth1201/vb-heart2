<?php
declare(strict_types=1);
namespace Drupal\heart_zoom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\apitools\Api\Client\ClientBase;
use Drupal\apitools\ClientManagerInterface;
use Drupal\apitools\ClientResourceManagerInterface;
use Drupal\zoomapi\Plugin\ApiTools;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Heart zoom routes.
 */
final class HeartZoomApiController extends ControllerBase
{

    /**
     * The route match.
     *
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    protected $request;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The user reference data creation.
     *
     * @var \Drupal\heart_user_data\UserRefData
     */
    protected $userRefData;

    /**
     * Constructs a new HeartDioceseController object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager.
     * @param \Drupal\heart_user_data\UserRefData            $userRefData
     *   The user reference data creation.
     * @param Symfony\Component\HttpFoundation\RequestStack  $request
     *   The route match.
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, UserRefData $userRefData, RequestStack $request)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->userRefData = $userRefData;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('heart_user_data.user_ref_data'),
            $container->get('request_stack'),
        );
    }

    public function testApi(RouteMatchInterface $route_match): array
    {
      $client = \Drupal::service('zoomapi.client');
      $webinarId = '83954019704';

      $page_size_data = 3;
      $reg_status_data = 'all';
      $webinar_list = [];
      $total_pages = '';
      $next_page_token = '';

      $webinar_list = $client->get('/past_webinars/'.$webinarId.'/participants?page_size='.$page_size_data.'');
      dump($webinar_list); exit;

      if ($page_size_data && $reg_status_data) {
        $webinar_list = $client->get('/past_webinars/'.$webinarId.'/participants?page_size='.$page_size_data.'&status='.$reg_status_data);
        dump($webinar_list); exit;
        if (isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
          $next_page_token = $webinar_list['next_page_token'];
        }
      }

      if (isset($webinar_list['page_size']) && !empty($webinar_list['page_size']) && (isset($webinar_list['total_records']) && !empty($webinar_list['total_records']))) {
        $total_pages = ceil($webinar_list['total_records'] / $webinar_list['page_size']);
      }

      if ($total_pages != '' && isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
        for ($i = 1; $i <= $total_pages; $i++) {
          $webinars = $client->get('/webinars/'.$webinarId.'/registrants?page_size='.$page_size_data.'&next_page_token='.$next_page_token);
          $webinar_list['full_list'][] = $webinars['registrants'];
          $next_page_token = $webinars['next_page_token'];
        }
      }
      $registrants = [];

      if (isset($webinar_list['full_list']) && !empty($webinar_list['full_list'])) {
        foreach ($webinar_list['full_list'] as $key => $value) {
          $registrants = array_merge($registrants, $value);
        }
      } else {
        $registrants = $webinar_list['registrants'];
      }

      // Create a temporary file
      $temp_file = tempnam(sys_get_temp_dir(), 'registrants');

      // Open the temporary file for writing
      $fp = fopen($temp_file, 'w');

      // Create the header row
      fputcsv($fp, ['First Name', 'Last Name', 'Status', 'Created At']);

      // Loop through each registrant and write desired data to CSV
      foreach ($registrants as $key => $value) {

        $data_to_write = [
          $value['first_name'],
          $value['last_name'],
          $value['status'],
          $value['create_time'],
        ];
        fputcsv($fp, $data_to_write);
      }

      // Close the file
      fclose($fp);
      // Force download
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="registrants.csv"');
      header('Content-Length: ' . filesize($temp_file));
      readfile($temp_file);
      // dump($file); exit;
      // Delete the temporary file
      unlink($temp_file);

      return [];
    }

    /**
     * Popup data custom.
     */
    public function deleteWebinar()
    {

        // Get the client.
        $client = \Drupal::service('zoomapi.client');

        // Get the input values from the request.
        $webinar_id = $this->request->getCurrentRequest()->request->get('webinar_id');
        $entity_id = $this->request->getCurrentRequest()->request->get('entity_id');

        // delete the entity.
        if (!empty($webinar_id) && !empty($entity_id)) {
            $response = $client->delete('/webinars/'.$webinar_id.'');
            $custom_entity = $this->entityTypeManager
                ->getStorage('heart_zoom_webinars')
                ->load($entity_id);
            $custom_entity->delete();
            return new JsonResponse("deleted");
        }
        else {
            // Return failed.
            return new JsonResponse('failed');
        }
    }

    /**
     * Popup data custom.
     */
    public function deletePanelist()
    {

        // Get the client.
        $client = \Drupal::service('zoomapi.client');

        // Get the input values from the request.
        $panelist_id = $this->request->getCurrentRequest()->request->get('panelist_id');
        $entity_id = $this->request->getCurrentRequest()->request->get('entity_id');
        $webinar_id = $this->request->getCurrentRequest()->request->get('webinar_id');

        // delete the entity.
        if (!empty($webinar_id) && !empty($entity_id) && !empty($panelist_id)) {

            $custom_entity = $this->entityTypeManager
                ->getStorage('heart_zoom_webinars')
                ->load($entity_id);
            $panelist_paragraph_data = $custom_entity->get('panelist_data')->getValue();

            $curr_paragraph_ids = [];

            foreach ($panelist_paragraph_data as $key => $value) {
                $curr_paragraph_ids[$value['target_id']] = $value['target_id'];
            }

            //Get the panelist data.
            $panelist_data = $client->get('/webinars/'.$webinar_id.'/panelists');

            foreach ($panelist_data['panelists'] as $key => $value) {
                $p_id = '';
                if ($value['id'] == $panelist_id) {
                    $paragraph = \Drupal::entityTypeManager()
                        ->getStorage('paragraph')
                        ->loadByProperties(
                            [
                            'type' => 'zoom_invite_panelists',
                            'field_email' => $value['email'],
                            'field_name' => $value['name'],
                            'field_ref_entity_id' => $entity_id
                            ]
                        );

                    $paragraph = reset($paragraph);
                    if ($paragraph) {
                      $p_id = $paragraph->id();
                      $paragraph->delete();
                    }

                    unset($curr_paragraph_ids[$p_id]);
                    $custom_entity->set('panelist_data', $curr_paragraph_ids);
                    $custom_entity->save();

                }
            }

            $response = $client->delete('/webinars/'.$webinar_id.'/panelists/'.$panelist_id.'');
            return new JsonResponse("deleted");
        }
        else {
            // Return failed.
            return new JsonResponse('failed');
        }
    }

    public function deleteReport() {
      $file_name = $this->request->getCurrentRequest()->request->get('file_name');
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $file_path = explode($host.'/', $file_name);
      unlink($file_path[1]);
      return new JsonResponse("deleted");
    }
}
