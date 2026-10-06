=== Gorres Blocks ===
Contributors: jgorres
Donate link: https://ko-fi.com/joerngorres/
Tags: blocks, block editor, hero, scroll, sticky
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A collection of lightweight blocks for the block editor that can be enabled individually.

== Description ==

Gorres Blocks is a small collection of blocks for the block editor. Every block can be switched on or off on its own, so only the blocks you actually use are loaded.

= Blocks =

* **Hero Teaser** — a screen-high image with a teaser box on top. While the visitor scrolls, the image stays in place and the box moves up across it. As soon as the box has left the screen, the image scrolls away too and the post follows. Set the start position, width and horizontal position of the box, the dimming and focal point of the image, and an offset for themes with a fixed header. Colours, border, shadow and padding of the box come from the usual block settings.

= Built to behave =

* **Lightweight.** No JavaScript library, no external requests, no tracking.
* **Only what you use.** A block that is switched off is not registered at all: no styles, no scripts, no entry in the block inserter.

== Installation ==

1. Install the plugin through the Plugins screen in WordPress, or upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to Settings > Gorres Blocks and switch off the blocks you do not need. All blocks are switched on by default.

== Frequently Asked Questions ==

= What happens to my content when I switch a block off? =

It stays on the front end unchanged. The editor shows the block as unsupported until you switch it on again.

= Does the plugin load anything from external servers? =

No. All code and styles ship with the plugin.

== Changelog ==

= 1.2.0 =
* New block: Hero Teaser.

= 1.1.0 =
* Settings screen to switch every block on or off.
* Blocks are registered from a single blocks manifest.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.2.0 =
New block: Hero Teaser.

= 1.1.0 =
Settings screen to switch blocks on or off.

= 1.0.0 =
First release.
