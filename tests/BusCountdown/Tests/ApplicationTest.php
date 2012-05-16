<?php
namespace BusCountdown\Tests;

require_once __DIR__ . '/../../../vendor/Silex/silex.phar';

use Silex\WebTestCase;

class ApplicationTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../src/app.php';
    }

    public function testInitialPage()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals(1, count($crawler->filter('h1:contains("Bus Countdown")')));
    }
}