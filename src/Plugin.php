<?php

declare(strict_types=1);

namespace Bigins\ScriptorMarkdownFeed;

use Scriptor\Boot\Plugin\Plugin as ScriptorPlugin;
use Scriptor\Boot\Plugin\PluginContext;

/**
 * Plugin manifest entry. Exists so Scriptor's composer-based discovery
 * (type: scriptor-plugin) lists the package and the editor's Plugins
 * module shows it.
 *
 * `register()` is intentionally a no-op: a feed is not page-shaped, so
 * it is not wired through a PageResolving/RouteNotFound listener.
 * Activation is one explicit guard line in the theme's `_ext.php`,
 * which runs {@see Feed::handle()} ahead of the theme build. See the
 * README. Same activation model as scriptor-simple-router.
 */
final class Plugin implements ScriptorPlugin
{
    public function name(): string
    {
        return 'bigins/scriptor-markdown-feed';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function register(PluginContext $context): void
    {
        // No-op by design; activation is the _ext.php guard line.
    }
}
