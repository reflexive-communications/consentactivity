<?php

namespace Civi\Consentactivity;

use Civi\Api4\Activity;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\EntityTag;
use Civi\Api4\Group;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\RcBase\ApiWrapper\Save;
use Civi\RcBase\Utils\PHPUnit;
use Civi\Test\TransactionalInterface;
use CRM_Consentactivity_ExtensionUtil as E;
use CRM_Core_Controller;
use CRM_Core_Exception;
use CRM_Event_Form_Registration_Confirm;
use CRM_Event_Form_Registration_Register;
use CRM_Profile_Form_Edit;

/**
 * @group headless
 */
class ServiceTest extends HeadlessTestCase implements TransactionalInterface
{
    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessMissingParameter()
    {
        $contact_id = PHPUnit::createIndividual();

        $_REQUEST = [
            'cid' => $contact_id,
        ];
        $form = new CRM_Profile_Form_Edit();
        $form->controller = new CRM_Core_Controller();
        $form->_submitValues = [];

        Service::postProcess(CRM_Profile_Form_Edit::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessInvalidContactId()
    {
        $_REQUEST = [
            'cid' => 0,
        ];
        $form = new CRM_Profile_Form_Edit();
        $form->controller = new CRM_Core_Controller();
        $form->_submitValues = ['is_opt_out' => ''];

        self::expectException(CRM_Core_Exception::class);
        Service::postProcess(CRM_Profile_Form_Edit::class, $form);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcess()
    {
        $contact_id = PHPUnit::createIndividual();

        $_REQUEST = [
            'cid' => $contact_id,
        ];
        $form = new CRM_Profile_Form_Edit();
        $form->controller = new CRM_Core_Controller();
        $form->_submitValues = ['is_opt_out' => ''];

        Service::postProcess(CRM_Profile_Form_Edit::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessWithUpdatedTagId()
    {
        $config = new Config(E::LONG_NAME);
        $config->load();
        $cfg = $config->get();
        $cfg['tag-id'] = 1;
        $config->update($cfg);
        $contact_id = PHPUnit::createIndividual();
        Save::tagContact($contact_id, $cfg['tag-id']);

        $_REQUEST = [
            'cid' => $contact_id,
        ];
        $form = new CRM_Profile_Form_Edit();
        $form->controller = new CRM_Core_Controller();
        $form->_submitValues = ['is_opt_out' => ''];

        Service::postProcess(CRM_Profile_Form_Edit::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));

        // Tag has to be removed.
        $tags = EntityTag::get(false)
            ->addWhere('entity_table', '=', 'civicrm_contact')
            ->addWhere('entity_id', '=', $contact_id)
            ->addWhere('tag_id', '=', $cfg['tag-id'])
            ->execute();
        self::assertSame(0, count($tags));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessEventConfirmForm()
    {
        $contact_id = PHPUnit::createIndividual();

        $form = new CRM_Event_Form_Registration_Confirm();
        $form->setVar('_values', ['participant' => ['contact_id' => $contact_id]]);
        $form->_submitValues = ['is_opt_out' => ''];

        Service::postProcess(CRM_Event_Form_Registration_Confirm::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessEventRegisterFormConfirmEnabled()
    {
        $contact_id = PHPUnit::createIndividual();

        $form = new CRM_Event_Form_Registration_Register();
        $form->setVar('_values', ['event' => ['is_confirm_enabled' => 1], 'participant' => ['contact_id' => $contact_id]]);
        $form->_submitValues = ['is_opt_out' => ''];

        Service::postProcess(CRM_Event_Form_Registration_Register::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(0, count($activities));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostProcessEventRegisterFormConfirmDisabled()
    {
        $contact_id = PHPUnit::createIndividual();

        $form = new CRM_Event_Form_Registration_Register();
        $form->setVar('_values', ['event' => ['is_confirm_enabled' => 0], 'participant' => ['contact_id' => $contact_id]]);
        $form->_submitValues = ['is_opt_out' => ''];

        Service::postProcess(CRM_Event_Form_Registration_Register::class, $form);

        $activities = Activity::get(false)
            ->execute();
        self::assertSame(1, count($activities));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testCustomCheckboxFields()
    {
        $customGroup = CustomGroup::create(false)
            ->addValue('title', 'Test custom group v1')
            ->addValue('extends', 'Contact')
            ->addValue('is_active', 1)
            ->addValue('is_public', 1)
            ->addValue('style', 'Inline')
            ->execute()
            ->first();
        $optionGroup = OptionGroup::create(false)
            ->addValue('title', 'Test option group v1')
            ->addValue('name', 'Test option group v1')
            ->addValue('data_type', 'String')
            ->addValue('is_public', 1)
            ->execute()
            ->first();
        OptionValue::create(false)
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('label', 'Value label v1')
            ->addValue('value', '1')
            ->addValue('weight', '1')
            ->execute();
        CustomField::create(false)
            ->addValue('custom_group_id', $customGroup['id'])
            ->addValue('label', 'Field label v1')
            ->addValue('data_type', 'String')
            ->addValue('html_type', 'CheckBox')
            ->addValue('option_group_id', $optionGroup['id'])
            ->addValue('options_per_line', '1')
            ->execute()
            ->first();
        Group::create(false)
            ->addValue('title', 'title')
            ->addValue('visibility', 'Public Pages')
            ->addValue('group_type', 'Mailing List')
            ->addValue('created_id', 1)
            ->execute()
            ->first();
        $params = Service::customCheckboxFields();
        self::assertSame(1, count($params));
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostNotRelevantData()
    {
        $contact_id = PHPUnit::createIndividual();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $refObject = (object)['is_test' => false, 'receive_date' => '2020010112131400', 'contact_id' => $contact_id];

        Service::post('delete', 'Contribution', 1, $refObject);
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);

        Service::post('create', 'Contact', 1, $refObject);
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);

        $refObject->is_test = true;
        Service::post('create', 'Contribution', 1, $refObject);
        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostConfigNotSet()
    {
        $contact_id = PHPUnit::createIndividual();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = false;
        $cfg->update($config);
        $refObject = (object)['is_test' => false, 'receive_date' => '2020010112131400', 'contact_id' => $contact_id];

        Service::post('create', 'Contribution', 1, $refObject);

        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostOldReceiveDate()
    {
        $contact_id = PHPUnit::createIndividual();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $before = $config['consent-expiration-years'];
        $before += 2;
        $refObject = (object)['is_test' => false, 'receive_date' => date('YmdHis', strtotime($before.' years ago')), 'contact_id' => $contact_id];

        Service::post('create', 'Contribution', 1, $refObject);

        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal), $activities);
    }

    /**
     * @return void
     * @throws \API_Exception
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function testPostTriggerActivity()
    {
        $contact_id = PHPUnit::createIndividual();
        $activitiesOriginal = Activity::get(false)
            ->execute();
        $cfg = new Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        $config['consent-after-contribution'] = true;
        $cfg->update($config);
        $refObject = (object)['is_test' => false, 'receive_date' => date('YmdHis'), 'contact_id' => $contact_id];

        Service::post('create', 'Contribution', 1, $refObject);

        $activities = Activity::get(false)
            ->execute();
        self::assertCount(count($activitiesOriginal) + 1, $activities);
    }
}
