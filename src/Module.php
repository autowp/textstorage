<?php

declare(strict_types=1);

namespace Autowp\TextStorage;

class Module
{
    /**
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'textstorage'     => $provider->getTextStorageConfig(),
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }
}
