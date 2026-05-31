# scriptor-markdown-feed

Atom 1.0 (and RSS 2.0) feeds generated on every request from a
[scriptor-markdown-pages](https://github.com/bigin/scriptor-markdown-pages)
content track, for [Scriptor](https://scriptor-cms.dev).

Point it at a directory of dated markdown files (a `news/`, `blog/`, or
`releases/` track) and it serves `/news/feed.xml` straight from those
files: no build step, no cache, no database. Add an entry, push, and the
next subscriber poll sees it.

## Install

This package is not on Packagist, so tell Composer where to find it with
a one-time `repositories` entry, then require it:

```bash
composer config repositories.scriptor-markdown-feed \
  vcs https://github.com/bigin/scriptor-markdown-feed
composer require bigins/scriptor-markdown-feed:^0.1
```

The first command adds a VCS repository to your `composer.json`; without
it `composer require` reports *"Could not find a version of package …"*.
If you install into Scriptor itself, its `composer.json` already ships a
`repositories` block covering `bigins/*` plugins, so the first command is
not needed there and a plain `composer require bigins/scriptor-markdown-feed`
works.

In Docker, add it to the `SCRIPTOR_PLUGINS` build arg like any other
plugin (see Scriptor's install docs).

## Activate

A feed is not page-shaped, so the plugin does not hook the page resolver.
Instead it runs ahead of the theme from one guard line at the top of your
theme's `_ext.php`, before the theme is built:

```php
<?php
use Scriptor\Boot\App;

require_once __DIR__ . '/vendor/autoload.php';

// Serve a feed before anything else if the URL matches one.
if (\Bigins\ScriptorMarkdownFeed\Feed::handle($config)) {
    return;
}

// ... normal theme bootstrap below ...
```

`Feed::handle($config)` returns `true` only when the request path matches
a configured feed; it emits the XML and the caller returns. Every other
request returns `false` and the normal page flow runs untouched. Same
activation model as
[scriptor-simple-router](https://github.com/bigin/scriptor-simple-router)'s
`Router::handle()`.

## Configure

Under `plugins.markdown_feed` in `data/settings/custom.scriptor-config.php`:

```php
return [
    'plugins' => [
        'markdown_feed' => [
            // Optional. Defaults to plugins.markdown_pages.content_root,
            // so a site already running scriptor-markdown-pages does not
            // repeat the path.
            'content_root' => '/var/www/scriptor/themes/info/content',

            'feeds' => [
                [
                    'track' => 'news',            // subdirectory of content_root
                    'path'  => '/news/feed.xml',  // URL the feed answers on
                    'title' => 'Scriptor News',   // channel title
                    'max'   => 20,                // newest N entries (default 20)
                    'format'=> 'atom',            // 'atom' (default) or 'rss'
                ],
            ],
        ],
    ],
];
```

| Key | Default | Effect |
|---|---|---|
| `content_root` | `plugins.markdown_pages.content_root` | Where the markdown tracks live. |
| `feeds[].track` | — (required) | Subdirectory of `content_root` to scan. |
| `feeds[].path` | — (required) | Exact request path the feed answers on. |
| `feeds[].title` | the track name | Channel `<title>`. |
| `feeds[].max` | `20` | Cap on entries, newest first. |
| `feeds[].format` | `atom` | `atom` (Atom 1.0) or `rss` (RSS 2.0). |

Multiple feeds are allowed (a `news` Atom feed and a `blog` feed side by
side). A feed entry missing `track` or `path` is dropped rather than
fatalling the request; a matched path with no `content_root` configured
yields to the normal 404 flow.

## How entries are built

Each `.md` file in the track directory becomes one entry:

- **`_index.md` is skipped** (it is the track landing page, not an entry).
- **Slug** is the filename without `.md`. The entry URL is
  `<siteUrl>/<track>/<slug>/`, matching how scriptor-markdown-pages
  resolves the same file. Keep filenames within `[a-z0-9_-]`: when the
  feed is paired with scriptor-markdown-pages, that plugin sanitises
  every URL segment to `[a-z0-9_-]`, so a dot (or other character) in
  the filename produces a feed link that resolves to a 404. Use
  `2026-05-22-release-v0-1-7.md`, not `…v0.1.7.md`.
- **Title** comes from `title:` frontmatter, falling back to the slug.
- **Sort** is by `date:` frontmatter, newest first; ties break on title.
  A file without a parseable `date:` falls back to its mtime, so an
  un-dated draft does not jump to the top on every edit.
- **Summary** comes from `summary:` frontmatter. Without it, the plugin
  derives a 240-character teaser from the body with markup stripped.

A typical entry:

```markdown
---
title: "Developer Guide is content-complete"
date: 2026-05-25
summary: "All four layers of the Developer Guide are now live."
---

# Developer Guide is content-complete

Body markdown…
```

## Security

- Titles, summaries, and URLs are escaped with
  `htmlspecialchars(…, ENT_XML1 | ENT_QUOTES)`, so feed content cannot
  break out of the XML.
- The plugin only ever reads files directly under the configured track
  directory (no recursion, no `..` traversal): the `track` config value
  selects the directory, never the request.
- Atom dates are ISO 8601 (UTC `Z`); RSS dates are RFC 822 (`DATE_RSS`).
  The two are never mixed within one feed.

## Development

```bash
composer install
composer test   # or: vendor/bin/phpunit
```

The suite covers frontmatter parsing, config + fallback resolution,
entry collection (sort order, `_index` skip, summary fallbacks), and both
serialisers (well-formed XML via `DOMDocument`, escaping, date formats).

## License

MIT. See [LICENSE](LICENSE).
