<?php
/**
 * File containing the FeatureContext class.
 *
 * This class contains general feature context for Behat.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\Features\Context;

use Behat\Behat\Event\OutlineExampleEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Behat\Behat\Exception\PendingException;
use PHPUnit_Framework_Assert as Assertion;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Feature context.
 */
class FeatureContext extends MinkContext implements KernelAwareInterface
{
    const DEFAULT_SITEACCESS_NAME = 'behat_site';

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var array Array to map identifier to urls, should be set by child classes.
     */
    protected $pageIdentifierMap = array();

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
     * @var string
     */
    protected $priorSearchPhrase = '';

    /**
     * Initializes context with parameters from behat.yml.
     *
     * @param array $parameters
     */
    public function __construct( array $parameters )
    {
        $this->parameters = $parameters;
    }

    /**
     * Sets HttpKernel instance.
     * This method will be automatically called by Symfony2Extension ContextInitializer.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel( KernelInterface $kernel )
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     *
     * @param ScenarioEvent|OutlineExampleEvent $event
     */
    public function prepareFeature( $event )
    {
        // Inject a properly generated siteaccess if the kernel is booted, and thus container is available.
        $this->kernel->getContainer()->set( 'ezpublish.siteaccess', $this->generateSiteAccess() );
    }

    /**
     * Generates the siteaccess
     *
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess
     */
    protected function generateSiteAccess()
    {
        $siteAccessName = getenv( 'EZPUBLISH_SITEACCESS' );
        if ( !$siteAccessName )
        {
            $siteAccessName = static::DEFAULT_SITEACCESS_NAME;
        }

        return new SiteAccess( $siteAccessName, 'cli' );
    }

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
     * Returns the path associated with $pageIdentifier
     *
     * @param string $pageIdentifier
     *
     * @return string
     */
    protected function getPathByPageIdentifier( $pageIdentifier )
    {
        if ( !isset( $this->pageIdentifierMap[$pageIdentifier] ) )
        {
            throw new \RuntimeException( "Unknown page identifier '{$pageIdentifier}'." );
        }

        return $this->pageIdentifierMap[$pageIdentifier];
    }

    /**
     * Returns $url without its query string
     *
     * @param string $url
     *
     * @return string
     */
    protected function getUrlWithoutQueryString( $url )
    {
        if ( strpos( $url, '?' ) !== false )
        {
            $url = substr( $url, 0, strpos( $url, '?' ) );
        }

        return $url;
    }

    /**
     * @Given /^test is pending(?:| (.+))$/
     */
    public function testIsPendingDesign( $reason )
    {
        throw new PendingException( $reason );
    }
}
