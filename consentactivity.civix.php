<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_Consentactivity_ExtensionUtil
{
    const SHORT_NAME = 'consentactivity';

    const LONG_NAME = 'consentactivity';

    const CLASS_PREFIX = 'CRM_Consentactivity';

    /**
     * Translate a string using the extension's domain.
     * If the extension doesn't have a specific translation
     * for the string, fallback to the default translations.
     *
     * @param string $text
     *   Canonical message text (generally en_US).
     * @param array $params
     *
     * @return string
     *   Translated text.
     * @see ts
     */
    public static function ts($text, $params = [])
    {
        if (!array_key_exists('domain', $params)) {
            $params['domain'] = [self::LONG_NAME, null];
        }

        return ts($text, $params);
    }

    /**
     * Get the URL of a resource file (in this extension).
     *
     * @param string|NULL $file
     *   Ex: NULL.
     *   Ex: 'css/foo.css'.
     *
     * @return string
     *   Ex: 'http://example.org/sites/default/ext/org.example.foo'.
     *   Ex: 'http://example.org/sites/default/ext/org.example.foo/css/foo.css'.
     */
    public static function url($file = null)
    {
        if ($file === null) {
            return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
        }

        return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
    }

    /**
     * Get the path of a resource file (in this extension).
     *
     * @param string|NULL $file
     *   Ex: NULL.
     *   Ex: 'css/foo.css'.
     *
     * @return string
     *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo'.
     *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo/css/foo.css'.
     */
    public static function path($file = null)
    {
        // return CRM_Core_Resources::singleton()->getPath(self::LONG_NAME, $file);
        return __DIR__.($file === null ? '' : (DIRECTORY_SEPARATOR.$file));
    }

    /**
     * Get the name of a class within this extension.
     *
     * @param string $suffix
     *   Ex: 'Page_HelloWorld' or 'Page\\HelloWorld'.
     *
     * @return string
     *   Ex: 'CRM_Foo_Page_HelloWorld'.
     */
    public static function findClass($suffix)
    {
        return self::CLASS_PREFIX.'_'.str_replace('\\', '_', $suffix);
    }
}

use CRM_Consentactivity_ExtensionUtil as E;

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function _consentactivity_civix_civicrm_config(&$config = null)
{
    static $configured = false;
    if ($configured) {
        return;
    }
    $configured = true;

    $template =& CRM_Core_Smarty::singleton();

    $extRoot = dirname(__FILE__).DIRECTORY_SEPARATOR;
    $extDir = $extRoot.'templates';

    if (is_array($template->template_dir)) {
        array_unshift($template->template_dir, $extDir);
    } else {
        $template->template_dir = [$extDir, $template->template_dir];
    }

    $include_path = $extRoot.PATH_SEPARATOR.get_include_path();
    set_include_path($include_path);
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy.
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @param array $item - the item to insert (parent/child attributes will be
 *    filled for you)
 *
 * @return bool
 */
function _consentactivity_civix_insert_navigation_menu(&$menu, $path, $item)
{
    // If we are done going down the path, insert menu
    if (empty($path)) {
        $menu[] = [
            'attributes' => array_merge([
                'label' => CRM_Utils_Array::value('name', $item),
                'active' => 1,
            ], $item),
        ];

        return true;
    } else {
        // Find an recurse into the next level down
        $found = false;
        $path = explode('/', $path);
        $first = array_shift($path);
        foreach ($menu as $key => &$entry) {
            if ($entry['attributes']['name'] == $first) {
                if (!isset($entry['child'])) {
                    $entry['child'] = [];
                }
                $found = _consentactivity_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item);
            }
        }

        return $found;
    }
}

/**
 * (Delegated) Implements hook_civicrm_navigationMenu().
 */
function _consentactivity_civix_navigationMenu(&$nodes)
{
    if (!is_callable(['CRM_Core_BAO_Navigation', 'fixNavigationMenu'])) {
        _consentactivity_civix_fixNavigationMenu($nodes);
    }
}

/**
 * Given a navigation menu, generate navIDs for any items which are
 * missing them.
 */
function _consentactivity_civix_fixNavigationMenu(&$nodes)
{
    $maxNavID = 1;
    array_walk_recursive($nodes, function ($item, $key) use (&$maxNavID) {
        if ($key === 'navID') {
            $maxNavID = max($maxNavID, $item);
        }
    });
    _consentactivity_civix_fixNavigationMenuItems($nodes, $maxNavID, null);
}

function _consentactivity_civix_fixNavigationMenuItems(&$nodes, &$maxNavID, $parentID)
{
    $origKeys = array_keys($nodes);
    foreach ($origKeys as $origKey) {
        if (!isset($nodes[$origKey]['attributes']['parentID']) && $parentID !== null) {
            $nodes[$origKey]['attributes']['parentID'] = $parentID;
        }
        // If no navID, then assign navID and fix key.
        if (!isset($nodes[$origKey]['attributes']['navID'])) {
            $newKey = ++$maxNavID;
            $nodes[$origKey]['attributes']['navID'] = $newKey;
            $nodes[$newKey] = $nodes[$origKey];
            unset($nodes[$origKey]);
            $origKey = $newKey;
        }
        if (isset($nodes[$origKey]['child']) && is_array($nodes[$origKey]['child'])) {
            _consentactivity_civix_fixNavigationMenuItems($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
        }
    }
}
