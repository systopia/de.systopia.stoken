<?php
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
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

declare(strict_types = 1);

require_once 'stoken.civix.php';

// phpcs:disable
use Civi\RemoteToolsDispatcher;
use CRM_Stoken_ExtensionUtil as E;
// phpcs:enable

/**
 * Hook implementation: New Tokens
 *
 * @param array<string, array<string, string>> $tokens
 */
function stoken_civicrm_tokens(array &$tokens): void {
  CRM_Stoken_AddressTokens::addTokens($tokens);
  CRM_Stoken_DateTokens::addTokens($tokens);
  CRM_Stoken_EmployerIfTokens::addTokens($tokens);
  CRM_Stoken_FormattingTokens::addTokens($tokens);
  CRM_Stoken_UserTokens::addTokens($tokens);
}

/**
 * Hook implementation: New Tokens
 *
 * @param array<int|string, array<string, mixed>> $values
 * @param array<int|string, int|string>|string $cids
 * @param array<string, array<int, string>> $tokens
 */
function stoken_civicrm_tokenValues(
  array &$values,
  array|string $cids,
  ?int $job = NULL,
  array $tokens = [],
  ?string $context = NULL
): void {
  if (is_string($cids)) {
    $contact_ids = explode(',', $cids);
  }
  elseif (isset($cids['contact_id'])) {
    $contact_ids = [$cids['contact_id']];
  }
  else {
    $contact_ids = $cids;
  }

  CRM_Stoken_AddressTokens::tokenValues($values, $contact_ids, $job, $tokens, $context);
  CRM_Stoken_DateTokens::tokenValues($values, $contact_ids, $job, $tokens, $context);
  CRM_Stoken_EmployerIfTokens::tokenValues($values, $contact_ids, $job, $tokens, $context);
  CRM_Stoken_FormattingTokens::tokenValues($values, $contact_ids, $job, $tokens, $context);
  CRM_Stoken_UserTokens::tokenValues($values, $contact_ids, $job, $tokens, $context);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function stoken_civicrm_config(CRM_Core_Config &$config): void {
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
function stoken_civicrm_install(): void {
  _stoken_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function stoken_civicrm_enable(): void {
  _stoken_civix_civicrm_enable();
}
