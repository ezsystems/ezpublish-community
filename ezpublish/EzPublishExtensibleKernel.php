<?php
use eZ\Bundle\EzPublishCoreBundle\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

include __DIR__ . '/EzPublishKernel.php';

class EzPublishExtensibleKernel extends EzPublishKernel
{
    /** @var string */
    protected $ezRootDir;

    /**
     * Loads the container configuration
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     *
     * @api
     */
    public function registerContainerConfiguration( LoaderInterface $loader )
    {
        $kernelRootDir = $this->getRootDir();

        // ezpublish/config.yml
        $environment = $this->getEnvironment();
        $loader->load( __DIR__ . '/config/config_' . $environment . '.yml' );

        // app/config.yml
        if ( $kernelRootDir !== __DIR__ )
        {
            $configFile = $kernelRootDir . '/config/config_' . $environment . '.yml';
            if ( is_file( $configFile ) )
            {
                if ( !is_readable( $configFile ) )
                {
                    throw new RuntimeException( "Configuration file '$configFile' is not readable." );
                }
                $loader->load( $configFile );
            }
        }

        // try the app config first, if any
        $configFile = $kernelRootDir . '/config/ezpublish_' . $environment . '.yml';

        // load setup configuration
        if ( !is_file( $configFile ) )
        {
            $configFile = __DIR__ . '/config/ezpublish_setup.yml';
        }

        if ( !is_readable( $configFile ) )
        {
            throw new RuntimeException( "Configuration file '$configFile' is not readable." );
        }

        $loader->load( $configFile );

    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'ez_kernel.root_dir' => $this->getEzRootDir()
            )
        );
    }

    public function getEzRootDir()
    {
        if ( $this->ezRootDir === null )
        {
            $this->ezRootDir = realpath( __DIR__ );
        }

        return $this->ezRootDir;
    }
}
