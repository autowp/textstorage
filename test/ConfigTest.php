<?php

declare(strict_types=1);

namespace AutowpTest\TextStorage;

use Autowp\TextStorage;
use Laminas\Mvc\Application;
use PHPUnit_Framework_TestCase;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $provider = new TextStorage\ConfigProvider();
        $config   = $provider();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('textstorage', $config);
    }

    public function testTexStorageRegistered()
    {
        $app = Application::init(require __DIR__ . '/_files/config/application.config.php');

        $serviceManager = $app->getServiceManager();

        $this->assertInstanceOf(TextStorage\Service::class, $serviceManager->get(TextStorage\Service::class));
    }
}
