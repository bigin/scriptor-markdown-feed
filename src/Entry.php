<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

/**
 * One feed entry, distilled from a single markdown file's frontmatter.
 *
 * `$slug` is the filename without its `.md` extension; the entry's
 * public URL is `<siteUrl>/<track>/<slug>/`, matching how
 * scriptor-markdown-pages resolves the same file. `$timestamp` is the
 * sort key: the parsed `date:` frontmatter, or the file mtime when
 * `date:` is absent or unparseable.
 */
final readonly class Entry
{
    public function __construct(
        public string $slug,
        public string $title,
        public int $timestamp,
        public string $summary,
    ) {}
}
