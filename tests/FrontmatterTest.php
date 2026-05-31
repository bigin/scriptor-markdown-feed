<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\Frontmatter;
use PHPUnit\Framework\TestCase;

final class FrontmatterTest extends TestCase
{
    public function testParsesKeysAndStripsQuotes(): void
    {
        $raw = "---\ntitle: \"Hello World\"\ndate: 2026-05-25\n---\nBody here.";
        $read = Frontmatter::read($raw);

        self::assertSame('Hello World', $read['frontmatter']['title']);
        self::assertSame('2026-05-25', $read['frontmatter']['date']);
        self::assertSame('Body here.', $read['body']);
    }

    public function testNoFrontmatterReturnsWholeBody(): void
    {
        $raw  = "# Just a heading\n\nNo fences.";
        $read = Frontmatter::read($raw);

        self::assertSame([], $read['frontmatter']);
        self::assertSame($raw, $read['body']);
    }

    public function testCarriageReturnsAreNormalised(): void
    {
        $raw  = "---\r\ntitle: CRLF\r\n---\r\nBody";
        $read = Frontmatter::read($raw);

        self::assertSame('CRLF', $read['frontmatter']['title']);
        self::assertSame('Body', $read['body']);
    }

    public function testUnterminatedFenceIsNotTreatedAsFrontmatter(): void
    {
        $raw  = "---\ntitle: dangling\nno closing fence";
        $read = Frontmatter::read($raw);

        self::assertSame([], $read['frontmatter']);
    }
}
