<?php

namespace Drupal\heart_zoom\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ZipArchive;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Action description.
 *
 * @Action(
 *   id = "heart_zoom_registration_report",
 *   label = @Translation("Registration report"),
 *   type = ""
 * )
 */
class RegistrationRequiredAction extends ViewsBulkOperationsActionBase
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

            /** @var \Drupal\Core\TempStore\PrivateTempStore $store */
            $store = \Drupal::service('tempstore.private')->get('heart_zoom');
            $reg_status_data = $store->get('reg_status_' . $user_id);
            $page_size_data = 300;

            $client = \Drupal::service('zoomapi.client');
            $webinarId = $entity->get('zoom_id')->getString();

            $page_size_data = 300;
            $reg_status_data = 'all';
            $webinar_list = [];
            $total_pages = '';
            $next_page_token = '';

            if ($page_size_data && $reg_status_data) {
                $webinar_list = $client->get('/webinars/' . $webinarId . '/registrants?page_size=' . $page_size_data . '&status=' . $reg_status_data);

                if (isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
                    $next_page_token = $webinar_list['next_page_token'];
                }
            }

            if (isset($webinar_list['page_size']) && !empty($webinar_list['page_size']) && (isset($webinar_list['total_records']) && !empty($webinar_list['total_records']))) {
                $total_pages = ceil($webinar_list['total_records'] / $webinar_list['page_size']);
            }

            if ($total_pages != '' && isset($webinar_list['next_page_token']) && !empty($webinar_list['next_page_token'])) {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $webinars = $client->get('/webinars/' . $webinarId . '/registrants?page_size=' . $page_size_data . '&next_page_token=' . $next_page_token);
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

            if (!empty($registrants)) {

                $public_path = PublicStream::basepath();

                $file_name = $webinarId . '_registrants.csv';

                $file_path = $public_path ."/report/". $file_name . "";

                $dirname = dirname($file_path);
                if (!is_dir($dirname)) {
                  mkdir($dirname, 0755, TRUE);
                }

                $fp = fopen($file_path, 'w');

                // Create the header row
                fputcsv($fp, ['First Name', 'Last Name', 'Status', 'Created At', 'Email', 'Phone']);

                // Loop through each registrant and write desired data to CSV
                foreach ($registrants as $key => $value) {
                    $data_to_write = [
                        $value['first_name'],
                        $value['last_name'],
                        $value['status'],
                        $value['create_time'],
                        $value['email'],
                        $value['phone']
                    ];
                    fputcsv($fp, $data_to_write);
                }

                // Close the file
                fclose($fp);

                $file_name = $webinarId . '_registrants.csv';
                $file_names = $store->get('reg_file_names_' . $user_id) ?? [];
                $file_names[] = $file_path;
                $store->set('reg_file_names_' . $user_id, $file_names);
            }

            return $this->t(
                'Unable to generate report for this webinar.', [
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
        if (\Drupal::currentUser()->hasPermission('access registration report')) {
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
    public static function finished($success, array $results, array $operations): ?RedirectResponse
    {
        $user_id = \Drupal::currentUser()->id();
        $host = \Drupal::request()->getSchemeAndHttpHost();

        /** @var \Drupal\Core\TempStore\PrivateTempStore $store */
        $store = \Drupal::service('tempstore.private')->get('heart_zoom');
        $store->delete('reg_status_' . $user_id);
        $store->delete('page_size_' . $user_id);

        $file_names = $store->get('reg_file_names_' . $user_id);
        \Drupal::logger('VBO')->info('<code>' . print_r($file_names, true) . '</code>');

        if (!empty($file_names)) {
            $public_path = PublicStream::basepath();
            $zipname = $public_path . "/report/" . $user_id . "_registrants_file.zip";

            $zip = new ZipArchive();
            if ($zip->open($zipname, ZipArchive::CREATE) !== TRUE) {
                \Drupal::logger('heart_zoom')->error('Unable to create zip file.');
                return new RedirectResponse('/zoom-webinars#reports-tab');
            }

            foreach ($file_names as $file) {
                if (file_exists($file)) {
                    // Add file to the zip without directory structure
                    $zip->addFile($file, basename($file));
                } else {
                    \Drupal::logger('heart_zoom')->warning('File does not exist: ' . $file);
                }
            }
            $zip->close();

            // Delete the original files
            foreach ($file_names as $file) {
                unlink($file);
            }

            $store->delete('reg_file_names_' . $user_id);

            \Drupal::messenger()->addMessage(t('Download the report from this link, <a class="download-webinar-report" href="'. $host . '/' . $zipname . '" download>Download</a>'));
        }

        return new RedirectResponse('/manage-content/zoom-webinars#reports-tab');
    }

}
