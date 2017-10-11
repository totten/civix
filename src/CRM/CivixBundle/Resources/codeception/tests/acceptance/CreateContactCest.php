<?php

/**
 * Class CreateContactCest
 */
class CreateContactCest {

  /**
   * Executed before the Test suite
   * @param \AcceptanceTester $I
   */
  public function _before(AcceptanceTester $I)
  {
  }

  /**
   * Executed after this test suite
   * @param \AcceptanceTester $I
   */
  public function _after(AcceptanceTester $I)
  {
  }

  /**
   * Create a contact in the local CiviCRM
   * @param \AcceptanceTester $I
   */
  public function createContact(AcceptanceTester $I) {
    $I->login('admin', 'admin');
    $I->amOnRoute('/civicrm/api');
    $I->see("CiviCRM API v3");
  }
}
