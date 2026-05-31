<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * Scans a track directory for markdown files and turns each into an
 * {@see Entry}. Pure filesystem read; no request or output concerns,
 * so it unit-tests against a fixtures directory.
 *
 * Rules:
 *   - `_index.md` is the track landing page, never a feed entry. Skipped.
 *   - Dotfiles and non-`.md` files are ignored.
 *   - `title` comes from frontmatter; a file without one falls back to
 *     its slug so a half-written entry still appears rather than vanishing.
 *   - `date:` frontmatter is the sort key (parsed via strtotime). When
 *     absent or unparseable, the file mtime stands in.
 *   - Entries sort newest-first; ties break on title ascending so the
 *     order is stable across requests.
 */
final class EntryCollector
{
    /**
     * @return list<Entry>
     */
    public static function collect(string $trackDir, int $max): array
    {
        if ($max < 1 || ! is_dir($trackDir)) {
            return [];
        }

        $entries = [];
        foreach (self::markdownFiles($trackDir) as $file) {
            $entries[] = self::toEntry($file);
        }

        usort($entries, static function (Entry $a, Entry $b): int {
            if ($a->timestamp !== $b->timestamp) {
                return $b->timestamp <=> $a->timestamp;
            }
            return strcasecmp($a->title, $b->title);
        });

        return array_slice($entries, 0, $max);
    }

    /**
     * @return list<string>
     */
    private static function markdownFiles(string $trackDir): array
    {
        $out = [];
        $handle = @scandir($trackDir);
        if ($handle === false) {
            return [];
        }
        foreach ($handle as $name) {
            if ($name === '' || $name[0] === '.') {
                continue;
            }
            if ($name === '_index.md' || ! str_ends_with($name, '.md')) {
                continue;
            }
            $path = $trackDir . '/' . $name;
            if (is_file($path)) {
                $out[] = $path;
            }
        }
        return $out;
    }

    private static function toEntry(string $file): Entry
    {
        $raw  = (string) @file_get_contents($file);
        $read = Frontmatter::read($raw);
        $fm   = $read['frontmatter'];

        $slug  = basename($file, '.md');
        $title = isset($fm['title']) && $fm['title'] !== '' ? $fm['title'] : $slug;

        $timestamp = self::resolveTimestamp($fm['date'] ?? '', $file);

        $summary = $fm['summary'] ?? '';
        if ($summary === '') {
            $summary = self::excerpt($read['body']);
        }

        return new Entry($slug, $title, $timestamp, $summary);
    }

    private static function resolveTimestamp(string $date, string $file): int
    {
        if ($date !== '') {
            $parsed = strtotime($date);
            if ($parsed !== false) {
                return $parsed;
            }
        }
        $mtime = @filemtime($file);
        return $mtime !== false ? $mtime : 0;
    }

    private static function excerpt(string $markdown, int $maxChars = 240): string
    {
        $text = strip_tags($markdown);
        // Strip the markdown markup that reads as noise in a plain-text
        // teaser: leading heading hashes and blockquote/list markers at
        // line starts, plus inline emphasis, code, and link syntax. The
        // summary is a fallback for entries without an explicit
        // `summary:`; a richer feed sets that frontmatter instead.
        $text = preg_replace('/^\s{0,3}#{1,6}\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^\s{0,3}[>*+-]\s+/m', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text) ?? $text; // [text](url) -> text
        $text = str_replace(['*', '_', '`'], '', $text);
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        if (strlen($text) <= $maxChars) {
            return $text;
        }
        return rtrim(substr($text, 0, $maxChars)) . '…';
    }
}
