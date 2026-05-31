<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\Entry;
use Bigins\ScriptorMarkdownFeed\Rss2Writer;
use PHPUnit\Framework\TestCase;

final class Rss2WriterTest extends TestCase
{
    private const NOW = 1748000000;

    public function testProducesWellFormedRss(): void
    {
        $xml = Rss2Writer::render(
            'Scriptor News',
            'https://scriptor-cms.dev',
            'news',
            'https://scriptor-cms.dev/news/feed.rss',
            [new Entry('hello', 'Hello', 1748131200, 'Summary.')],
            self::NOW,
        );

        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($xml));
        self::assertStringContainsString('<rss version="2.0">', $xml);
        self::assertStringContainsString('<channel>', $xml);
        self::assertStringContainsString('<guid isPermaLink="true">https://scriptor-cms.dev/news/hello/</guid>', $xml);
    }

    public function testUsesRfc822Dates(): void
    {
        $xml = Rss2Writer::render(
            'News',
            'https://x.dev',
            'news',
            'https://x.dev/news/feed.rss',
            [new Entry('hello', 'Hello', 1748131200, 'x')],
            self::NOW,
        );

        self::assertStringContainsString('<pubDate>' . gmdate(\DATE_RSS, 1748131200) . '</pubDate>', $xml);
    }

    public function testSpecialCharactersAreEscaped(): void
    {
        $xml = Rss2Writer::render(
            'News',
            'https://x.dev',
            'news',
            'https://x.dev/news/feed.rss',
            [new Entry('s', 'A & B <x>', 1748131200, 'y & z')],
            self::NOW,
        );

        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($xml));
        self::assertStringNotContainsString('<x>', $xml);
    }
}
