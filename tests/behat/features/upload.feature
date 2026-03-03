@api
Feature: Upload test.

  Scenario: Upload an image.
    Given I am logged in as a user with the "administrator" role

    When I go to "/node/add/article"
    And I fill in "Title" with "This!"
    And I attach the file "image.png" to "files[field_image_0]"
    And I press "Upload"
    And I fill in "field_image[0][alt]" with "Nothing"
    And I press "Save"
    Then I should see the heading "This!"
