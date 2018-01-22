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

class CRM_Stoken_DateTokens {

  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   */
  public static function addTokens(&$tokens) {
    $tokens['date'] = array(
      'date.kurz'  => E::ts('Current Date German (short)'),
      'date.lang'  => E::ts('Current Date German (long)'),
      'date.short' => E::ts('Current Date English (short)'),
      'date.long'  => E::ts('Current Date English (long)'),
    );
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
    if (!empty($tokens['date'])) {
      $oldlocale = setlocale(LC_ALL, 0);

      // add German dates
      setlocale(LC_ALL, 'de_DE');
      $date = array(
        'date.kurz' => strftime("%d.%m.%Y"),
        'date.lang' => strftime("%A, der %d. %B %Y"),
      );

      // add English dates
      setlocale(LC_ALL, 'en_US');
      $date = array(
        'date.short' => strftime("%m/%d/%Y"),
        'date.long'  => strftime("%B %e, %Y"),
      );

      setlocale(LC_ALL, $oldlocale);
      foreach ($cids as $cid) {
        $values[$cid] = empty($values[$cid]) ? $date : $values[$cid] + $date;
      }
    }
  }
}