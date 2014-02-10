@contenttypegroup @commonFeature
Feature: Read all Content Type Groups
    Read all Content Type Groups
    As an administrator or anonymous user
    I want to know all existing Content Type Groups

    @qa-265
    Scenario: Read all ContentTypeGroups
        Given I am logged as an "administrator"
        When I open "ContentTypeGroups" list
        Then I see the following ContentTypeGroups:
            | ContentTypeGroups |
            | Content           |
            | Users             |
            | Media             |
            | Setup             |

    @qa-265
    Scenario: Attempt to read all ContentTypeGroups with a non authorized user
        Given I am an anonymous visitor
        When I open "ContentTypeGroups" list
        Then I see an unauthorized error
