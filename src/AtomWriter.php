<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * Serialises a feed as Atom 1.0 (RFC 4287). Atom is the default because
 * its schema is tighter than RSS 2.0 (mandatory id/updated/title) and
 * its dates are unambiguous ISO 8601.
 *
 * Pure string builder: feed in, XML out. No headers, no echo, no exit.
 */
final class AtomWriter
{
    public const CONTENT_TYPE = 'application/atom+xml; charset=utf-8';

    /**
     * @param list<Entry> $entries
     */
    public static function render(
        string $feedTitle,
        string $siteUrl,
        string $track,
        string $selfUrl,
        array $entries,
        int $now,
    ): string {
        $siteUrl  = rtrim($siteUrl, '/');
        $trackUrl = $siteUrl . '/' . trim($track, '/') . '/';
        $updated  = $entries === [] ? $now : $entries[0]->timestamp;

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= sprintf("  <title>%s</title>\n", self::esc($feedTitle));
        $xml .= sprintf("  <link href=\"%s\" rel=\"self\"/>\n", self::esc($selfUrl));
        $xml .= sprintf("  <link href=\"%s\"/>\n", self::esc($trackUrl));
        $xml .= sprintf("  <id>%s</id>\n", self::esc($selfUrl));
        $xml .= sprintf("  <updated>%s</updated>\n", self::date($updated));

        foreach ($entries as $entry) {
            $entryUrl = $trackUrl . $entry->slug . '/';
            $xml .= "  <entry>\n";
            $xml .= sprintf("    <title>%s</title>\n", self::esc($entry->title));
            $xml .= sprintf("    <link href=\"%s\"/>\n", self::esc($entryUrl));
            $xml .= sprintf("    <id>%s</id>\n", self::esc($entryUrl));
            $xml .= sprintf("    <updated>%s</updated>\n", self::date($entry->timestamp));
            if ($entry->summary !== '') {
                $xml .= sprintf("    <summary>%s</summary>\n", self::esc($entry->summary));
            }
            $xml .= "  </entry>\n";
        }

        $xml .= '</feed>' . "\n";
        return $xml;
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function date(int $ts): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }
}
