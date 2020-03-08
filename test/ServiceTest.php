<?php

declare(strict_types=1);

namespace AutowpTest\TextStorage;

use Autowp\TextStorage;
use Laminas\Mvc\Application;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private const EXAMPLE_TEXT    = 'Example text';
    private const EXAMPLE_TEXT_2  = 'Example text 2';
    private const EXAMPLE_TEXT_3  = 'Example text 3';
    private const EXAMPLE_USER_ID = 1;

    private function getStorage(): TextStorage\Service
    {
        $app = Application::init(require __DIR__ . '/_files/config/application.config.php');

        $serviceManager = $app->getServiceManager();

        return $serviceManager->get(TextStorage\Service::class);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testReadWrite(): void
    {
        $storage = $this->getStorage();

        $textId = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);

        $text = $storage->getText($textId);

        $this->assertEquals(self::EXAMPLE_TEXT, $text);

        $info = $storage->getTextInfo($textId);

        $revisionInfo = $storage->getRevisionInfo($textId, $info['revision']);

        $this->assertEquals([
            'text'     => self::EXAMPLE_TEXT,
            'revision' => $info['revision'],
            'user_id'  => self::EXAMPLE_USER_ID,
        ], $revisionInfo);

        $storage->setText($textId, self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $userIds = $storage->getTextUserIds($textId);
        $this->assertEquals([self::EXAMPLE_USER_ID], $userIds);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testGetFirstText(): void
    {
        $storage = $this->getStorage();

        $textId1 = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textId2 = $storage->createText(self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $text = $storage->getFirstText([$textId1, $textId2]);
        $this->assertEquals(self::EXAMPLE_TEXT, $text);

        $text = $storage->getFirstText([$textId2, $textId1]);
        $this->assertEquals(self::EXAMPLE_TEXT_2, $text);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testSecondTextNotAffectsFirst(): void
    {
        $storage = $this->getStorage();

        $textId1   = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textInfo1 = $storage->getTextInfo($textId1);

        $textId2 = $storage->createText(self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $storage->setText($textId2, self::EXAMPLE_TEXT_3, self::EXAMPLE_USER_ID);

        $newTextInfo1 = $storage->getTextInfo($textId1);
        $this->assertEquals($textInfo1, $newTextInfo1);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testThrowExceptionOnUnexpectedOption(): void
    {
        $this->expectException(TextStorage\Exception::class);
        new TextStorage\Service([
            'foo' => 'bar',
        ]);
    }

    public function testReturnNullOnEmptyIds(): void
    {
        $storage = $this->getStorage();

        $text = $storage->getFirstText([]);

        $this->assertNull($text);
    }

    public function testReturnNullWhenNothingFound(): void
    {
        $storage = $this->getStorage();

        $result = $storage->getText(123456789);
        $this->assertNull($result);

        $result = $storage->getTextInfo(123456789);
        $this->assertNull($result);

        $result = $storage->getFirstText([123456789]);
        $this->assertNull($result);

        $result = $storage->getRevisionInfo(123456789, 1);
        $this->assertNull($result);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testThrowExceptionWhenSetUnexistentText(): void
    {
        $this->expectException(TextStorage\Exception::class);

        $storage = $this->getStorage();

        $storage->setText(123456789, self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testRevisionNumberIncresed(): void
    {
        $storage = $this->getStorage();

        $textId    = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textInfo1 = $storage->getTextInfo($textId);

        $storage->setText($textId, self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);
        $textInfo2 = $storage->getTextInfo($textId);

        $this->assertEquals($textInfo1['revision'] + 1, $textInfo2['revision']);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testRevisionNumberNotIncresedWhenTextNotChanged(): void
    {
        $storage = $this->getStorage();

        $textId    = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textInfo1 = $storage->getTextInfo($textId);

        $storage->setText($textId, self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textInfo2 = $storage->getTextInfo($textId);

        $this->assertEquals($textInfo1['revision'], $textInfo2['revision']);
    }

    /**
     * @throws TextStorage\Exception
     */
    public function testGetFirstTextIgnoreEmptyText(): void
    {
        $storage = $this->getStorage();

        $textId1 = $storage->createText('', self::EXAMPLE_USER_ID);
        $textId2 = $storage->createText(self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $text = $storage->getFirstText([$textId1, $textId2]);

        $this->assertEquals(self::EXAMPLE_TEXT_2, $text);
    }
}
