<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\AtomWriter;
use Bigins\ScriptorMarkdownFeed\Entry;
use Bigins\ScriptorMarkdownFeed\Feed;
use Bigins\ScriptorMarkdownFeed\FeedDefinition;
use Bigins\ScriptorMarkdownFeed\Rss2Writer;
use PHPUnit\Framework\TestCase;

final class FeedTest extends TestCase
{
    private const NOW = 1748000000;

    public function testRenderPicksAtomByDefault(): void
    {
        $def = new FeedDefinition('news', '/news/feed.xml', 'News');
        [$body, $type] = Feed::render($def, 'https://x.dev', 'https://x.dev/news/feed.xml', $this->entries(), self::NOW);

        self::assertSame(AtomWriter::CONTENT_TYPE, $type);
        self::assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $body);
    }

    public function testRenderPicksRssWhenConfigured(): void
    {
        $def = new FeedDefinition('news', '/news/feed.rss', 'News', 20, FeedDefinition::FORMAT_RSS);
        [$body, $type] = Feed::render($def, 'https://x.dev', 'https://x.dev/news/feed.rss', $this->entries(), self::NOW);

        self::assertSame(Rss2Writer::CONTENT_TYPE, $type);
        self::assertStringContainsString('<rss version="2.0">', $body);
    }

    /**
     * @param array<string,string> $server
     * @dataProvider schemeProvider
     */
    public function testDetectSchemeHonoursProxyHeaders(array $server, string $expected): void
    {
        $saved = $_SERVER;
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $method = new \ReflectionMethod(Feed::class, 'detectScheme');
        $method->setAccessible(true);

        try {
            self::assertSame($expected, $method->invoke(null));
        } finally {
            $_SERVER = $saved;
        }
    }

    /**
     * @return array<string,array{0:array<string,string>,1:string}>
     */
    public static function schemeProvider(): array
    {
        return [
            'no signal'            => [[], 'http'],
            'direct https'         => [['HTTPS' => 'on'], 'https'],
            'forwarded https'      => [['HTTP_X_FORWARDED_PROTO' => 'https'], 'https'],
            'forwarded https list' => [['HTTP_X_FORWARDED_PROTO' => 'https, http'], 'https'],
            'forwarded http'       => [['HTTP_X_FORWARDED_PROTO' => 'http'], 'http'],
        ];
    }

    /**
     * @return list<Entry>
     */
    private function entries(): array
    {
        return [new Entry('hello', 'Hello', 1748131200, 'Summary.')];
    }
}
