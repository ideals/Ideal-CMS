<?php


class CrawlerTest extends PHPUnit_Framework_TestCase
{
    public function testCaseAbsoluteString()
    {
        $crawler = new \Sitemap\Crawler();

        $result = $crawler->mock('getAbsoluteUrl', ["'?page=2'", "'http://example.com/test.html'"]);
        $this->assertEquals('http://example.com/test.html?page=2', $result);

        $result = $crawler->mock('getAbsoluteUrl', ["'simple.html'", "'http://example.com/test.html'"]);
        $this->assertEquals('http://example.com/simple.html', $result);

        $result = $crawler->mock('getAbsoluteUrl', ["'/simple.html'", "'http://example.com/lvl/test.html'"]);
        $this->assertEquals('http://example.com/simple.html', $result);

        $result = $crawler->mock('getAbsoluteUrl', ["'simple.html'", "'http://example.com/lvl/test.html'"]);
        $this->assertEquals('http://example.com/lvl/simple.html', $result);

        $result = $crawler->mock(
            'getAbsoluteUrl',
            ["'/about/news/?year=2009&amp;PAGEN_1=2'", "'http://example.com/lvl/test.html'"],
        );
        $this->assertEquals('http://example.com/about/news/?year=2009&PAGEN_1=2', $result);

        //$result = $crawler->mock('getUrl', array("'http://example/test.txt'", "'http://example.com/lvl/test.html'"));
        //$this->assertEquals('this is', $result);
    }

    public function testCasecutExcessGet()
    {
        $crawler = new \Sitemap\Crawler();

        $crawler->evalMe('$this->config[\'disallow_key\'] = array(\'page\');');

        $result = $crawler->mock('cutExcessGet', ["'http://example.com/about/news/?year=2009&PAGEN_1=2'"]);
        $this->assertEquals('http://example.com/about/news/?year=2009&PAGEN_1=2', $result);
    }

    public function testCaseParseLinks()
    {
        $crawler = new \Sitemap\Crawler();

        $content = '<a href="/about/news/?year=2009&amp;PAGEN_1=2">Конец</a>';

        $result = $crawler->mock('parseLinks', ["'{$content}'"]);
        $this->assertEquals('/about/news/?year=2009&amp;PAGEN_1=2', $result[0]);
    }
    public static function setUpBeforeClass()
    {
        include_once '../Crawler.php';
    }
}
