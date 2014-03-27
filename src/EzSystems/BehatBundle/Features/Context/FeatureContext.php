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
    const DB_DUMP_FILE_PATH = 'behatdbdump.sql';

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    static private $kernel;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array Array to map identifier to urls, should be set by child classes.
     */
    protected $pageIdentifierMap = array();

    /**
     * This will containt the source path for media files
     *
     * ex:
     * $fileSource = array(
     * 	    "Video 1" => "/var/storage/original/media/video1.mp4",
     * );
     *
     * @var array This will have a ( 'identifier' => 'path' )
     */
    protected $fileSource = array();

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
     * DB commands
     *
     * @param string $type What should be executed (options: "backup" or "restore")
     */
    static public function databaseCommand( $type )
    {
        // get database parameters
        // if it throws an error, than this should be testing SetupWizard or
        // any test previous of the installation
        try
        {
            $dbParams = self::$kernel->getContainer()->get( 'ezpublish.connection' )->getConnection()->getParams();
        }
        catch ( \Exception $e )
        {
            return;
        }

        // these are the strings to be changed on the template commands
        $search = array(
            'user'   => "<% username %>",
            'passwd' => "<% password %>",
            'db'     => "<% database %>",
            'host'   => "<% host %>",
            'file'   => "<% dumpfile %>",
        );

        // template commands
        $allCommands = array(
            'backup' => array(
                'mysql' => 'mysqldump --user="' . $search['user'] . '" --password="' . $search['passwd'] . '" ' . $search['db'] . ' > ' . $search['file'],
                'pgsql' => 'export PGPASSWD="' . $search['passwd'] . '" && pg_dump --username="' . $search['user'] . '" ' . $search['db'] . ' > ' . $search['file'],
            ),
            'restore' => array(
                'mysql' => 'mysql --user="' . $search['user'] . '" --password="' . $search['passwd'] . '" ' . $search['db'] . ' < ' . $search['file'],
                'pgsql' => 'export PGPASSWD="' . $search['passwd'] . '" && pg_restore --username="' . $search['user'] . '" --dbname="' . $search['db'] . '" ' . $search['file'],
            )
        );

        // get database type
        $dbtype = str_replace( array( 'pdo', '_' ), array( '', '' ), $dbParams['driver'] );

        // finally create the command
        $command = str_replace(
            array(
                $search['user'],
                $search['passwd'],
                $search['db'],
                $search['host'],
                $search['file'],
            ),
            array(
                $dbParams['user'],
                $dbParams['password'],
                $dbParams['dbname'],
                $dbParams['host'],
                $filePath,
            ),
            $allCommands[$type][$dbtype]
        );
        // and execute it
        exec( $command );
    }

    /**
     * Make backup of database
     *
     * @BeforeSuite
     */
    static public function databaseBackup( $event )
    {
        self::databaseCommand( 'backup' );
    }

    /**
     * Import the backup script made from self::databaseBackup
     *
     * @beforeFeature
     */
    static public function databaseCleaner( $event )
    {
        self::databaseCommand( 'restore' );
        exec( 'php ezpublish/console cache:clear --env=behat' );
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
     * Returns the path associated with the $fileSource
     *
     * @param sring $fileSource
     *
     * @return string
     */
    protected function getPathByFileSource( $file )
    {
        if ( !isset( $this->fileSource[$file] ) )
        {
            throw new \RuntimeException( "Unknown file '{$file}'." );
        }

        return $this->fileSource[$file];
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
