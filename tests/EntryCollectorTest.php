<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\EntryCollector;
use PHPUnit\Framework\TestCase;

final class EntryCollectorTest extends TestCase
{
    private const TRACK = __DIR__ . '/fixtures/news';

    /**
     * The `no-frontmatter` fixture carries no `date:`, so it sorts by
     * mtime. Git restores files with a checkout-time mtime, which would
     * float that entry to "now" and make ordering assertions flaky. Pin
     * it to a fixed past time so the dated entries always sort ahead.
     */
    protected function setUp(): void
    {
        touch(self::TRACK . '/no-frontmatter.md', strtotime('2026-01-01'));
    }

    public function testSkipsIndexAndNonMarkdown(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 20);
        $slugs   = array_map(static fn($e) => $e->slug, $entries);

        self::assertNotContains('_index', $slugs);
        self::assertContains('2026-05-25-newer-entry', $slugs);
        self::assertContains('2026-05-22-older-entry', $slugs);
        self::assertContains('no-frontmatter', $slugs);
    }

    public function testSortsNewestFirstByDateFrontmatter(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 20);

        $newerIdx = $this->indexOfSlug($entries, '2026-05-25-newer-entry');
        $olderIdx = $this->indexOfSlug($entries, '2026-05-22-older-entry');

        self::assertLessThan($olderIdx, $newerIdx, 'newer dated entry must sort before older');
    }

    public function testTitleFallsBackToSlugWhenMissing(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 20);
        $plain   = $this->entryBySlug($entries, 'no-frontmatter');

        self::assertSame('no-frontmatter', $plain->title);
    }

    public function testSummaryUsedFromFrontmatterVerbatim(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 20);
        $newer   = $this->entryBySlug($entries, '2026-05-25-newer-entry');

        self::assertSame('The newest dated entry, should sort first.', $newer->summary);
    }

    public function testSummaryDerivedFromBodyWhenAbsent(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 20);
        $plain   = $this->entryBySlug($entries, 'no-frontmatter');

        self::assertStringContainsString('No frontmatter at all', $plain->summary);
        self::assertStringNotContainsString('#', $plain->summary, 'markdown markup should be stripped');
    }

    public function testMaxLimitsCount(): void
    {
        $entries = EntryCollector::collect(self::TRACK, 1);

        self::assertCount(1, $entries);
        self::assertSame('2026-05-25-newer-entry', $entries[0]->slug, 'cap keeps the newest');
    }

    public function testMissingDirectoryYieldsEmpty(): void
    {
        self::assertSame([], EntryCollector::collect('/no/such/dir', 20));
    }

    public function testZeroMaxYieldsEmpty(): void
    {
        self::assertSame([], EntryCollector::collect(self::TRACK, 0));
    }

    /**
     * @param list<\Bigins\ScriptorMarkdownFeed\Entry> $entries
     */
    private function indexOfSlug(array $entries, string $slug): int
    {
        foreach ($entries as $i => $entry) {
            if ($entry->slug === $slug) {
                return $i;
            }
        }
        self::fail("slug not found: {$slug}");
    }

    /**
     * @param list<\Bigins\ScriptorMarkdownFeed\Entry> $entries
     */
    private function entryBySlug(array $entries, string $slug): \Bigins\ScriptorMarkdownFeed\Entry
    {
        return $entries[$this->indexOfSlug($entries, $slug)];
    }
}
