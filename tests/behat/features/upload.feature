Feature: Upload test
  As a developer
  I want to upload files to entity fields in test scenarios
  So that I can test image and file attachment workflows

  @test-drupal @api
  Scenario: Upload an image.
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "This!"
    And I attach the file "image.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "Nothing"
    And I press "Save"
    Then I should see the heading "This!"

  # A file attached in one scenario is removed afterwards, so the next
  # scenario that uploads the same filename is served the original path
  # instead of a Drupal-renamed "_0" variant.
  @test-drupal @api
  Scenario: Attaching a file in one scenario cleans it up afterwards
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "First cleanup upload"
    And I attach the file "cleanup_check.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "First"
    And I press "Save"
    Then I should see the heading "First cleanup upload"

  @test-drupal @api
  Scenario: Re-uploading the same filename keeps the original path after cleanup
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "Second cleanup upload"
    And I attach the file "cleanup_check.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "Second"
    And I press "Save"
    Then I should see the heading "Second cleanup upload"
    # Reject any rename suffix ("_0", "_1", ...), not just "_0", so a failed
    # cleanup cannot slip through on a higher-numbered variant.
    And the response should not contain "cleanup_check_"

  # The @no-file-cleanup tag opts a scenario out, leaving its uploaded file in
  # place so the next upload of the same filename is renamed by Drupal - its
  # native behaviour.
  @test-drupal @api @no-file-cleanup
  Scenario: A file attached with the opt-out tag is left in place
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "Kept upload"
    And I attach the file "optout_check.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "Kept"
    And I press "Save"
    Then I should see the heading "Kept upload"

  @test-drupal @api
  Scenario: Re-uploading the same filename is renamed when cleanup was opted out
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "Renamed upload"
    And I attach the file "optout_check.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "Renamed"
    And I press "Save"
    Then I should see the heading "Renamed upload"
    # The leftover file forces a rename; assert the "_N" suffix without
    # pinning the exact number, which depends on pre-existing files.
    And the response should contain "optout_check_"

  # Attaching a file under the blackbox driver records the upload, but the
  # after-scenario cleanup is a no-op because the driver cannot manage Drupal
  # file entities.
  @test-blackbox
  Scenario: Attaching a file on a non-Drupal driver does not break cleanup
    Given I am at "upload_form.html"

    When I attach the file "image.png" to "upload"
    Then I should see the heading "Upload Form"
