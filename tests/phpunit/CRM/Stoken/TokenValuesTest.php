<?php

declare(strict_types = 1);

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class CRM_Stoken_TokenValuesTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @covers \CRM_Stoken_AddressTokens::addTokens
   */
  public function testAddTokensDoesNotAccumulateAcrossLocationTypes(): void {
    $tokens = [];
    CRM_Stoken_AddressTokens::addTokens($tokens);

    $location_type_map = CRM_Stoken_AddressTokens::getLocationTypeMap();
    static::assertNotEmpty($location_type_map);

    foreach ($location_type_map as $location_type_id => $section_name) {
      static::assertArrayHasKey($section_name, $tokens);
      foreach (array_keys($tokens[$section_name]) as $token_name) {
        static::assertStringStartsWith(
          "$section_name.{$location_type_id}_",
          $token_name,
          "Token $token_name does not belong into group $section_name"
        );
      }
    }
  }

  /**
   * @covers \CRM_Stoken_DateTokens::tokenValues
   */
  public function testFrenchLongDateContainsNoStrayApostrophe(): void {
    $values = [];
    CRM_Stoken_DateTokens::tokenValues($values, [1], NULL, ['date' => ['fr_FR_longue']]);

    $french_date = $values[1]['date.fr_FR_longue'];
    static::assertIsString($french_date);
    static::assertStringStartsWith('le ', $french_date);
    static::assertStringNotContainsString("'", $french_date);
    if ((int) (new DateTime())->format('j') === 1) {
      static::assertStringContainsString('1er ', $french_date);
    }
  }

  /**
   * @covers ::stoken_civicrm_tokenValues
   */
  public function testTokenValuesAcceptsCommaSeparatedContactIds(): void {
    $values = [];
    stoken_civicrm_tokenValues($values, '1,2', NULL, ['date' => ['kurz']]);

    static::assertArrayHasKey(1, $values);
    static::assertArrayHasKey(2, $values);
    static::assertSame($values[1]['date.kurz'], $values[2]['date.kurz']);
  }

  /**
   * @covers ::stoken_civicrm_tokenValues
   */
  public function testTokenValuesAcceptsContactIdKey(): void {
    $values = [];
    stoken_civicrm_tokenValues($values, ['contact_id' => 1], NULL, ['date' => ['kurz']]);

    static::assertArrayHasKey(1, $values);
  }

}
