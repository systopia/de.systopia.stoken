<?php

/**
 * Type stub for the optional "de.systopia.eventmessages" extension
 * (https://github.com/systopia/de.systopia.eventmessages).
 *
 * de.systopia.stoken only integrates with this extension if it happens to be
 * installed (see CRM_Stoken_EventMessagesIntegration and the class_exists()
 * check in stoken_civicrm_config()); it is not a hard dependency, so it isn't
 * required via composer. This file exists purely so PHPStan can resolve the
 * type hints used in that integration, regardless of whether/where the real
 * extension is installed. It is never included or executed and only needs to
 * mirror the handful of methods actually used here.
 */

namespace Civi\EventMessages;

class MessageTokens {

  /**
   * @return array<string, mixed>
   */
  public function getTokens(): array {
  }

  /**
   * @param string $name
   * @return bool
   */
  public function requiresToken($name) {
  }

  /**
   * @param string $name
   * @param mixed $data
   * @param bool $enhance
   */
  public function setToken($name, $data, $enhance = TRUE) {
  }

}

class MessageTokenList {

  /**
   * @param string $key
   * @param string $description
   */
  public function addToken($key, $description) {
  }

}
