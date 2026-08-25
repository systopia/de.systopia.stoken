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

declare(strict_types = 1);

use CRM_Stoken_ExtensionUtil as E;

/**
 * Sets current_employer token
 *  if address location type is work/dienstlich?
 *
 * @author T. Leichtfuss
 */
class CRM_Stoken_EmployerIfTokens {

  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   *
   * @param array<string, array<string, string>> $tokens
   */
  public static function addTokens(array &$tokens): void {
    $tokens['address']['address.employer_if']    = E::ts('Employer if work-address');
    $tokens['address']['address.employer_if_nl'] = E::ts('Employer if work-address (with line break)');
    $tokens['address']['address.employer_if_br'] = E::ts('Employer if work-address (with HTML line break)');
  }

  /**
   * Handles civicrm_tokenValues hook
   *
   * @param array<int|string, array<string, mixed>> $values
   *   array of values, keyed by contact id
   * @param array<int, int|string> $cids
   *   array of contactIDs that the system needs values for.
   * @param int|null $job
   *   the job_id
   * @param array<string, array<int, string>> $tokens
   *   tokens used in the mailing - use this to check whether
   *   a token is being used and avoid fetching data for unneeded tokens
   * @param string|null $context
   *   the class name
   *
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokenValues
   */
  public static function tokenValues(
    array &$values,
    array $cids,
    ?int $job = NULL,
    array $tokens = [],
    ?string $context = NULL
  ): void {
    if (isset($tokens['address']) && is_array($tokens['address'])) {
      $used_tokens = array_intersect($tokens['address'], ['employer_if', 'employer_if_nl', 'employer_if_br']);
      if ($used_tokens === []) {
        // none of our tokens were used
        return;
      }

      // TODO: refactor! very slow!!
      foreach ($cids as $cid) {
        // get contacts current_employer
        $contact_result = civicrm_api3('Contact', 'get', [
          'sequential' => 1,
          'return' => 'current_employer',
          'id' => $cid,
        ]);
        $current_employer = $contact_result['values'][0]['current_employer'] ?? '';
        if ($current_employer === '') {
          continue;
        }
        // get location_type_id of primary address
        $address_result = civicrm_api3('Address', 'get', [
          'sequential' => 1,
          'return' => 'location_type_id',
          'contact_id' => $cid,
          'is_primary' => 1,
        ]);
        if (!isset($address_result['values'][0]['location_type_id'])) {
          continue;
        }

        // get location_type_name
        $location_type_result = civicrm_api3('LocationType', 'get', [
          'sequential' => 1,
          'return' => 'name',
          'id' => $address_result['values'][0]['location_type_id'],
        ]);
        if (!isset($location_type_result['values'][0]['name'])) {
          continue;
        }

        $location_type = $location_type_result['values'][0]['name'];
        if (preg_match('/(work|dienstlich)/i', $location_type) === 1) {
          $values[$cid]['address.employer_if']    = $current_employer;
          $values[$cid]['address.employer_if_nl'] = $current_employer . "\n";
          $values[$cid]['address.employer_if_br'] = $current_employer . '<br/>';
        }
      }
    }
  }

}
