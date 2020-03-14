<?php

declare(strict_types=1);

namespace Autowp\TextStorage\Factory;

use Autowp\TextStorage\Exception;
use Autowp\TextStorage\Service;
use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param string $requestedName
     * @return Service
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config            = $container->has('config') ? $container->get('config') : [];
        $textstorageConfig = $config['textstorage'] ?? [];

        $textstorageConfig['dbAdapter'] = $container->get(AdapterInterface::class);

        return new Service($textstorageConfig);
    }
}
