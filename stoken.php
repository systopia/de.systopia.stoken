<?php

require_once 'stoken.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function stoken_civicrm_config(&$config) {
  _stoken_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function stoken_civicrm_xmlMenu(&$files) {
  _stoken_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function stoken_civicrm_install() {
  _stoken_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function stoken_civicrm_uninstall() {
  _stoken_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function stoken_civicrm_enable() {
  _stoken_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function stoken_civicrm_disable() {
  _stoken_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function stoken_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _stoken_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function stoken_civicrm_managed(&$entities) {
  _stoken_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function stoken_civicrm_caseTypes(&$caseTypes) {
  _stoken_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function stoken_civicrm_angularModules(&$angularModules) {
_stoken_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function stoken_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _stoken_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Einfügen von Extra-Tokens
 */
function stoken_civicrm_tokens(&$tokens) {
  // Employer-if-primary-is-work
  $tokens['address']['address.employer_if'] = 'Employer if work-address';

  // Datumstokens hinzufügen (siehe https://projekte.systopia.de/redmine/issues/2218)
  $tokens['datum'] = array(
    'datum.kurz' => 'aktuelles Datum (kurz)',
    'datum.lang' => 'aktuelles Datum (lang)',
  );
}

/**
 * Einfügen von Extra-Tokens
 */
function stoken_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  // Employer-if-primary-is-work
  if (!empty($tokens['address']) && in_array('employer_if', $tokens['address'])) {
    foreach ($cids as $cid) {
      // get contacts current_employer
      $contact_result = civicrm_api3('Contact', 'get', array(
        'sequential' => 1,
        'return' => "current_employer",
        'id' => $cid,
      ));
      if (empty($contact_result['values'][0]['current_employer'])) continue;
      // get location_type_id of primary address
      $address_result = civicrm_api3('Address', 'get', array(
        'sequential' => 1,
        'return' => "location_type_id",
        'contact_id' => $cid,
        'is_primary' => 1,
      ));
      if (!isset($address_result['values'][0]['location_type_id'])) continue;

      // get location_type_name
      $location_type_result = civicrm_api3('LocationType', 'get', array(
        'sequential' => 1,
        'return' => "name",
        'id' => $address_result['values'][0]['location_type_id'],
      ));
      if (!isset($location_type_result['values'][0]['name'])) continue;

      $current_employer = $contact_result['values'][0]['current_employer'];
      $location_type = $location_type_result['values'][0]['name'];
      if (preg_match('/(work|dienstlich)/i', $location_type)) {
        $values[$cid]['address.employer_if'] = $current_employer;
      }
    }
  }

  // Werte für Datumstokens hinzufügen (siehe https://projekte.systopia.de/redmine/issues/2218)
  if (!empty($tokens['datum'])) {
    $oldlocale = setlocale(LC_ALL, 0);
    setlocale(LC_ALL, 'de_DE');
    $datum = array(
      'datum.kurz' => strftime("%d.%m.%Y"),
      'datum.lang' => strftime("%A, der %d. %B %Y"),
    );
    setlocale(LC_ALL, $oldlocale);
    foreach ($cids as $cid) {
      $values[$cid] = empty($values[$cid]) ? $datum : $values[$cid] + $datum;
    }
  }
}
