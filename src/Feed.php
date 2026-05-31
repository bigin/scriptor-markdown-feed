<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * The runtime entry point, called from a theme's `_ext.php` as a single
 * guard line, before the theme is built:
 *
 *     if (\Bigins\ScriptorMarkdownFeed\Feed::handle($config)) return;
 *
 * `handle()` returns true only when the request path matches a
 * configured feed; it then emits the XML and the caller returns. Any
 * other request returns false and the normal page-resolution flow runs
 * untouched. Running ahead of the theme keeps a feed hit off the page
 * resolver and the markdown-pages plugin entirely.
 *
 * This mirrors scriptor-simple-router's `Router::handle()` shape: the
 * plugin is discovered via composer (type scriptor-plugin), but a feed
 * is not page-shaped, so activation is one explicit line rather than a
 * PageResolving listener.
 */
final class Feed
{
    /**
     * @param array<string, mixed> $config the full Scriptor config array
     */
    public static function handle(array $config): bool
    {
        $feedConfig = FeedConfig::fromConfig($config);
        if ($feedConfig->feeds === []) {
            return false;
        }

        $def = $feedConfig->matchPath(self::requestPath());
        if ($def === null) {
            return false;
        }

        // Path matched a feed but no content root is configured: there
        // is nothing to serve. Returning false lets the normal flow
        // produce its 404 rather than emitting an empty feed.
        if ($feedConfig->contentRoot === '') {
            return false;
        }

        $entries = EntryCollector::collect(
            $feedConfig->contentRoot . '/' . trim($def->track, '/'),
            $def->max,
        );

        $siteUrl = self::detectSiteUrl();
        $selfUrl = rtrim($siteUrl, '/') . '/' . trim($def->path, '/');

        [$body, $contentType] = self::render($def, $siteUrl, $selfUrl, $entries, time());

        if (! headers_sent()) {
            header('Content-Type: ' . $contentType);
        }
        echo $body;
        return true;
    }

    /**
     * Pure serialisation step: pick the writer for the feed's format and
     * return `[body, contentType]`. Split out from {@see handle()} so it
     * unit-tests without request globals or output.
     *
     * @param list<Entry> $entries
     * @return array{0: string, 1: string}
     */
    public static function render(
        FeedDefinition $def,
        string $siteUrl,
        string $selfUrl,
        array $entries,
        int $now,
    ): array {
        if ($def->format === FeedDefinition::FORMAT_RSS) {
            return [
                Rss2Writer::render($def->title, $siteUrl, $def->track, $selfUrl, $entries, $now),
                Rss2Writer::CONTENT_TYPE,
            ];
        }
        return [
            AtomWriter::render($def->title, $siteUrl, $def->track, $selfUrl, $entries, $now),
            AtomWriter::CONTENT_TYPE,
        ];
    }

    private static function requestPath(): string
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, \PHP_URL_PATH);
        return is_string($path) ? $path : '/';
    }

    private static function detectSiteUrl(): string
    {
        $scheme = (($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
}
