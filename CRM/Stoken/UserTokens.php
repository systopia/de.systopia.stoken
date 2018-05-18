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

class CRM_Stoken_UserTokens {

  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   */
  public static function addTokens(&$tokens) {
    $user_contact_id = CRM_Core_Session::getLoggedInContactID();

    if ($user_contact_id) {
      // add tokens for logged-in user
      $tokens["User"]["User.first_name"] = E::ts("First Name");
      $tokens["User"]["User.last_name"]  = E::ts("Last Name");
    }
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
    $user_contact_id = CRM_Core_Session::getLoggedInContactID();
    if ($user_contact_id) {
      $contact = civicrm_api3('Contact', 'getsingle', array(
          'id' => $user_contact_id,
          'return' => 'first_name,last_name'));
      foreach ($cids as $cid) {
        $values[$cid]["User.first_name"] = $contact['first_name'];
        $values[$cid]["User.last_name"] = $contact['last_name'];
      }
    }
  }
}