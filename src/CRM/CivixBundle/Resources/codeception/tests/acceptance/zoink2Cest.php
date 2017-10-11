<?php


class zoink2Cest {

  public function _before(AcceptanceTester $I)
  {
  }

  public function _after(AcceptanceTester $I)
  {
  }

  public function first_test(AcceptanceTester $I) {
    $I->login('admin', 'FJQ6sSnSoSi5');
    $I->amOnRoute('/civicrm/api');
    $I->see("CiviCRM API v3");
  }
}
