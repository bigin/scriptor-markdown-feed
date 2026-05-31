<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * Serialises a feed as RSS 2.0 for readers that do not speak Atom.
 * Note the RSS-specific date format (RFC 822 via DATE_RSS) versus
 * Atom's ISO 8601: mixing the two silently breaks strict parsers.
 *
 * Pure string builder: feed in, XML out. No headers, no echo, no exit.
 */
final class Rss2Writer
{
    public const CONTENT_TYPE = 'application/rss+xml; charset=utf-8';

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
        $built    = $entries === [] ? $now : $entries[0]->timestamp;

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= sprintf("    <title>%s</title>\n", self::esc($feedTitle));
        $xml .= sprintf("    <link>%s</link>\n", self::esc($trackUrl));
        $xml .= sprintf("    <description>%s</description>\n", self::esc($feedTitle));
        $xml .= sprintf("    <lastBuildDate>%s</lastBuildDate>\n", self::date($built));

        foreach ($entries as $entry) {
            $entryUrl = $trackUrl . $entry->slug . '/';
            $xml .= "    <item>\n";
            $xml .= sprintf("      <title>%s</title>\n", self::esc($entry->title));
            $xml .= sprintf("      <link>%s</link>\n", self::esc($entryUrl));
            $xml .= sprintf("      <guid isPermaLink=\"true\">%s</guid>\n", self::esc($entryUrl));
            $xml .= sprintf("      <pubDate>%s</pubDate>\n", self::date($entry->timestamp));
            if ($entry->summary !== '') {
                $xml .= sprintf("      <description>%s</description>\n", self::esc($entry->summary));
            }
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= '</rss>' . "\n";
        return $xml;
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function date(int $ts): string
    {
        return gmdate(\DATE_RSS, $ts);
    }
}
