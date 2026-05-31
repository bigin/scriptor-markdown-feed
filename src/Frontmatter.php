<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * Reads the small YAML-ish frontmatter block at the top of a markdown
 * file: `key: value` pairs between two `---` fences, with optional
 * double-quoted values. Deliberately not a full YAML parser; it handles
 * the flat keys a news/blog entry actually carries (title, date,
 * summary) and ignores structural YAML.
 *
 * Kept byte-compatible with scriptor-markdown-pages' FrontmatterReader
 * so an entry written for one reads the same in the other.
 */
final class Frontmatter
{
    /**
     * @return array{frontmatter: array<string, string>, body: string}
     */
    public static function read(string $raw): array
    {
        $normalised = str_replace(["\r\n", "\r"], "\n", $raw);

        if (! str_starts_with($normalised, "---\n")) {
            return ['frontmatter' => [], 'body' => $normalised];
        }

        $end = strpos($normalised, "\n---", 4);
        if ($end === false) {
            return ['frontmatter' => [], 'body' => $normalised];
        }

        $block = substr($normalised, 4, $end - 4);
        $body  = ltrim(substr($normalised, $end + 4), "\n");

        return [
            'frontmatter' => self::parseBlock($block),
            'body'        => $body,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function parseBlock(string $block): array
    {
        $out = [];
        foreach (explode("\n", $block) as $line) {
            if (trim($line) === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (strlen($value) >= 2 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            }
            $value = trim($value);
            if ($key !== '') {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}
