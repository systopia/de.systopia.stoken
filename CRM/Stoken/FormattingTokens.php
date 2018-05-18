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

use CRM_Stoken_ExtensionUtil as E;

class CRM_Stoken_FormattingTokens {

  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   */
  public static function addTokens(&$tokens) {
    $tokens['address']['address.supplemental_address_1_nl'] = E::ts('Supplemental Address 1 (with line break)');
    $tokens['address']['address.supplemental_address_1_br'] = E::ts('Supplemental Address 1 (with HTML line break)');
    $tokens['address']['address.supplemental_address_2_nl'] = E::ts('Supplemental Address 2 (with line break)');
    $tokens['address']['address.supplemental_address_2_br'] = E::ts('Supplemental Address 2 (with HTML line break)');
  }

  /**
   * Handles civicrm_tokenValues hook
   * @param $values - array of values, keyed by contact id
   * @param $cids - array of contactIDs that the system needs values for.
   * @param $job - the job_id
   * @param $tokens - tokens used in the mailing - use this to check whether a token is being used and avoid fetching data for unneeded tokens
   * @param $context - the class name
   *
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokenValues
   */
  public static function tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
    $mytokens = array('supplemental_address_1_nl', 'supplemental_address_1_br', 'supplemental_address_2_nl', 'supplemental_address_2_br');
    if (isset($tokens['address']) && is_array($tokens['address'])) {
      $my_used_tokens = array_intersect($tokens['address'], $mytokens);
      if (empty($my_used_tokens)) {
        // none of our tokens were used
        return;
      }

      // load addresses
      $data = civicrm_api3('Contact', 'get', array(
        'id'     => array('IN' => $cids),
        'return' => 'supplemental_address_1,supplemental_address_2,id'));
      foreach ($data['values'] as $entry) {
        $cid = $entry['id'];
        if (!empty($entry['supplemental_address_1'])) {
          $values[$cid]['address.supplemental_address_1_nl'] = $entry['supplemental_address_1'] . "\n";
          $values[$cid]['address.supplemental_address_1_br'] = $entry['supplemental_address_1'] . "<br/>";
        }
        if (!empty($entry['supplemental_address_2'])) {
          $values[$cid]['address.supplemental_address_2_nl'] = $entry['supplemental_address_2'] . "\n";
          $values[$cid]['address.supplemental_address_2_br'] = $entry['supplemental_address_2'] . "<br/>";
        }
      }
    }
  }
}