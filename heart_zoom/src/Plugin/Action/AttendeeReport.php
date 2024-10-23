<?php

namespace Drupal\heart_zoom\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Render\Markup;
use Drupal\apitools\Api\Client\ClientBase;
use Drupal\apitools\ClientManagerInterface;
use Drupal\apitools\ClientResourceManagerInterface;
use Drupal\zoomapi\Plugin\ApiTools;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Action description.
 *
 * @Action(
 *   id = "heart_zoom_attendee_report",
 *   label = @Translation("Attendee report"),
 *   type = ""
 * )
 */
class AttendeeReport extends ViewsBulkOperationsActionBase
{

    use StringTranslationTrait;
    const EXTENSION = 'csv';

    /**
     * {@inheritdoc}
     */
    public function execute($entity = null)
    {

        \Drupal::logger('heart_zoom')->info('Execute function triggered for user @user with entity @entity', [
            '@user' => \Drupal::currentUser()->getAccountName(),
            '@entity' => $entity ? $entity->label() : 'No entity',
        ]);

        if ($entity) {

            $user_id = \Drupal::currentUser()->id();

            $store = \Drupal::service('tempstore.private')->get('heart_zoom');
            $client = \Drupal::service('zoomapi.client');
            $webinarId = $entity->get('zoom_id')->getString();
            // \Drupal::logger('VBO')->info('<code>' . print_r($webinarId, true) . '</code>');

            $page_size_data = 300;
            $webinar_list = [];
            $total_pages = '';
            $next_page_token = '';

            if ($page_size_data) {
                $webinar_list = $client->get('/past_webinars/'.$webinarId.'/participants?page_size='.$page_size_data.'');

                if (isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
                    $next_page_token = $webinar_list['next_page_token'];
                }
            }

            if (isset($webinar_list['page_size']) && !empty($webinar_list['page_size']) && (isset($webinar_list['total_records']) && !empty($webinar_list['total_records']))) {
                $total_pages = ceil($webinar_list['total_records'] / $webinar_list['page_size']);
            }

            if ($total_pages != '' && isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $webinars = $client->get('/past_webinars/'.$webinarId.'/participants?page_size='.$page_size_data.'');
                    $webinar_list['full_list'][] = $webinars['participants'];
                    $next_page_token = $webinars['next_page_token'];
                }
            }

            $participants = [];

            if (isset($webinar_list['full_list']) && !empty($webinar_list['full_list'])) {
                foreach ($webinar_list['full_list'] as $key => $value) {
                    $participants = array_merge($participants, $value);
                }
            } else {
                $participants = $webinar_list['participants'];
            }

            if (!empty($participants)) {

                $public_path = PublicStream::basepath();

                $file_name = $webinarId . '_participants.csv';

                $file_path = $public_path ."/report/". $file_name . "";

                $dirname = dirname($file_path);
                if (!is_dir($dirname)) {
                  mkdir($dirname, 0755, TRUE);
                }

                $fp = fopen($file_path, 'w');

                // Create the header row
                fputcsv($fp, ['Name', 'Email', 'Join time', 'Leave time', 'Duration', 'Status']);

                // Loop through each registrant and write desired data to CSV
                foreach ($participants as $key => $value) {
                    $data_to_write = [
                    $value['name'],
                    $value['user_email'],
                    $value['join_time'],
                    $value['leave_time'],
                    $value['duration'],
                    $value['status'],
                    ];
                    fputcsv($fp, $data_to_write);
                }

                // Close the file
                fclose($fp);

                $file_name = $webinarId.'_participants.csv';

                $file_names = $store->get('participants_file_names_'.$user_id.'');

                $file_names[] = $file_path;
                $store->set('participants_file_names_'.$user_id.'', $file_names);

            }

            return $this->t(
                'Report has been generated for this webinar.', [
                '%name' => $entity->label(),
                ]
            );
        }
    }
    /**
     * {@inheritdoc}
     */
    public function access($object, AccountInterface $account = null, $return_as_object = false)
    {
        if (\Drupal::currentUser()->hasPermission('access Attendee report')) {
            return \Drupal\Core\Access\AccessResult::allowed();
        }

        // Fallback to 'update' access check for admins or higher permission roles.
        return $object->access('update', $account, $return_as_object);
    }

    /**
     * Batch finished callback.
     *
     * @param bool  $success
     *   Was the process successful?
     * @param array $results
     *   Batch process results array.
     * @param array $operations
     *   Performed operations array.
     */
    public static function finished($success, array $results, array $operations): ? RedirectResponse
    {

        $user_id = \Drupal::currentUser()->id();
        $host = \Drupal::request()->getSchemeAndHttpHost();

        /**
         * @var \Drupal\Core\TempStore\PrivateTempStore $store
         */
        $store = \Drupal::service('tempstore.private')->get('heart_zoom');

        $file_names = $store->get('participants_file_names_'.$user_id.'');
        \Drupal::logger('VBO')->info('<code>' . print_r($file_names, true) . '</code>');

        if (!empty($file_names)) {
            $public_path = PublicStream::basepath();

            $zipname = $public_path . "/report/". $user_id.'_participants_file.zip';
            $zip = new ZipArchive;
            $zip->open($zipname, ZipArchive::CREATE);
            foreach ($file_names as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            foreach ($file_names as $key => $value) {
                unlink($value);
            }
            $store->delete('participants_file_names_'.$user_id.'');
            // new RedirectResponse($zipname);
            // unlink($zipname);
            \Drupal::messenger()->addMessage(t('Download the report from this link, <a class="download-webinar-report" href="'. $host . '/' . $zipname . '" download>Download</a>'));

            return new RedirectResponse('/manage-content/zoom-webinars#reports-tab');
        }
        return new RedirectResponse('/manage-content/zoom-webinars#reports-tab');

    }
}
