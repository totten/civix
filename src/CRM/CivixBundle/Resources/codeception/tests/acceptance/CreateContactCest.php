<?php

/**
 * Class CreateContactCest
 */
class CreateContactCest {

  /**
   * Executed before the Test suite
   * @param \AcceptanceTester $I
   */
  public function _before(AcceptanceTester $I) {
  }

  /**
   * Executed after this test suite
   * @param \AcceptanceTester $I
   */
  public function _after(AcceptanceTester $I) {
  }

  /**
   * Create a contact in the local CiviCRM
   * @param \AcceptanceTester $I
   */
  public function createContact(AcceptanceTester $I) {
    global $_CV;
    $I->amGoingTo('Login to Civicrm and create a new Contact');
    $I->login($_CV['ADMIN_USER'], $_CV['ADMIN_PASS']);
    $I->wantTo('Create an example Contact');
    $I->amOnRoute('civicrm/contact/add?reset=1&ct=Individual');
    $I->expect('The Create Individual Form');
    $I->see("New Individual");

    $I->fillField("#first_name", "Joe");
    $I->fillField("#last_name", "Tester");
    $I->fillField("#email_1_email", "tester@example.com");
    $I->click('#_qf_Contact_upload_view-top');
    $I->see("Joe Tester");
  }

}
