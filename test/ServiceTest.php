<?php

namespace AutowpTest\TextStorage;

use Zend\Mvc\Application;

use Autowp\TextStorage;

class SereviceTest extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_TEXT = 'Example text';
    const EXAMPLE_TEXT_2 = 'Example text 2';
    const EXAMPLE_TEXT_3 = 'Example text 3';
    const EXAMPLE_USER_ID = 1;

    /**
     * @return TextStorage\Service
     */
    private function getStorage()
    {
        $app = Application::init(require __DIR__ . '/_files/config/application.config.php');

        $serviceManager = $app->getServiceManager();

        return $serviceManager->get(TextStorage\Service::class);
    }

    public function testReadWrite()
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
            'user_id'  => self::EXAMPLE_USER_ID
        ], $revisionInfo);

        $storage->setText($textId, self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $userIds = $storage->getTextUserIds($textId);
        $this->assertEquals([self::EXAMPLE_USER_ID], $userIds);
    }

    public function testGetFirstText()
    {
        $storage = $this->getStorage();

        $textId1 = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textId2 = $storage->createText(self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $text = $storage->getFirstText([$textId1, $textId2]);
        $this->assertEquals(self::EXAMPLE_TEXT, $text);

        $text = $storage->getFirstText([$textId2, $textId1]);
        $this->assertEquals(self::EXAMPLE_TEXT_2, $text);
    }

    public function testSecondTextNotAffectsFirst()
    {
        $storage = $this->getStorage();

        $textId1 = $storage->createText(self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
        $textInfo1 = $storage->getTextInfo($textId1);

        $textId2 = $storage->createText(self::EXAMPLE_TEXT_2, self::EXAMPLE_USER_ID);

        $storage->setText($textId2, self::EXAMPLE_TEXT_3, self::EXAMPLE_USER_ID);

        $newTextInfo1 = $storage->getTextInfo($textId1);
        $this->assertEquals($textInfo1, $newTextInfo1);
    }

    /**
     * @expectedException \Autowp\TextStorage\Exception
     */
    public function testThrowExceptionOnUnexpectedOption()
    {
        new TextStorage\Service([
            'foo' => 'bar'
        ]);
    }

    public function testReturnNullOnEmptyIds()
    {
        $storage = $this->getStorage();

        $text = $storage->getFirstText([]);

        $this->assertNull($text);
    }

    public function testReturnNullWhenNothingFound()
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
     * @expectedException \Autowp\TextStorage\Exception
     */
    public function testThrowExceptionWhenSetUnexistentText()
    {
        $storage = $this->getStorage();

        $storage->setText(123456789, self::EXAMPLE_TEXT, self::EXAMPLE_USER_ID);
    }
}
