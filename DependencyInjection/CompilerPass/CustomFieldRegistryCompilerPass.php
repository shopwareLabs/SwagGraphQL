<?php declare(strict_types=1);

namespace SwagGraphQL\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomFieldRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->collectQueries($container);
        $this->collectMutations($container);
    }

    private function collectQueries(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('swag_graphql.queries');
        $registry = $container->getDefinition('swag_graphql.query_registry');

        foreach ($services as $serviceId => $attributes) {
            $query = null;
            foreach ($attributes as $attr) {
                if (array_key_exists('query', $attr)) {
                    $query = $attr['query'];
                    break;
                }

                throw new \RuntimeException(sprintf('Missing query attribute in service tag for class %s.', $serviceId));
            }

            $registry->addMethodCall('addField', [$query, new Reference($serviceId)]);
        }
    }

    private function collectMutations(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('swag_graphql.mutations');
        $registry = $container->getDefinition('swag_graphql.mutation_registry');

        foreach ($services as $serviceId => $attributes) {
            $mutation = null;
            foreach ($attributes as $attr) {
                if (array_key_exists('mutation', $attr)) {
                    $mutation = $attr['mutation'];
                    break;
                }

                throw new \RuntimeException(sprintf('Missing mutation attribute in service tag for class %s.', $serviceId));
            }

            $registry->addMethodCall('addField', [$mutation, new Reference($serviceId)]);
        }

    }
}
