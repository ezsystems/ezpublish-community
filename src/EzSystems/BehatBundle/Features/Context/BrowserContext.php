<?php
/**
 * File containing the BrowserContext class.
 *
 * This class contains general feature context for Behat.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\Features\Context;

use EzSystems\BehatBundle\Features\Context\FeatureContext as BaseFeatureContext;
use PHPUnit_Framework_Assert as Assertion;
use Behat\Behat\Context\Step;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Exception\PendingException;
use Behat\Mink\Exception\UnsupportedDriverActionException as MinkUnsupportedDriverActionException;

/**
 * Browser interface helper context.
 */
class BrowserContext extends BaseFeatureContext
{
    /**
     * This will tell us which containers (design) to search, should be set by child classes.
     *
     * ex:
     * $mainAttributes = array(
     *      "content"   => "thisIsATag",
     *      "column"    => array( "class" => "thisIstheClassOfTheColumns" ),
     *      "menu"      => "//xpath/for/the[menu]",
     *      ...
     * );
     *
     * @var array This will have a ( identifier => array )
     */
    public $mainAttributes = array();

    /**
     * This method works as a complement to the $mainAttributes var
     *
     * @param  string $block This should be an identifier for the block to use
     *
     * @return string
     *
     * @see $this->mainAttributes
     */
    public function makeXpathForBlock( $block = 'main' )
    {
        if ( !isset( $this->mainAttributes[strtolower( $block )] ) )
            return "";

        $xpath = $this->mainAttributes[strtolower( $block )];

        // check if value is a composed array
        if ( is_array( $xpath ) )
        {
            $nuXpath = "";
            // verify if there is a tag
            if ( isset( $xpath['tag'] ) )
            {
                if ( strpos( $xpath, "/" ) === 0 || strpos( $xpath, "(" ) === 0 )
                    $nuXpath = $xpath['tag'];
                else
                    $nuXpath = "//" . $xpath['tag'];

                unset( $xpath['tag'] );
            }
            else
                $nuXpath = "//*";

            foreach ( $xpath as $key => $value )
            {
                switch ( $key ) {
                case "text":
                    $att = "text()";
                    break;
                default:
                    $att = "@$key";
                }
                $nuXpath .= "[contains($att, {$this->literal( $value )})]";
            }

            return $nuXpath;
        }

        //  if the string is an Xpath
        if ( strpos( $xpath, "/" ) === 0 || strpos( $xpath, "(" ) === 0  )
            return $xpath;

        // if xpath is an simple tag
        return "//$xpath";
    }

    /**
     * With this function we get a centralized way to define what are the possible
     * tags for a type of data and return them as a xpath search
     *
     * @param  string $type Type of text (ie: if header/title, or list element, ...)
     *
     * @return string Xpath string for searching elements insed those tags
     *
     * @throws PendingException If the $type isn't defined yet
     */
    public function getTagsFor( $type )
    {
        switch ( strtolower( $type ) ){
        case "topic":
        case "header":
        case "title":
            return array( "h1", "h2", "h3" );
        case "list":
            return array( "li" );
        }

        throw new PendingException( "Tag's for '$type' type not defined" );
    }

    /**
     * This should be seen as a complement to self::getTagsFor() where it will
     * get the respective tags from there and will make a valid Xpath string with
     * all OR's needed
     *
     * @param array  $tags  Array of tags strings (ex: array( "a", "p", "h3", "table" ) )
     * @param string $xpath String to be concatenated to each tag
     *
     * @return string
     */
    public function concatTagsWithXpath( array $tags, $xpath = null )
    {
        $finalXpath = "";
        for ( $i = 0; !empty( $tags[$i] ); $i++ )
        {
            $finalXpath .= "//{$tags[$i]}$xpath";
            if ( !empty($tags[$i + 1]) )
                $finalXpath .= " | ";
        }

        return $finalXpath;
    }

    /**
     * This is a simple shortcut for
     * $this->getSession()->getPage()->getSelectorsHandler()->xpathLiteral()
     *
     * @param string $text
     */
    public function literal( $text )
    {
        return $this->getSession()->getSelectorsHandler()->xpathLiteral( $text );
    }

    /**
     * @Given /^(?:|I )am logged in as "([^"]*)" with password "([^"]*)"$/
     */
    public function iAmLoggedInAsWithPassword( $user, $password )
    {
        return array(
            new Step\Given( 'I am on "/user/login"' ),
            new Step\When( 'I fill in "Username" with "' . $user . '"' ),
            new Step\When( 'I fill in "Password" with "' . $password . '"' ),
            new Step\When( 'I press "Login"' ),
            new Step\Then( 'I should be redirected to "/"' ),
        );
    }

