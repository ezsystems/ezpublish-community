@contenttypegroup @commonFeature
Feature: Read one Content Type Group
    Read one Content Type Group
    As an administrator or anonymous user
    I want to be able to read a ContentTypeGroup

    @qa-266
    Scenario Outline: Retrieve a Content Type Group
        Given I am logged as an "administrator"
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
            | identifier | B     |
        When I open ContentTypeGroup with "<identification>" "<value>"
        Then I should see a ContentTypeGroup with:
            | field      | value |
            | id         | A     |
            | identifier | B     |

        Examples:
            | identification | value |
            | id             | A     |
            | identifier     | B     |

    @qa-266
    Scenario: Attempt to retrieve a non existing Content Type Group
        Given I am logged as an "administrator"
        And I don't have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
        When I open ContentTypeGroup with "id" "A"
        Then I should see not found error


    @qa-266
    Scenario: Attempt to retrieve a Content Type Group with a non authorized user
        Given I am an anonymous visitor
        And I have a "ContentTypeGroup" with:
            | field      | value |
            | id         | A     |
        When I open ContentTypeGroup with "id" "A"
        Then I should see an unauthorized error
