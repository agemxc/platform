<?php

namespace Oro\Bundle\EntityBundle;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\GeneratedValueStrategyListenerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedServiceViaAddMethodCompilerPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\DoctrineUtils\DependencyInjection\AddTransactionWatcherCompilerPass;
use Oro\Component\PhpUtils\ClassLoader;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The EntityBundle bundle class.
 */
class OroEntityBundle extends Bundle
{
    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        // register logging hydrators class loader
        $loader = new ClassLoader(
            'OroLoggingHydrator\\',
            $kernel->getCacheDir() . DIRECTORY_SEPARATOR . 'oro_entities'
        );
        $loader->register();

        // register connection proxy class that supports the transaction watcher
        $loader = new ClassLoader(
            AddTransactionWatcherCompilerPass::CONNECTION_PROXY_NAMESPACE . '\\',
            AddTransactionWatcherCompilerPass::getConnectionProxyRootDir($kernel->getCacheDir())
        );
        $loader->register();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new Compiler\DatabaseCheckerCompilerPass());
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_entity.entity_alias_loader',
            'oro_entity.class_provider',
            'addEntityClassProvider'
        ));
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_entity.entity_alias_loader',
            'oro_entity.alias_provider',
            'addEntityAliasProvider'
        ));
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_entity.entity_class_name_provider',
            'oro_entity.class_name_provider',
            'addProvider'
        ));
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_entity.exclusion_provider',
            'oro_entity.exclusion_provider.default',
            'addProvider'
        ));
        $container->addCompilerPass(new Compiler\VirtualFieldProvidersCompilerPass());
        $container->addCompilerPass(new Compiler\VirtualRelationProvidersCompilerPass());
        $container->addCompilerPass(new Compiler\QueryHintResolverPass());
        $container->addCompilerPass(new Compiler\EntityFieldHandlerPass());
        $container->addCompilerPass(new Compiler\CustomGridFieldValidatorCompilerPass());
        $container->addCompilerPass(new Compiler\ManagerRegistryCompilerPass());
        $container->addCompilerPass(new Compiler\DataCollectorCompilerPass());
        $container->addCompilerPass(new Compiler\EntityFallbackCompilerPass());
        $container->addCompilerPass(new Compiler\SqlWalkerPass());
        $container->addCompilerPass(new Compiler\EntityRepositoryCompilerPass());
        $container->addCompilerPass(new Compiler\EntityDeleteHandlerCompilerPass());

        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new Compiler\GeneratedValueStrategyListenerPass());
            $container->moveCompilerPassBefore(
                GeneratedValueStrategyListenerPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );
        }

        $container->addCompilerPass(
            new AddTransactionWatcherCompilerPass('oro.doctrine.connection.transaction_watcher')
        );
    }
}
