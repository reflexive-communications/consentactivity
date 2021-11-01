<?php

use CRM_Consentactivity_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Activity;

/**
 * It checks the opt out process.
 * Given:
 * - contact
 * - email
 * - group
 * - contact is added to the group
 * - mosaico message, with the group as include group
 * - process mailing jobs
 * When:
 * - call the easy-opt-out landing
 *
 * @group headless
 */
class CRM_Consentactivity_Page_ConsentRenewTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
    public function setUpHeadless()
    {
        return \Civi\Test::headless()
            ->install('org.civicrm.flexmailer')
            ->installMe(__DIR__)
            ->apply();
    }

    public function setUp(): void
    {
        parent::setUp();
        Civi::settings()->add(['flexmailer_traditional' => 'flexmailer']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Apply a forced rebuild of DB, thus
     * create a clean DB before running tests
     *
     * @throws \CRM_Extension_Exception_ParseException
     */
    public static function setUpBeforeClass(): void
    {
        // Resets DB and install depended extension
        \Civi\Test::headless()
            ->install('org.civicrm.flexmailer')
            ->installMe(__DIR__)
            ->apply(true);
    }

    /**
     * Create a clean DB before running tests
     *
     * @throws CRM_Extension_Exception_ParseException
     */
    public static function tearDownAfterClass(): void
    {
        \Civi\Test::headless()
            ->uninstallMe(__DIR__)
            ->uninstall('org.civicrm.flexmailer')
            ->apply(true);
    }

    /*
     * On case of missing parameters (jid, qid, h) it has to throw exception.
     */
    public function testRunMissingParameters()
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $page = new CRM_Consentactivity_Page_ConsentRenew();
        self::expectException(CRM_Core_Exception::class);
        self::expectExceptionMessage(E::ts('Missing input parameters'));
        $page->run();
    }
    public function testRunInvalidParameters()
    {
        $_GET = [
            'jid' => '10',
            'qid' => '10',
            'h' => 'wronghash',
        ];
        $_POST = [];
        $_REQUEST = [
            'jid' => '10',
            'qid' => '10',
            'h' => 'wronghash',
        ];
        $page = new CRM_Consentactivity_Page_ConsentRenew();
        self::expectException(CRM_Core_Exception::class);
        self::expectExceptionMessage(E::ts('There was an error in your request'));
        $page->run();
    }
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
    private function setupMailing()
    {
        $domainName = 'my-domain';
        $result = civicrm_api3('Domain', 'create', [
            'name' => $domainName,
            'domain_version' => '5.37.1',
            'id' => 1,
            'contact_id' => 1,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the domain update.');
        $result = civicrm_api3('MailSettings', 'create', [
            'id' => 1,
            'domain_id' => $domainName,
            'name' => 'myMailerAccount',
            'domain' => 'civicrm-base.com',
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
            'mailing_backend' => ["outBound_option"=>5,"smtpUsername"=>"admin","smtpPassword"=>"admin"]
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the mailing_backend update.');
        $result = civicrm_api3('OptionValue', 'create', [
            'option_group_id' => 'from_email_address',
            'label' => '"info" <info@civicrm-base.com>',
            'name' => '"info" <info@civicrm-base.com>',
            'domain_id' => $domainName,
            'is_default' => 1,
            'is_active' =>1,
        ]);
        self::assertSame(0, $result['is_error']);
        self::assertTrue(array_key_exists('id', $result), 'Missing id from the OptionValue update.');
    }
    private function processMailing(int $groupId, int $contactId): array
    {
        $result = civicrm_api3('Mailing', 'create', [
            'subject' => 'email subject',
            'name' => 'email name',
            'template_type' => "traditional",
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
            $page->run();
        } catch (Exception $e) {
            self::fail('Page load should be successful.');
        }
        $activitiesAfter = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesBefore) + 1, $activitiesAfter);
    }
}
