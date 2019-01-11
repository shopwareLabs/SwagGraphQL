<?php declare(strict_types=1);

namespace SwagGraphQL;

use Shopware\Core\Framework\Plugin;
use SwagGraphQL\DependencyInjection\CompilerPass\CustomFieldRegistryCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagGraphQL extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('services.xml');

        parent::build($container);

        $container->addCompilerPass(new CustomFieldRegistryCompilerPass());
    }

}