    /**
     * @Then /^(?:|I )am (?:at|on) the "([^"]*)(?:| page)"$/
     * @Then /^(?:|I )see "([^"]*)" page$/
     */
    public function iAmOnThe( $pageIdentifier )
    {
        $currentUrl = $this->getUrlWithoutQueryString( $this->getSession()->getCurrentUrl() );

        $expectedUrl = $this->locatePath( $this->getPathByPageIdentifier( $pageIdentifier ) );

        Assertion::assertEquals(
            $expectedUrl,
            $currentUrl,
            "Unexpected URL of the current site. Expected: '$expectedUrl'. Actual: '$currentUrl'."
        );
    }

    /**
     * @Given /^(?:|I )click (?:on|at) "([^"]*)" link$/
     *
     * Can also be used @When steps
     */
    public function iClickAtLink( $link )
    {
        return array(
            new Step\When( "I follow \"{$link}\"" )
        );
    }

    /**
     * @Then /^(?:|I )don\'t see links(?:|\:)$/
     */
    public function iDonTSeeLinks( TableNode $table )
    {
        $session = $this->getSession();
        $rows = $table->getRows();
        array_shift( $rows );   // this is needed to take the first row ( readability only )
        $base = $this->makeXpathForBlock( 'main' );
        foreach ( $rows as $row )
        {
            $link = $row[0];
            $url = $this->literal( str_replace( ' ', '-', $link ) );
            $literal = $this->literal( $link );
            $el = $session->getPage()->find( "xpath", "$base//a[text() = $literal][@href]" );

            Assertion::assertNull( $el, "Unexpected link found" );
        }
    }

    /**
     * @Given /^(?:|I )am (?:at|on) (?:|the )"([^"]*)" page$/
     * @When  /^(?:|I )go to (?:|the )"([^"]*)"(?:| page)$/
     */
    public function iGoToThe( $pageIdentifier )
    {
        return array(
            new Step\When( 'I am on "' . $this->getPathByPageIdentifier( $pageIdentifier ) . '"' ),
        );
    }

    /**
     * @When /^(?:|I )search for "([^"]*)"$/
     */
    public function iSearchFor( $searchPhrase )
    {
        $session = $this->getSession();
        $searchField = $session->getPage()->findById( 'site-wide-search-field' );

        Assertion::assertNotNull( $searchField, 'Search field not found.' );

        $searchField->setValue( $searchPhrase );

        // Ideally, using keyPress(), but doesn't work since no keypress handler exists
        // http://sahi.co.in/forums/discussion/2717/keypress-in-java/p1
        //     $searchField->keyPress( 13 );
        //
        // Using JS instead:
        // Note:
        //     $session->executeScript( "$('#site-wide-search').submit();" );
        // Gives:
        //     error:_call($('#site-wide-search').submit();)
        //     SyntaxError: missing ) after argument list
        //     Sahi.ex@http://<hostname>/_s_/spr/concat.js:3480
        //     @http://<hostname>/_s_/spr/concat.js:3267
        // Solution: Encapsulating code in a closure.
        // @todo submit support where recently added to MinkCoreDriver, should us it when the drivers we use support it
        try
        {
            $session->executeScript( "(function(){ $('#site-wide-search').submit(); })()" );
        }
        catch ( MinkUnsupportedDriverActionException $e )
        {
            // For drivers not able to do javascript we assume we can click the hidden button
            $searchField->getParent()->findButton( 'SearchButton' )->click();
        }

        // Store for reuse in result page
        $this->priorSearchPhrase = $searchPhrase;
    }

    /**
     * @Given /^(?:|I )see search (\d+) result$/
     */
    public function iSeeSearchResults( $arg1 )
    {
        $resultCountElement = $this->getSession()->getPage()->find( 'css', 'div.feedback' );

        Assertion::assertNotNull(
            $resultCountElement,
            'Could not find result count text element.'
        );

        Assertion::assertEquals(
            "Search for \"{$this->priorSearchPhrase}\" returned {$arg1} matches",
            $resultCountElement->getText()
        );
    }

