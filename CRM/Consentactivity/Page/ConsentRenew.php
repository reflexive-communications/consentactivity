<?php

use Civi\Consentactivity\Config;
use Civi\Consentactivity\Service;
use Civi\RcBase\ApiWrapper\Get;
use Civi\RcBase\Exception\MissingArgumentException;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * Consent renew landing page
 */
class CRM_Consentactivity_Page_ConsentRenew extends CRM_Core_Page
{
    /**
     * @return void
     * @throws \Civi\RcBase\Exception\APIException
     * @throws \CRM_Core_Exception
     */
    public function run(): void
    {
        $config = new Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();

        $this->assign('org_name', Get::entityByID('Domain', CRM_Core_Config::domainID(), 'name'));
        $this->assign('email_contact', $cfg['email-contact'] ?? '');

        Civi::resources()->addStyleFile(E::LONG_NAME, 'assets/css/landing.css');

        try {
            $job_id = CRM_Utils_Request::retrieve('jid', 'Int');
            $queue_id = CRM_Utils_Request::retrieve('qid', 'Int');
            $hash = CRM_Utils_Request::retrieve('h', 'String');
            if ($job_id < 1 || $queue_id < 1 || empty($hash)) {
                throw new MissingArgumentException('input parameters');
            }

            $queue = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
            if (!$queue) {
                throw new InvalidArgumentException('input parameters');
            }

            // Add consent-activity
            $activity = Service::createConsentActivityToContact($queue->contact_id);
            if (!count($activity)) {
                throw new \Civi\RcBase\Exception\RunTimeException('Failed to renew consent');
            }

            $redirect = $cfg['landing-page'];
            if (!empty($redirect)) {
                CRM_Utils_System::redirect($redirect);
            }

            $this->assign('error', false);
            parent::run();
        } catch (Throwable $ex) {
            $this->assign('error', true);
            parent::run();

            return;
        }
    }
}
