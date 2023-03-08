<?php

require_once 'consentactivity.civix.php';

// phpcs:disable
use CRM_Consentactivity_ExtensionUtil as E;

// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function consentactivity_civicrm_config(&$config)
{
    _consentactivity_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function consentactivity_civicrm_xmlMenu(&$files)
{
    _consentactivity_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function consentactivity_civicrm_install()
{
    _consentactivity_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function consentactivity_civicrm_postInstall()
{
    _consentactivity_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function consentactivity_civicrm_uninstall()
{
    _consentactivity_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function consentactivity_civicrm_enable()
{
    _consentactivity_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function consentactivity_civicrm_disable()
{
    _consentactivity_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function consentactivity_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _consentactivity_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function consentactivity_civicrm_managed(&$entities)
{
    _consentactivity_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 * Generate a list of case-types.
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function consentactivity_civicrm_caseTypes(&$caseTypes)
{
    _consentactivity_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 * Generate a list of Angular modules.
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function consentactivity_civicrm_angularModules(&$angularModules)
{
    _consentactivity_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function consentactivity_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _consentactivity_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function consentactivity_civicrm_entityTypes(&$entityTypes)
{
    _consentactivity_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function consentactivity_civicrm_themes(&$themes)
{
    _consentactivity_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function consentactivity_civicrm_preProcess($formName, &$form) {
//
//}

// The following hooks are implemented by me.
/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess
 */
function consentactivity_civicrm_postProcess($formName, $form)
{
    CRM_Consentactivity_Service::postProcess($formName, $form);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function consentactivity_civicrm_navigationMenu(&$menu)
{
    _consentactivity_civix_insert_navigation_menu($menu, 'Administer', [
        'label' => E::ts('Consentactivity Settings'),
        'name' => 'consentactivity_setting',
        'url' => 'civicrm/admin/consent-activity',
        'permission' => 'administer CiviCRM',
        'operator' => 'AND',
        'separator' => 0,
    ]);
    _consentactivity_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @param string $formName
 * @param array $fields
 * @param array $files
 * @param CRM_Core_Form $form
 * @param array $errors
 */
function consentactivity_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors)
{
    // extend tag validation. On case of tag deletion, check
    if ($formName === 'CRM_Tag_Form_Edit' && $form->_action === CRM_Core_Action::DELETE) {
        $ids = $form->getVar('_id');
        $cfg = new CRM_Consentactivity_Config(E::LONG_NAME);
        $cfg->load();
        $config = $cfg->get();
        foreach ($ids as $id) {
            if ($id == $config['tag-id'] || $id == $config['expired-tag-id']) {
                $errors['tag_id'] = E::ts('The tag is reserved for the consentactivity.');
                CRM_Core_Session::setStatus(E::ts('The tag is reserved for the consentactivity.'), 'Consentactivity', 'error');

                return;
            }
        }
    }
}

/**
 * Implements hook_civicrm_post().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function consentactivity_civicrm_post($op, $objectName, $objectId, &$objectRef)
{
    CRM_Consentactivity_Service::post($op, $objectName, $objectId, $objectRef);
}

/**
 * Implements hook_civicrm_tokens().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tokens
 */
function consentactivity_civicrm_tokens(&$tokens)
{
    $tokens['Consentactivity'] = [
        'Consentactivity.consent_renewal' => E::ts('Renew Consent Link'),
    ];
}

/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container
 */
function consentactivity_civicrm_container($container)
{
    $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
    $container->findDefinition('dispatcher')->addMethodCall(
        'addListener',
        ['civi.token.eval', 'consentactivity_evaluate_tokens']
    );
}

function consentactivity_evaluate_tokens(\Civi\Token\Event\TokenValueEvent $e)
{
    foreach ($e->getRows() as $row) {
        $urlParams = [
            'reset' => 1,
            'jid' => $row->context['mailingJobId'],    // The job id.
            'qid' => $row->context['mailingActionTarget']['id'] ?? null,    // The queue id.
            'h' => $row->context['mailingActionTarget']['hash'] ?? null,      // The hash.
        ];
        $url = CRM_Utils_System::url('civicrm/consent/renew', $urlParams, true, null, true, true);
        $row->format('text/html');
        $row->tokens('Consentactivity', 'consent_renewal', $url);
    }
}
