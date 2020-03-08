<?php

declare(strict_types=1);

namespace Autowp\TextStorage;

class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'textstorage'  => $this->getTextStorageConfig(),
        ];
    }

    /**
     * Return application-level dependency configuration.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                Service::class => Factory\ServiceFactory::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTextStorageConfig()
    {
        return [
            'textTableName'     => 'textstorage_text',
            'revisionTableName' => 'textstorage_revision',
            'dbAdapter'         => null,
        ];
    }
}
