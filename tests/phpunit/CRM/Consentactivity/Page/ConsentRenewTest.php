<?php

use Civi\Api4\Activity;
use Civi\Consentactivity\HeadlessTestCase;
use CRM_Consentactivity_ExtensionUtil as E;

/**
 * @group headless
 */
class CRM_Consentactivity_Page_ConsentRenewTest extends HeadlessTestCase
{
    const DOMAIN_NAME = 'my-domain';

    /**
     * @param string $title
     *
     * @return int
     * @throws \CiviCRM_API3_Exception
     */
    private function createGroup(string $title): int
    {
        $result = civicrm_api3('Group', 'create', [
            'title' => $title,
            'visibility' => 'Public Pages',
            'group_type' => 'Mailing List',
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing group id.');

        return $result['id'];
    }

    /**
     * @param int $groupId
     *
     * @return int
     * @throws \CiviCRM_API3_Exception
     */
    private function addNewContactWithEmailToGroup(int $groupId): int
    {
        $result = civicrm_api3('Contact', 'create', [
            'contact_type' => 'Individual',
            'first_name' => 'Bob',
            'last_name' => 'Lastname',
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing contact id for the new user.');
        $contactId = $result['id'];
        $result = civicrm_api3('GroupContact', 'create', [
            'group_id' => $groupId,
            'contact_id' => $contactId,
            'status' => 'Added',
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('added', $result), 'Missing added key from groupContact.');
        self::assertSame(1, $result['added'], 'One contact has to be added to the group.');
        $result = civicrm_api3('Email', 'create', [
            'contact_id' => $contactId,
            'email' => 'individual.bob.lastname@email.com',
            'is_bulkmail' => 1,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing email id.');

        return $contactId;
    }

    /**
     * @return void
     * @throws \CiviCRM_API3_Exception
     */
    private function setupMailing()
    {
        $result = civicrm_api3('Domain', 'create', [
            'name' => self::DOMAIN_NAME,
            'domain_version' => '5.37.1',
            'id' => 1,
            'contact_id' => 1,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the domain update.');
        $result = civicrm_api3('MailSettings', 'create', [
            'id' => 1,
            'domain_id' => self::DOMAIN_NAME,
            'name' => 'myMailerAccount',
            'domain' => 'example.org',
            'protocol' => 'POP3',
            'username' => 'admin',
            'password' => 'admin',
            'activity_status' => 'Completed',
            'is_default' => 1,
            'is_ssl' => 0,
            'is_non_case_email_skipped' => 0,
            'is_contact_creation_disabled_if_no_match' => 0,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the MailSettings update.');
        $result = civicrm_api3('Setting', 'create', [
            'mailing_backend' => ['outBound_option' => 5, 'smtpUsername' => 'admin', 'smtpPassword' => 'admin'],
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the mailing_backend update.');
        $result = civicrm_api3('OptionValue', 'create', [
            'option_group_id' => 'from_email_address',
            'label' => '"info" <info@example.org>',
            'name' => '"info" <info@example.org>',
            'domain_id' => self::DOMAIN_NAME,
            'is_default' => 1,
            'is_active' => 1,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the OptionValue update.');
    }

    /**
     * @param int $groupId
     * @param int $contactId
     *
     * @return array
     * @throws \CiviCRM_API3_Exception
     */
    private function processMailing(int $groupId, int $contactId): array
    {
        $result = civicrm_api3('Mailing', 'create', [
            'subject' => 'email subject',
            'name' => 'email name',
            'template_type' => 'traditional',
            'body_html' => '<div>{Consentactivity.consent_renewal}. {action.optOutUrl}. {domain.address}</div>',
            'body_text' => '{Consentactivity.consent_renewal}. {action.optOutUrl}. {domain.address}',
            'groups' => ['include' => [$groupId], 'exclude' => []],
            'created_at' => 1,
            'scheduled_date' => 'now',
            'template_options' => [
                'nonce' => 1,
            ],
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the Mailing create.');
        $mailingId = $result['id'];
        // attachment replace that could be found in the browser console.
        $result = civicrm_api3('Attachment', 'replace', [
            'entity_table' => 'civicrm_mailing',
            'entity_id' => $mailingId,
            'values' => [],
        ]);
        self::assertSame(0, $result['is_error']);
        $result = civicrm_api3('MailingRecipients', 'get', [
            'mailing_id' => $mailingId,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertSame(1, count($result['values']), 'Invalid number of email recipients.');
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the MailingRecipients get.');
        self::assertSame($contactId, intval($result['values'][$result['id']]['contact_id'], 10), 'Missing contact from the recipients.');

        return civicrm_api3('Mailing', 'get', ['id' => $mailingId]);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CiviCRM_API3_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testRunAddActivity()
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        Civi::settings()->add([
            'civimail_multiple_bulk_emails' => 1,
        ]);
        $this->setupMailing();
        $groupId = $this->createGroup('WithToken');
        $contactId = $this->addNewContactWithEmailToGroup($groupId);
        $submitData = $this->processMailing($groupId, $contactId);
        // run the mail sending job
        $job = civicrm_api3('Job', 'process_mailing', ['runInNonProductionEnvironment' => true]);
        self::assertSame(0, $job['is_error']);
        self::assertSame(1, count($job['values']), 'Invalid number of email recipients.');
        self::assertTrue(array_key_exists('processed', $job['values']), 'Missing processed key.');
        self::assertSame(1, $job['values']['processed'], 'Invalid number of processed mails.');
        $result = CRM_Utils_SQL_Select::from('civicrm_mailing_event_queue mec')
            ->where('mec.contact_id = @cid')
            ->param(['cid' => $contactId])
            ->execute()
            ->fetchAll();
        // Extract data. As this is the only successfully submitted message, it has to be under the 0 key:
        $hash = $result[0]['hash'];
        $jobId = $result[0]['job_id'];
        $queueId = $result[0]['id'];
        // Count the current number of activities.
        // After the execution it has to be increased.
        $activitiesBefore = Activity::get(false)
            ->execute();
        $_GET = [
            'jid' => $jobId,
            'qid' => $queueId,
            'h' => $hash,
        ];
        $_POST = [];
        $_REQUEST = [
            'jid' => $jobId,
            'qid' => $queueId,
            'h' => $hash,
        ];
        $page = new CRM_Consentactivity_Page_ConsentRenew();
        try {
            self::expectOutputRegex('/You have successfully renewed your GDPR consent at '.self::DOMAIN_NAME.'/i');
            $page->run();
        } catch (Exception $e) {
            self::fail('Page load should be successful.');
        }
        $activitiesAfter = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesBefore) + 1, $activitiesAfter);
    }
}
