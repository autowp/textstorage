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

        if (isset($textstorageConfig['dbAdapter'])) {
            $textstorageConfig['dbAdapter'] = $container->get($textstorageConfig['dbAdapter']);
        }

        return new Service($textstorageConfig);
    }
}
