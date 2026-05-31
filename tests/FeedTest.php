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
     * @return list<Entry>
     */
    private function entries(): array
    {
        return [new Entry('hello', 'Hello', 1748131200, 'Summary.')];
    }
}
