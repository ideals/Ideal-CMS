<?php


class CrawlerTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        include_once '../Crawler.php';
    }

    public function testCaseAbsoluteString()
    {
        $crawler = new \Sitemap\Crawler();

        $result = $crawler->mock('getAbsoluteUrl', array("'?page=2'", "'http://example.com/test.html'"));
        $this->assertEquals('http://example.com/test.html?page=2', $result);

        $result = $crawler->mock('getAbsoluteUrl', array("'simple.html'", "'http://example.com/test.html'"));
        $this->assertEquals('http://example.com/simple.html', $result);

        $result = $crawler->mock('getAbsoluteUrl', array("'/simple.html'", "'http://example.com/lvl/test.html'"));
        $this->assertEquals('http://example.com/simple.html', $result);

        $result = $crawler->mock('getAbsoluteUrl', array("'simple.html'", "'http://example.com/lvl/test.html'"));
        $this->assertEquals('http://example.com/lvl/simple.html', $result);

        $result = $crawler->mock('getUrl', array("'http://example/test.txt'", "'http://example.com/lvl/test.html'"));
        $this->assertEquals('this is', $result);
    }

    /**
     * @expectedException Exception
     */
    public function testCaseAbsoluteUrlException()
    {
        $crawler = new \Sitemap\Crawler();

        $result = $crawler->mock('getAbsoluteUrl', array("' simple.html '", "'http://example.com/lvl/test.html'"));
    }
}
