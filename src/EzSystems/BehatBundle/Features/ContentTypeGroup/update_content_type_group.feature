@contenttypegroup @commonFeature
Feature: Update a Content Type Group
    Update a Content Type Group
    As an administrator
    I want to be able to update a ContentTypeGroup

    @qa-268
    Scenario: Update Content Type Group fields
        Given I am logged as an "administrator"
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
            | identifier | B     |
        When I update ContentTypeGroup identified by "identifier" "B" with:
            | field      | value |
            | identifier | C     |
        Then I should see a ContentTypeGroup with:
            | field      | value |
            | id         | A     |
            | identifier | C     |
        And I shouldn't see a ContentTypeGroup identified by "identifier" "B"

    @qa-268
    Scenario: Attempt to update a ContentTypeGroup identifier to a existing one
        Given I am logged as an "administrator"
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
            | identifier | B     |
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | E     |
            | identifier | F     |
        When I update ContentTypeGroup identified by "identifier" "B" with:
            | field      | value |
            | identifier | F     |
        Then I should see invalid field error
        And I should see 1 ContentTypeGroup identified by "identifier" "F"
        And I should see ContentTypeGroup identified by "id" "A" with:
            | field      | value |
            | identifier | B     |
        And I should see ContentTypeGroup identified by "id" "E" with:
            | field      | value |
            | identifier | F     |

    @qa-268 @no-gui
    Scenario: Attempt to update a ContentTypeGroup with a non authorized user
        Given I am an anonymous user
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
            | identifier | B     |
        When I update ContentTypeGroup identified by "identifier" "B" with:
            | field      | value |
            | identifier | C     |
        Then I should see an unauthorized error
        And I should see 1 ContentTypeGroup identified by "identifier" "B"
        And I should see ContentTypeGroup identified by "id" "A" with:
            | field      | value |
            | identifier | B     |
        And I shouldn't see a ContentTypeGroup identified by "identifier" "C"

    @qa-268 @no-api
    Scenario: Attempt to reach update ContentTypeGroup page with non authorized user
        Given I am an anonymous user
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
            | identifier | B     |
        When I open "Update ContentTypeGroup" page
        Then I should see an unauthorized error
