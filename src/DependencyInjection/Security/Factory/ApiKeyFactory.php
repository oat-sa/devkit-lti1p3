<?php

namespace App\DependencyInjection\Security\Factory;

use App\Security\Api\Firewall\ApiKeyListener;
use App\Security\Api\Provider\ApiKeyProvider;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiKeyFactory implements SecurityFactoryInterface
{
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'api_key';
    }

    public function create(
        ContainerBuilder $container,
        string $id,
        array $config,
        string $userProvider,
        ?string $defaultEntryPoint
    ): array {
        $providerId = 'security.authentication.provider.api_key.'.$id;
        $container
            ->setDefinition($providerId, new ChildDefinition(ApiKeyProvider::class))
            ->setArgument(0, new Reference($userProvider));

        $listenerId = 'security.authentication.listener.api_key.'.$id;
        $container->setDefinition($listenerId, new ChildDefinition(ApiKeyListener::class));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        return;
    }
}