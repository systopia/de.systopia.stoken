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

class CRM_Stoken_AddressTokens {

  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   */
  public static function addTokens(&$tokens) {
    // add tokens for primary addresses
    $tokens["Address"]["Address.address_master"]      = E::ts("Master Name");

    // add special country token (HBS-4944)
    $tokens["Address"]["Address.address_country_int"] = E::ts("International Country");

    // add tokens for other location types
    $location_type_map = self::getLocationTypeMap();
    $new_tokens = array();
    foreach ($location_type_map as $location_type_id => $section_name) {

      // address tokens
      $new_tokens["{$section_name}.{$location_type_id}_street_address"]         = E::ts("Street Name");
      $new_tokens["{$section_name}.{$location_type_id}_supplemental_address_1"] = E::ts("Supplemental Address 1");
      $new_tokens["{$section_name}.{$location_type_id}_supplemental_address_2"] = E::ts("Supplemental Address 2");
      $new_tokens["{$section_name}.{$location_type_id}_postal_code"]            = E::ts("Postal Code");
      $new_tokens["{$section_name}.{$location_type_id}_city"]                   = E::ts("City");
      $new_tokens["{$section_name}.{$location_type_id}_country"]                = E::ts("Country");

      // extra tokens
      $new_tokens["{$section_name}.{$location_type_id}_master"]                 = E::ts("Master Name");

      // store results
      $tokens["{$section_name}"] = $new_tokens;
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
    // extract contact_ids
    if (is_string($cids)) {
      $contact_ids = explode(',', $cids);
    } elseif (isset($cids['contact_id'])) {
      $contact_ids = array($cids['contact_id']);
    } elseif (is_array($cids)) {
      $contact_ids = $cids;
    } else {
      error_log("Cannot interpret cids: " . json_encode($cids));
      return;
    }

    // load our mapping
    $location_type_map = array_flip(self::getLocationTypeMap());

    foreach ($tokens as $token_class => $token_list) {
      if (isset($location_type_map[$token_class])) {
        $location_type_id = $location_type_map[$token_class];
        $includes_master_tokens = self::includesMasterTokens($token_list);
        $location_type_addresses = self::loadAddresses($contact_ids, $location_type_id, $includes_master_tokens);
        foreach ($contact_ids as $contact_id) {
          if (isset($location_type_addresses[$contact_id])) {
            $address = $location_type_addresses[$contact_id];
            foreach ($token_list as $token) {
              $field = substr($token, strlen($location_type_id) + 1);
              $values[$contact_id]["{$token_class}.{$token}"] = $address[$field];
              // error_log("FIELD {$token_class}.{$token} to $field, value is " . $address[$field]);
            }
          } else {
            // this guy doesn't have this address
            foreach ($token_list as $token) {
              $values[$contact_id]["{$token_class}.{$token}"] = '';
              // error_log("FIELD {$token_class}.{$token}n set to empty string");
            }
          }
        }
      } elseif ($token_class == 'Address') {
        // add tokens for primary addresses (HBS-4943)
        if (self::includesMasterTokens($token_list) || self::includesIntlToken($token_list)) {
          $addresses = self::loadAddresses($contact_ids, NULL, TRUE);
          foreach ($contact_ids as $contact_id) {
            if (!isset($addresses[$contact_id])) {
              // this contact has no address
              continue;
            }
            $address = $addresses[$contact_id];
            foreach ($token_list as $token) {
              switch ($token) {
                case 'address_country_int':
                  // add special country token (HBS-4944)
                  if (!empty($address['country_id']) && $address['country_id'] != 1082) {
                    // this is an international (not German) country
                    $values[$contact_id]["{$token_class}.{$token}"] = CRM_Core_PseudoConstant::country($address['country_id']);
                  } else {
                    $values[$contact_id]["{$token_class}.{$token}"] = '';
                  }
                  break;

                case 'address_master':
                  $values[$contact_id]["{$token_class}.{$token}"] = CRM_Utils_Array::value('master',  $address, '');
                  break;

                default:
                  break;
              }
            }
          }
        }
      }
    }
  }

  /**
   * just check if the token list includes tokens that
   * require loading the master contact (address sharing)
   */
  protected static function includesMasterTokens($token_list) {
    foreach ($token_list as $token) {
      if (strstr($token, 'master')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * just check if the token list includes the
   * "International Country" token
   */
  protected static function includesIntlToken($token_list) {
    foreach ($token_list as $token) {
      if ('address_country_int' == substr($token, (strlen($token)-19))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * loads all addresses with a given type for the contact list
   * If $load_master is true, the fields 'master' will be popuplated
   */
  protected static function loadAddresses($contact_ids, $location_type_id, $load_master = FALSE) {
    // TODO: cache?

    // compile query
    $query_parameters = array(
      'contact_id'       => array('IN' => $contact_ids),
      'return'           => 'street_address,supplemental_address_1,supplemental_address_2,postal_code,city,country_id,master_id,contact_id',
      'options'          => array('limit' => 0));
    if ($location_type_id) {
      $query_parameters['location_type_id'] = $location_type_id;
    } else {
      $query_parameters['is_primary'] = 1;
    }
    $query = civicrm_api3('Address', 'get', $query_parameters);

    // index by contact
    $contactId_2_address = array();
    $contactId_2_masterAddressId  = array();
    foreach ($query['values'] as $address) {
      $contactId_2_address[$address['contact_id']] = $address;
      if (!empty($address['master_id'])) {
        $contactId_2_masterAddressId[$address['contact_id']] = $address['master_id'];
      }
    }

    // add master information if requested
    // TODO: speed up with SQL?
    if ($load_master && !empty($contactId_2_masterAddressId)) {
      // step 1: load all master addresses
      $master_query = civicrm_api3('Address', 'get', array(
        'id'         => array('IN' => array_values($contactId_2_masterAddressId)),
        'return'     => 'id,contact_id',
        'sequential' => 0,
        'options'    => array('limit' => 0),
        ));
      $contactId_2_masterContactId = array();
      foreach ($contactId_2_masterAddressId as $contact_id => $master_id) {
        if (isset($master_query['values'][$master_id]['contact_id'])) {
          $contactId_2_masterContactId[$contact_id] = $master_query['values'][$master_id]['contact_id'];
        }
      }

      // step 2: load all master contacts and set values in $contactId_2_address
      $master_contactquery = civicrm_api3('Contact', 'get', array(
        'id'         => array('IN' => array_values($contactId_2_masterContactId)),
        'sequential' => 0,
        'return'     => "display_name",
        'options'    => array('limit' => 0),
        ));
      foreach ($contactId_2_masterContactId as $contact_id => $master_contact_id) {
        $master_contact = $master_contactquery['values'][$master_contact_id];
        $contactId_2_address[$contact_id]['master']   = CRM_Utils_Array::value('display_name',  $master_contact, '');
      }
    }

    return $contactId_2_address;
  }


  /**
   * get a unique map location_type_id => token class name
   */
  public static function getLocationTypeMap() {
    $location_type_map = array();
    $location_types = civicrm_api3('LocationType', 'get', array(
      'is_active'     => 1,
      'options.limit' => 0,
      'return'        => 'display_name,name'));
    foreach ($location_types['values'] as $location_type) {
      $preferred_name = 'Adresse_' . $location_type['display_name'];
      // token class does not allow any special characters (except '_')
      $preferred_name = preg_replace('#ä#', 'ae', $preferred_name);
      $preferred_name = preg_replace('#ü#', 'ue', $preferred_name);
      $preferred_name = preg_replace('#ö#', 'oe', $preferred_name);
      $preferred_name = preg_replace('#[^\w]#', '_', $preferred_name);
      $actual_name = $preferred_name;
      while (in_array($actual_name, array_values($location_type_map))) {
        // name already exists -> just extend
        $actual_name = $actual_name . '_';
      }
      $location_type_map[$location_type['id']] = $actual_name;
    }
    return $location_type_map;
  }
}