    /**
     * @Then /^(?:|I )see links for Content objects(?:|\:)$/
     *
     * $table = array(
     *      array(
     *          [link|object],  // mandatory
     *          parentLocation, // optional
     *      ),
     *      ...
     *  );
     *
     * @todo verify if the links are for objects
     * @todo check if it has a different url alias
     * @todo check "parent" node
     */
    public function iSeeLinksForContentObjects( TableNode $table )
    {
        $session = $this->getSession();
        $rows = $table->getRows();
        array_shift( $rows );   // this is needed to take the first row ( readability only )
        $base = $this->makeXpathForBlock( 'main' );
        foreach ( $rows as $row )
        {
            if( count( $row ) >= 2 )
                list( $link, $parent ) = $row;
            else
                $link = $row[0];

            Assertion::assertNotNull( $link, "Missing link for searching on table" );

            $url = $this->literal( str_replace( ' ', '-', $link ) );

            $el = $session->getPage()->find( "xpath", "$base//a[contains(@href, $url)]" );

            Assertion::assertNotNull( $el, "Couldn't find a link for object '$link' with url containing '$url'" );
        }
    }

    /**
     * @Then /^(?:|I )see links for Content objects in following order(?:|\:)$/
     *
     *  @todo check "parent" node
     */
    public function iSeeLinksForContentObjectsInFollowingOrder( TableNode $table )
    {
        $page = $this->getSession()->getPage();
        $base = $this->makeXpathForBlock( 'main' );
        // get all links
        $links = $page->findAll( "xpath", "$base//a[@href]" );

        $i = $passed = 0;
        $last = '';
        $rows = $table->getRows();
        array_shift( $rows );   // this is needed to take the first row ( readability only )

        foreach ( $rows as $row )
        {
            // get values ( if there is no $parent defined on gherkin there is
            // no problem since it will only be tested if it is not empty
            if( count( $row ) >= 2 )
                list( $name, $parent ) = $row;
            else
                $name = $row[0];

            $url = str_replace( ' ', '-', $name );

            // find the object
            while(
                !empty( $links[$i] )
                && strpos( $links[$i]->getAttribute( "href" ), $url ) === false
                && strpos( $links[$i]->getText(), $name ) === false
            )
                $i++;

            $test = !null;
            if( empty( $links[$i] ) )
                $test = null;

            // check if the link was found or the $i >= $count
            Assertion::assertNotNull( $test, "Couldn't find '$name' after '$last'" );

            $passed++;
            $last = $name;
        }

        Assertion::assertEquals(
            count( $rows ),
            $passed,
            "Expected to evaluate '{count( $rows )}' links evaluated '{$passed}'"
        );
    }

    /**
     * @Then /^(?:|I )see links in(?:|\:)$/
     */
    public function iSeeLinksIn( TableNode $table )
    {
        $session = $this->getSession();
        $rows = $table->getRows();
        array_shift( $rows );   // this is needed to take the first row ( readability only )
        foreach ( $rows as $row )
        {
            // prepare data
            Assertion::assertEquals( count( $row ), 2, "The table should be have array with link and tag" );
            list( $link, $type ) = $row;

            // make xpath
            $literal = $this->literal( $link );
            $xpath = $this->concatTagsWithXpath(
                $this->getTagsFor( $type ),
                "//a[@href and text() = $literal]"
            );

            $el = $session->getPage()->find( "xpath", $xpath );

            Assertion::assertNotNull( $el, "Couldn't find a link with '$link' text" );
        }
    }

    /**
     * @Then /^(?:|I )see (\d+) "([^"]*)" elements listed$/
     */
    public function iSeeListedElements( $count, $objectType )
    {
        $objectListTable = $this->getSession()->getPage()->find(
            'xpath',
            '//table[../h1 = "' . $objectType  . ' list"]'
        );

        Assertion::assertNotNull(
            $objectListTable,
            'Could not find listing table for ' . $objectType
        );

        Assertion::assertCount(
            $count + 1,
            $objectListTable->findAll( 'css', 'tr' ),
            'Found incorrect number of table rows.'
        );
    }

    /**
     * @Then /^(?:|I )should be redirected to "([^"]*)"$/
     */
    public function iShouldBeRedirectedTo( $redirectTarget )
    {
        $redirectForm = $this->getSession()->getPage()->find( 'css', 'form[name="Redirect"]' );

        Assertion::assertNotNull(
            $redirectForm,
            'Missing redirect form.'
        );

        Assertion::assertEquals( $redirectTarget, $redirectForm->getAttribute( 'action' ) );
    }

    /**
     * @Then /^(?:|I )want dump of (?:|the )page$/
     */
    public function iWantDumpOfThePage()
    {
        echo $this->getSession()->getPage()->getContent();
    }
}
