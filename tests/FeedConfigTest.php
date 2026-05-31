<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed\Tests;

use Bigins\ScriptorMarkdownFeed\FeedConfig;
use Bigins\ScriptorMarkdownFeed\FeedDefinition;
use PHPUnit\Framework\TestCase;

final class FeedConfigTest extends TestCase
{
    public function testParsesOwnConfigBlock(): void
    {
        $config = ['plugins' => ['markdown_feed' => [
            'content_root' => '/srv/content',
            'feeds' => [
                ['track' => 'news', 'path' => '/news/feed.xml', 'title' => 'News', 'max' => 10],
            ],
        ]]];

        $fc = FeedConfig::fromConfig($config);

        self::assertSame('/srv/content', $fc->contentRoot);
        self::assertCount(1, $fc->feeds);
        self::assertSame('news', $fc->feeds[0]->track);
        self::assertSame(10, $fc->feeds[0]->max);
    }

    public function testContentRootFallsBackToMarkdownPages(): void
    {
        $config = ['plugins' => [
            'markdown_pages' => ['content_root' => '/srv/pages-root'],
            'markdown_feed'  => ['feeds' => [
                ['track' => 'news', 'path' => '/news/feed.xml'],
            ]],
        ]];

        $fc = FeedConfig::fromConfig($config);

        self::assertSame('/srv/pages-root', $fc->contentRoot);
    }

    public function testOwnContentRootWinsOverFallback(): void
    {
        $config = ['plugins' => [
            'markdown_pages' => ['content_root' => '/srv/pages-root'],
            'markdown_feed'  => [
                'content_root' => '/srv/feed-root',
                'feeds' => [['track' => 'news', 'path' => '/news/feed.xml']],
            ],
        ]];

        self::assertSame('/srv/feed-root', FeedConfig::fromConfig($config)->contentRoot);
    }

    public function testTrailingSlashStrippedFromContentRoot(): void
    {
        $config = ['plugins' => ['markdown_feed' => [
            'content_root' => '/srv/content/',
            'feeds' => [['track' => 'news', 'path' => '/news/feed.xml']],
        ]]];

        self::assertSame('/srv/content', FeedConfig::fromConfig($config)->contentRoot);
    }

    public function testInvalidFeedEntriesAreDropped(): void
    {
        $config = ['plugins' => ['markdown_feed' => [
            'content_root' => '/srv/content',
            'feeds' => [
                ['track' => 'news', 'path' => '/news/feed.xml'],
                ['track' => '', 'path' => '/broken/feed.xml'], // no track
                ['track' => 'blog'],                            // no path
                'not-an-array',
            ],
        ]]];

        $fc = FeedConfig::fromConfig($config);

        self::assertCount(1, $fc->feeds);
        self::assertSame('news', $fc->feeds[0]->track);
    }

    public function testEmptyConfigYieldsNoFeeds(): void
    {
        self::assertSame([], FeedConfig::fromConfig([])->feeds);
        self::assertSame('', FeedConfig::fromConfig([])->contentRoot);
    }

    public function testMatchPathIgnoresTrailingSlash(): void
    {
        $fc = FeedConfig::fromConfig(['plugins' => ['markdown_feed' => [
            'content_root' => '/srv/content',
            'feeds' => [['track' => 'news', 'path' => '/news/feed.xml']],
        ]]]);

        self::assertInstanceOf(FeedDefinition::class, $fc->matchPath('/news/feed.xml'));
        self::assertInstanceOf(FeedDefinition::class, $fc->matchPath('/news/feed.xml/'));
        self::assertNull($fc->matchPath('/news/'));
        self::assertNull($fc->matchPath('/other/feed.xml'));
    }
}
