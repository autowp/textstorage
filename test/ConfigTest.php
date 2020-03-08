<?php

declare(strict_types=1);

namespace AutowpTest\TextStorage;

use Autowp\TextStorage;
use Laminas\Mvc\Application;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $provider = new TextStorage\ConfigProvider();
        $config   = $provider();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('textstorage', $config);
    }

    public function testTexStorageRegistered(): void
    {
        $app = Application::init(require __DIR__ . '/_files/config/application.config.php');

        $serviceManager = $app->getServiceManager();

        $this->assertInstanceOf(TextStorage\Service::class, $serviceManager->get(TextStorage\Service::class));
    }
}
