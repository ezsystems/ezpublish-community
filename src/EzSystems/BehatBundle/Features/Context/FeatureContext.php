<?php
/**
 * File containing the FeatureContext class.
 *
 * This class contains general feature context for Behat.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\Features\Context;

use Behat\Behat\Event\OutlineExampleEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
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
    private $kernel;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array Array to map identifier to urls, should be set by child classes.
     */
    protected $pageIdentifierMap = array();

    /**
     * @var string
     */
    private $priorSearchPhrase = '';

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
     * @BeforeScenario
     *
     * @param FeatureEvent $event
     */
    public function scenarioDatabaseClear( $event )
    {
        $parameters = $this->parameters;

        // verify if it's setted to reset Db each scenario
        if ( !isset( $parameters['scenarioDbReset'] ) || !$parameters['scenarioDbReset'] )
        {
            return;
        }

        $this->databaseClear( $parameters );
    }

    /**
     * Clear database with the parameters given
     *
     * @param array $parameters Parameters passed to define how to reset dabase
     *
     * @todo pass this funciton to static so that the BeforeFeature hook works also
     * @todo discover why the symfony options are not taken into account
     */
    public function databaseClear( array $parameters = null )
    {
        $defaultOptions = array(
            'quiet' => true,
            'fixture' => '',
        );

        $inputOptions = array_merge( $defaultOptions, $parameters );

        $options = array(
            'command' => 'ezpublish:test:init_db',
            '--no-interaction' => true,
            '--no-database' => !$inputOptions['dbinit'],
            '--fixture' => $inputOptions['fixture'],
            '--quiet' => $inputOptions['quiet'],
        );

        $application = new Application( $this->kernel );
        $application->add( new \eZ\Bundle\EzPublishCoreBundle\Command\TestInitDbCommand() );
        $command = $application->find( "ezpublish:test:init_db" );
        $command->run( new ArrayInput( $options ), new ConsoleOutput() );
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
}
