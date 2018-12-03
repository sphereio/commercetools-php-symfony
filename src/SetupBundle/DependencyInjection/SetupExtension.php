<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\DependencyInjection;

use Commercetools\Symfony\CtpBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SetupExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['custom_types'])) {
            $container->setParameter('commercetools.custom_types', $config['custom_types']);
        }
    }
}
