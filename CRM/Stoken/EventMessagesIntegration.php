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

declare(strict_types = 1);

use Civi\EventMessages\MessageTokens as MessageTokens;
use Civi\EventMessages\MessageTokenList as MessageTokenList;

class CRM_Stoken_EventMessagesIntegration {

  public const TOKEN_CLASSES = [
    CRM_Stoken_AddressTokens::class,
    CRM_Stoken_DateTokens::class,
    CRM_Stoken_EmployerIfTokens::class,
    CRM_Stoken_FormattingTokens::class,
    CRM_Stoken_UserTokens::class,
  ];

  /**
   * Get the available token metadata
   *
   * @return array<string, array{
   *   key: string,
   *   description: string,
   *   class: class-string,
   *   group: string,
   *   name: string,
   *   local_name: string,
   *   }>
   *   list of token => attribute
   */
  public static function getAllSTokens(): array {
    static $all_tokens = NULL;
    if ($all_tokens === NULL) {
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
          }
          else {
            $prefix = '';
          }

          foreach ($group_tokens as $token_name => $token_title) {
            // skip wrongly added tokens(!) - there seems to be an error in the token generator...
            $token_names = explode('.', $token_name);
            if ($token_names[0] !== $group) {
              continue;
            }

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
   * @param \Civi\EventMessages\MessageTokenList $tokenList
   *   token list event
   */
  public static function listTokens(MessageTokenList $tokenList): void {
    // gather tokens
    $tokens = self::getAllSTokens();
    foreach ($tokens as $token) {
      $tokenList->addToken('$' . $token['key'], $token['description']);
    }
  }

  /**
   * Provide token values to our
   *
   * @param \Civi\EventMessages\MessageTokens $messageTokens
   *   the token list
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public static function addTokens(MessageTokens $messageTokens): void {
    // extract contact ID
    $tokens = $messageTokens->getTokens();
    $contact = is_array($tokens['contact'] ?? NULL) ? $tokens['contact'] : [];
    $contact_id = $contact['id'] ?? NULL;
    if (!is_numeric($contact_id) || (int) $contact_id <= 0) {
      // no contact found
      return;
    }
    $contact_id = (int) $contact_id;
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

    // generate the token list, grouped by token group
    $token_list = [];
    foreach ($used_tokens as $used_token) {
      $token_list[$used_token['group']][] = $used_token['local_name'];
    }

    // now gather token values
    /** @var array<int|string, array<string, mixed>> $values */
    $values = [];
    foreach ($required_classes as $generator_class) {
      $generator_class::tokenValues($values, $cids, NULL, $token_list);
    }

    // finally: set tokens
    foreach ($used_tokens as $used_token) {
      if (isset($values[$contact_id]["{$used_token['group']}.{$used_token['local_name']}"])) {
        $messageTokens->setToken(
          $used_token['key'],
          $values[$contact_id]["{$used_token['group']}.{$used_token['local_name']}"],
          FALSE
        );
      }
    }
  }

}
