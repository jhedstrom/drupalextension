Feature: AJAX wait diagnostics

  When 'iWaitForAjaxToFinish()' fails to clear the wait condition before the
  configured 'ajax_timeout', the resulting RuntimeException must include
  diagnostic state captured from the live browser - the page URL, jQuery
  counters, and a list of active 'Drupal.ajax.instances' - so failures in
  CI logs are actionable. The fixture in 'fixtures/blackbox/ajax_hang.html'
  pre-loads a permanently 'ajaxing' Drupal AJAX instance, guaranteeing the
  timeout fires.

  This scenario runs the wait directly against a real browser session
  rather than via the BehatCli subprocess pattern, because @javascript
  inside a sub-process is a known-hanging combination.

  @test-blackbox @javascript
  Scenario: Wait for AJAX times out with verbose diagnostic output
    Given I visit "/ajax_hang.html"
    Then the AJAX wait should time out with verbose diagnostic info
