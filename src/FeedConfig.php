<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * Parses the plugin's config block out of the full Scriptor config
 * array. Reads `plugins.markdown_feed`:
 *
 *   content_root  string  Absolute path to the markdown tree. Optional;
 *                         falls back to `plugins.markdown_pages.content_root`
 *                         so a site running scriptor-markdown-pages does
 *                         not repeat the path. Empty when neither is set.
 *   feeds         list    One entry per feed (see {@see FeedDefinition}).
 *
 * Invalid feed entries (missing track or path) are dropped, so a typo
 * disables one feed rather than fatalling the request.
 */
final readonly class FeedConfig
{
    /**
     * @param list<FeedDefinition> $feeds
     */
    public function __construct(
        public string $contentRoot,
        public array $feeds,
    ) {}

    /**
     * @param array<string, mixed> $config the full Scriptor config array
     */
    public static function fromConfig(array $config): self
    {
        $plugins = self::asArray($config['plugins'] ?? []);
        $own     = self::asArray($plugins['markdown_feed'] ?? []);
        $pages   = self::asArray($plugins['markdown_pages'] ?? []);

        $contentRoot = trim((string) (
            $own['content_root']
            ?? $pages['content_root']
            ?? ''
        ));
        $contentRoot = rtrim($contentRoot, '/');

        $feeds = [];
        foreach (self::asArray($own['feeds'] ?? []) as $rawFeed) {
            if (! is_array($rawFeed)) {
                continue;
            }
            $def = FeedDefinition::fromArray($rawFeed);
            if ($def->isValid()) {
                $feeds[] = $def;
            }
        }

        return new self($contentRoot, $feeds);
    }

    /**
     * Find the feed whose path matches the request path, or null. The
     * comparison is exact after trimming a trailing slash from both
     * sides, so `/news/feed.xml` and `/news/feed.xml/` both match a
     * `path` of `/news/feed.xml`.
     */
    public function matchPath(string $requestPath): ?FeedDefinition
    {
        $needle = '/' . trim($requestPath, '/');
        foreach ($this->feeds as $feed) {
            if ('/' . trim($feed->path, '/') === $needle) {
                return $feed;
            }
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|array<int, mixed>
     */
    private static function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
