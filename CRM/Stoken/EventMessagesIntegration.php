<?php
/*-------------------------------------------------------+
| SYSTOPIA Additional Tokens                             |
| EventMessage Integration                               |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
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

use Civi\EventMessages\MessageTokens as MessageTokens;
use Civi\EventMessages\MessageTokenList as MessageTokenList;

class CRM_Stoken_EventMessagesIntegration {

  const TOKEN_CLASSES = [
      'CRM_Stoken_AddressTokens',
      'CRM_Stoken_DateTokens',
      'CRM_Stoken_EmployerIfTokens',
      'CRM_Stoken_FormattingTokens',
      'CRM_Stoken_UserTokens'
  ];

  /**
   * Get the available token metadata
   *
   * @return array
   *  list of token => attribute
   */
  public static function getAllSTokens() {
    static $all_tokens = null;
    if ($all_tokens === null) {
      $all_tokens = [];

      foreach (self::TOKEN_CLASSES as $token_class) {
        if (!class_exists($token_class)) {
          Civi::log()->warning("Token class '{$token_class}' doesn't exist!");
          continue;
        }
        if (!method_exists($token_class, 'addTokens')) {
          Civi::log()->warning("Method '{$token_class}::addTokens' doesn't exist!");
          continue;
        }

        // collect tokens
        $tokens = [];
        $token_class::addTokens($tokens);

        // process tokens
        foreach ($tokens as $group => $group_tokens) {
          $group_segments = explode('_', $group);
          if (count($group_segments) > 1) {
            $prefix = "[{$group_segments[1]} {$group_segments[0]}] ";
          } else {
            $prefix = '';
          }

          foreach ($group_tokens as $token_name => $token_title) {
            // skip wrongly added tokens(!) - there seems to be an error in the token generator...
            $token_names = explode('.', $token_name);
            if ($token_names[0] != $group) continue;

            // compile token data
            $token_key = 'stoken_' . $group . '_' . $token_names[1];
            $description = $token_title;
            $all_tokens[$token_name] = [
                'key'         => $token_key,
                'description' => $prefix . $description,
                'class'       => $token_class,
                'group'       => $group,
                'name'        => $token_name,
                'local_name'  => $token_names[1],
            ];
          }
        }
      }
    }

    return $all_tokens;
  }

  /**
   * Register our tokens with the EventMessages extension
   *
   * @param MessageTokenList $tokenList
   *   token list event
   */
  public static function listTokens($tokenList)
  {
    // gather tokens
    $tokens = self::getAllSTokens();
    foreach ($tokens as $token) {
      $tokenList->addToken('$' . $token['key'], $token['description']);
    }
  }

  /**
   * Provide token values to our
   *
   * @param MessageTokens $messageTokens
   *   the token list
   */
  public static function addTokens(MessageTokens $messageTokens)
  {
    // extract contact ID
    $tokens = $messageTokens->getTokens();
    if (empty($tokens['contact']['id'])) {
      // no contact found
      return;
    }
    $contact_id = $tokens['contact']['id'];
    $cids = [$contact_id];

    // find out which tokens we need
    $tokens = self::getAllSTokens();
    $required_classes = self::TOKEN_CLASSES;
    $used_tokens = $tokens;

    // if possible, restrict classes
    if (method_exists($messageTokens, 'requiresToken')) {
      // modern interface: we can check, which classes are really required
      $classes_used = [];
      $used_tokens = [];
      foreach ($tokens as $token) {
        if ($messageTokens->requiresToken($token['key'])) {
          $classes_used[$token['class']] = 1;
          $used_tokens[] = $token;
        }
      }
      $required_classes = array_keys($classes_used);
    }

    // generate token_list ($token_group => $token_list)
    $token_list = [];
    foreach ($used_tokens as $used_token) {
      $token_list[$used_token['group']][] = $used_token['local_name'];
    }

    // now gather token values
    $values = [];
    foreach ($required_classes as $generator_class) {
      $generator_class::tokenValues($values, $cids, null, $token_list);
    }

    // finally: set tokens
    foreach ($used_tokens as $used_token) {
      if (isset($values[$contact_id]["{$used_token['group']}.{$used_token['local_name']}"])) {
        $messageTokens->setToken($used_token['key'], $values[$contact_id]["{$used_token['group']}.{$used_token['local_name']}"], false);
      }
    }
  }
}