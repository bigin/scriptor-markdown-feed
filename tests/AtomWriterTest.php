<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\AtomWriter;
use Bigins\ScriptorMarkdownFeed\Entry;
use PHPUnit\Framework\TestCase;

final class AtomWriterTest extends TestCase
{
    private const NOW = 1748000000; // fixed for deterministic <updated>

    public function testProducesWellFormedAtom(): void
    {
        $entries = [
            new Entry('newer', 'Newer', 1748131200, 'Newest summary.'),
            new Entry('older', 'Older', 1747958400, 'Older summary.'),
        ];
        $xml = AtomWriter::render(
            'Scriptor News',
            'https://scriptor-cms.dev',
            'news',
            'https://scriptor-cms.dev/news/feed.xml',
            $entries,
            self::NOW,
        );

        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($xml), 'output must be valid XML');
        self::assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $xml);
        self::assertStringContainsString('<title>Scriptor News</title>', $xml);
    }

    public function testEntryUrlIsTrackPlusSlug(): void
    {
        $xml = AtomWriter::render(
            'News',
            'https://scriptor-cms.dev/',
            'news',
            'https://scriptor-cms.dev/news/feed.xml',
            [new Entry('hello-world', 'Hello', 1748131200, 'x')],
            self::NOW,
        );

        self::assertStringContainsString(
            '<link href="https://scriptor-cms.dev/news/hello-world/"/>',
            $xml,
        );
    }

    public function testUpdatedUsesNewestEntryTimestamp(): void
    {
        $xml = AtomWriter::render(
            'News',
            'https://scriptor-cms.dev',
            'news',
            'https://scriptor-cms.dev/news/feed.xml',
            [new Entry('newer', 'Newer', 1748131200, 'x')],
            self::NOW,
        );

        // Feed-level <updated> must equal the (only/newest) entry's date,
        // not $now, so the feed mtime reflects content not request time.
        self::assertStringContainsString('<updated>' . gmdate('Y-m-d\TH:i:s\Z', 1748131200) . '</updated>', $xml);
    }

    public function testEmptyFeedFallsBackToNowForUpdated(): void
    {
        $xml = AtomWriter::render('News', 'https://x.dev', 'news', 'https://x.dev/news/feed.xml', [], self::NOW);

        self::assertStringContainsString('<updated>' . gmdate('Y-m-d\TH:i:s\Z', self::NOW) . '</updated>', $xml);
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($xml));
    }

    public function testSpecialCharactersAreEscaped(): void
    {
        $xml = AtomWriter::render(
            'A & B <tag>',
            'https://x.dev',
            'news',
            'https://x.dev/news/feed.xml',
            [new Entry('s', 'Title with "quotes" & <angle>', 1748131200, 'sum & more')],
            self::NOW,
        );

        self::assertStringNotContainsString('<tag>', $xml);
        self::assertStringContainsString('A &amp; B', $xml);
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($xml), 'escaped output must still parse');
    }

    public function testSummaryOmittedWhenEmpty(): void
    {
        $xml = AtomWriter::render(
            'News',
            'https://x.dev',
            'news',
            'https://x.dev/news/feed.xml',
            [new Entry('s', 'No summary', 1748131200, '')],
            self::NOW,
        );

        self::assertStringNotContainsString('<summary>', $xml);
    }
}
