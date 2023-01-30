<?php
/*-------------------------------------------------------+
| SYSTOPIA Additional Tokens                             |
| Copyright (C) 2016-2018 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
|         T. Leichtfuß (leichtfuss -at- systopia.de)     |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'stoken.civix.php';

// phpcs:disable
use Civi\RemoteToolsDispatcher;
use CRM_Stoken_ExtensionUtil as E;
// phpcs:enable


/**
 * Hook implementation: New Tokens
 */
function stoken_civicrm_tokens( &$tokens ) {
  CRM_Stoken_AddressTokens::addTokens($tokens);
  CRM_Stoken_DateTokens::addTokens($tokens);
  CRM_Stoken_EmployerIfTokens::addTokens($tokens);
  CRM_Stoken_FormattingTokens::addTokens($tokens);
  CRM_Stoken_UserTokens::addTokens($tokens);
}

/**
 * Hook implementation: New Tokens
 */
function stoken_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  CRM_Stoken_AddressTokens::tokenValues($values, $cids, $job, $tokens, $context);
  CRM_Stoken_DateTokens::tokenValues($values, $cids, $job, $tokens, $context);
  CRM_Stoken_EmployerIfTokens::tokenValues($values, $cids, $job, $tokens, $context);
  CRM_Stoken_FormattingTokens::tokenValues($values, $cids, $job, $tokens, $context);
  CRM_Stoken_UserTokens::tokenValues($values, $cids, $job, $tokens, $context);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function stoken_civicrm_config(&$config) {
  // subscribe to 'event messages' events (with our own wrapper to avoid duplicate registrations)
  if (class_exists('Civi\RemoteToolsDispatcher')) {
    $dispatcher = new RemoteToolsDispatcher();
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokenlist',
        ['CRM_Stoken_EventMessagesIntegration', 'listTokens']
    );
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokens',
        ['CRM_Stoken_EventMessagesIntegration', 'addTokens']
    );
  }

  _stoken_civix_civicrm_config($config);
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
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function stoken_civicrm_enable() {
  _stoken_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function stoken_civicrm_entityTypes(&$entityTypes) {
  _stoken_civix_civicrm_entityTypes($entityTypes);
}
