<?php
use CRM_Consentactivity_ExtensionUtil as E;

class CRM_Consentactivity_Page_ConsentRenew extends CRM_Core_Page
{
    public function run()
    {
        // URL validation.
        $jobId = CRM_Utils_Request::retrieve('jid', 'Int');
        $queueId = CRM_Utils_Request::retrieve('qid', 'Int');
        $hash = CRM_Utils_Request::retrieve('h', 'String');
        if (!$jobId || !$queueId || !$hash) {
            throw new CRM_Core_Exception(E::ts('Missing input parameters'));
        }
        // verify that the three numbers above match
        $q = CRM_Mailing_Event_BAO_Queue::verify($jobId, $queueId, $hash);
        if (!$q) {
            throw new CRM_Core_Exception(E::ts('There was an error in your request'));
        }
        $activity = CRM_Consentactivity_Service::createConsentActivityToContact($q->contact_id);
        if (!count($activity)) {
            throw new CRM_Core_Exception(E::ts('Failed to renew consent.'));
        }
        CRM_Utils_System::setTitle(E::ts(''));

        parent::run();
    }
}
