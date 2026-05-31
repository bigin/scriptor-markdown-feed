<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\FeedDefinition;
use PHPUnit\Framework\TestCase;

final class FeedDefinitionTest extends TestCase
{
    public function testDefaultsAtomAndMax20(): void
    {
        $def = FeedDefinition::fromArray(['track' => 'news', 'path' => '/news/feed.xml']);

        self::assertSame(FeedDefinition::FORMAT_ATOM, $def->format);
        self::assertSame(20, $def->max);
        self::assertSame('news', $def->title, 'title falls back to track');
    }

    public function testUnknownFormatFallsBackToAtom(): void
    {
        $def = FeedDefinition::fromArray(['track' => 'n', 'path' => '/p', 'format' => 'json']);
        self::assertSame(FeedDefinition::FORMAT_ATOM, $def->format);
    }

    public function testRssFormatHonoured(): void
    {
        $def = FeedDefinition::fromArray(['track' => 'n', 'path' => '/p', 'format' => 'RSS']);
        self::assertSame(FeedDefinition::FORMAT_RSS, $def->format);
    }

    public function testNonPositiveMaxResetsToDefault(): void
    {
        $def = FeedDefinition::fromArray(['track' => 'n', 'path' => '/p', 'max' => 0]);
        self::assertSame(20, $def->max);
    }

    public function testValidity(): void
    {
        self::assertTrue(FeedDefinition::fromArray(['track' => 'n', 'path' => '/p'])->isValid());
        self::assertFalse(FeedDefinition::fromArray(['track' => '', 'path' => '/p'])->isValid());
        self::assertFalse(FeedDefinition::fromArray(['track' => 'n', 'path' => ''])->isValid());
    }
}
