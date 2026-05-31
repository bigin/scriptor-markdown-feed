<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * A single configured feed: which content track it draws from, the URL
 * it answers on, its channel title, how many entries it carries, and
 * which serialisation format to emit.
 */
final readonly class FeedDefinition
{
    public const FORMAT_ATOM = 'atom';
    public const FORMAT_RSS  = 'rss';

    public function __construct(
        public string $track,
        public string $path,
        public string $title,
        public int $max = 20,
        public string $format = self::FORMAT_ATOM,
    ) {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $track  = trim((string) ($raw['track'] ?? ''));
        $path   = trim((string) ($raw['path'] ?? ''));
        $title  = trim((string) ($raw['title'] ?? $track));
        $max    = (int) ($raw['max'] ?? 20);
        $format = strtolower(trim((string) ($raw['format'] ?? self::FORMAT_ATOM)));

        if ($format !== self::FORMAT_RSS) {
            $format = self::FORMAT_ATOM;
        }
        if ($max < 1) {
            $max = 20;
        }

        return new self($track, $path, $title, $max, $format);
    }

    public function isValid(): bool
    {
        return $this->track !== '' && $this->path !== '';
    }
}
