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
      'date.fr_FR_longue' => E::ts('Current Date French/France (long)'),
      'date.fr_FR_courte' => E::ts('Current Date French/France (short)'),
      'date.es_ES_corto' => E::ts('Current Date Spanish/Spain (short)'),
      'date.es_ES_medio' => E::ts('Current Date Spanish/Spain (medium)'),
      'date.es_ES_largo' => E::ts('Current Date Spanish/Spain (long)'),
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
      $dates = array();
      $now = new DateTime();

      // add German dates
      setlocale(LC_TIME, 'de_DE.utf8');
      $formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
      $formatter->setPattern('dd.MM.yyyy');
      $dates['date.kurz'] = $formatter->format($now);

      $formatter->setPattern("EEEE, 'der' dd. MMMM yyyy");
      $dates['date.lang'] = $formatter->format($now);

      // add English dates
      setlocale(LC_TIME, 'en_US.utf8');
      $formatter = new IntlDateFormatter('en_US', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
      $formatter->setPattern('MM/dd/yyyy');
      $dates['date.short'] = $formatter->format($now);

      $formatter->setPattern('MMMM d, yyyy');
      $dates['date.long']  = $formatter->format($now);

      // add French dates
      setlocale(LC_TIME, 'fr_FR.utf8');
      $day = (int) $now->format('j');
      $day_appendix = $day === 1 ? 'er' : '';
      $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
      $formatter->setPattern("'le' d'$day_appendix' MMMM yyyy");
      $dates['date.fr_FR_longue'] = $formatter->format($now);

      $formatter->setPattern('dd/MM/yyyy');
      $dates['date.fr_FR_courte'] = $formatter->format($now);

      // add Spanish dates
      setlocale(LC_TIME, 'es_ES.utf8');
      $day = (int) $now->format('j');
      $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::FULL, IntlDateFormatter::NONE);

      $formatter->setPattern("d 'de' MMMM 'de' yyyy");
      $esLargo = $formatter->format($now);
      if ($day === 1) {
          $esLargo = preg_replace('/^1/', 'primero', $esLargo);
      }
      $dates['date.es_ES_largo'] = $esLargo;

      $formatter->setPattern('dd-MM-yyyy');
      $dates['date.es_ES_corto'] = $formatter->format($now);

      $formatter->setPattern('dd-MMM-yyyy');
      $dates['date.es_ES_medio'] = $formatter->format($now);

      // restore locale and set data
      setlocale(LC_ALL, $oldlocale);
      foreach ($cids as $cid) {
        $values[$cid] = empty($values[$cid]) ? $dates : $values[$cid] + $dates;
      }
    }
  }
}