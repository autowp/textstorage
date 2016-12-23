<?php

namespace Autowp\TextStorage\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

use Autowp\TextStorage\Service;

class ServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $textstorageConfig = isset($config['textstorage']) ? $config['textstorage'] : [];

        $textstorageConfig['dbAdapter'] = $container->get(\Zend\Db\Adapter\AdapterInterface::class);

        return new Service($textstorageConfig);
    }
}
