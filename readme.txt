=== Responsive Visibility — Show or Hide Blocks by Device, Custom Breakpoints & Conditions ===
Version: 1.2.0
Author: wowdevs
Author URI: https://wowdevs.com/
Contributors: wowdevs, bdkoder, hashibali
Donate link: https://buy.stripe.com/8x214f0XKf0cfop6a14wM02
Tags: block visibility, conditional blocks, visibility, responsive, gutenberg
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Hide or show any Gutenberg block by device — unlimited custom breakpoints, no CSS, no theme lock-in. Mobile, tablet, desktop and beyond.

== Description ==

**Responsive Visibility** is a lightweight **block visibility** plugin that lets you **hide or show any Gutenberg block by device** — straight from the editor, with no custom CSS and no theme dependency. Whether you want to **hide blocks on mobile**, show content only on desktop, or fine-tune everything in between, it takes a single toggle in the Inspector sidebar — the block disappears on exactly the screens you choose.

Unlike other block visibility plugins that lock you into three fixed device sizes, Responsive Visibility gives you **unlimited custom breakpoints** so you can build truly **responsive blocks** for any screen. Add, rename, and resize as many breakpoints as your design needs — widescreen, 4K, small phone, anything — Elementor-style, all managed from a clean settings page.

In a mobile-first world, controlling **responsive visibility** for every block is essential. Show a lead-generation form in the desktop sidebar but hide it on phones. Hide a heavy hero image on mobile to speed up load time. Reveal an extra call-to-action only on ultrawide screens. Responsive Visibility makes **conditional blocks** effortless — no shortcodes, no code, no theme edits.

For a detailed walkthrough, watch this tutorial:

https://youtu.be/g7My09gTghI

= How It Works (3 Steps) =

1. **Edit any block** in the Gutenberg editor and open the Responsive Visibility panel in the sidebar.
2. **Pick the devices** to hide it on — mobile, tablet, desktop, or any custom breakpoint you defined.
3. **Save.** The block is hidden on exactly those screens — no custom CSS, no theme edits, no shortcodes.

= Why Responsive Visibility? =

* **Unlimited custom breakpoints** — not just mobile/tablet/desktop. Define any number of named device ranges and the plugin generates the matching CSS media queries automatically.
* **100% cache-friendly** — visibility is handled with CSS classes and media queries, so every visitor gets the same HTML. Works perfectly with any caching plugin, and updates live as the screen resizes.
* **No theme lock-in** — integrates with any block theme or classic theme. No template edits, no dependencies.
* **Backward compatible, always** — blocks hidden with earlier versions keep working after every update. Your existing settings are never lost.
* **Built for Gutenberg** — controls live in the native block Inspector, right where you already edit.

= Key Features =

* **Hide or show blocks by device** (desktop, tablet, mobile, and any custom breakpoint)
* **Login-status visibility** — show a block to everyone, logged-in users only, or logged-out visitors only
* **Unlimited custom breakpoints** — add, rename, reorder, and set pixel widths from Settings → Responsive Visibility
* **Dynamic CSS generation** — pixel values come from your saved breakpoints, never hardcoded
* **Cache-safe responsive design** — CSS-based hiding that survives full-page caching
* **Per-block control** — settings are applied per block, not per page
* **Seamless Gutenberg integration** with native WordPress blocks and most third-party blocks
* **Improve page experience** by hiding unnecessary or heavy elements on smaller screens

= Ideal For =

* **Content creators** who want to hide large or secondary blocks on phones and tablets.
* **Designers** building responsive Gutenberg layouts without writing one-off CSS.
* **Site owners** tailoring what each device sees to boost engagement and speed.
* **Agencies and developers** who need simple, reliable block visibility on client sites.

= More Plugins by WowDevs =

* **[Sky Elementor Addons](https://wordpress.org/plugins/sky-elementor-addons/)** — A powerful toolkit of Elementor widgets, extensions, and ready-made templates to build stunning pages faster.
* **[Ultimate Spin Wheel](https://wordpress.org/plugins/ultimate-spin-wheel/)** — Gamified spin-to-win wheel that turns visitors into subscribers and sales with fun, interactive discounts.
* **[Blockish](https://wordpress.org/plugins/blockish/)** — A collection of beautiful, lightweight Gutenberg blocks to design rich layouts without the bloat.

== Installation ==

Getting started with **Responsive Visibility** is easy! Follow these steps to start controlling your blocks' visibility based on devices:

1. Upload the plugin files to the `/wp-content/plugins/responsive-visibility` directory, or install it directly from the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in your WordPress admin panel.
3. Edit any Gutenberg block and use the visibility controls in the Inspector sidebar to choose which devices it shows on.
4. (Optional) Go to **Settings → Responsive Visibility** to add, rename, or resize your own custom breakpoints.

== Frequently Asked Questions ==

= Does this plugin work with all Gutenberg blocks? =

Yes. Responsive Visibility adds its controls to every standard Gutenberg block and works with most third-party and dynamic blocks too. It's a block extension, so the visibility options appear automatically wherever you edit.

= How do I add a custom breakpoint? =

Go to **Settings → Responsive Visibility**. Add a new breakpoint, give it a name (for example "Widescreen"), and set its max-width in pixels. The plugin generates the matching CSS media query automatically, and the new breakpoint appears in every block's visibility controls. You can add as many as you like.

= Does it remove the block or hide it with CSS? =

It hides blocks with CSS classes and media queries — the block stays in the page HTML but is set to `display:none` on the device sizes you selected. This keeps the same markup for every visitor.

= Will it work with caching plugins? =

Yes. Because visibility is CSS-based, every visitor receives identical HTML, so it is fully compatible with full-page caching and CDNs. Visibility also updates live as the browser is resized.

= Will it slow down my site? =

No. The plugin outputs a single small inline `<style>` block in the page head and adds no extra HTTP requests or JavaScript on the front end. It stays lightweight by design.

= Does it work with block themes and the Site Editor (FSE)? =

Yes. Responsive Visibility works with block themes, classic themes, and Full Site Editing contexts.

= Will my existing hidden blocks keep working after I update? =

Yes. Backward compatibility is a core promise. Blocks hidden with older versions continue to work unchanged — legacy device settings are always honored, and your saved breakpoints are never regenerated or lost.

= Does it work with Elementor or other page builders? =

No. Responsive Visibility is built specifically for the WordPress block editor (Gutenberg) and does not control content rendered by Elementor or other page builders.

= Can I use it on a multisite network? =

Yes, the plugin works on standard WordPress multisite installations.

= Can I show a block only to logged-in or logged-out users? =

Yes. In the block's "Visibility Conditions" panel, choose who should see it — Everyone, Logged-in users only, or Logged-out visitors only. Unlike device hiding (which uses CSS), login-status blocks are removed on the server, so member-only content is never placed in the page source for guests.

= Does login-status visibility work with caching plugins? =

Yes, with one note: because login rules are evaluated on the server, your full-page cache must serve a different (or no) cache to logged-in users. Every major caching plugin does this by default. Device and breakpoint rules remain CSS-based and are unaffected by caching.

= How can I become a Contributor? =

If you want to contribute, go to our [Responsive Visibility GitHub Repository](https://github.com/bdkoder/responsive-visibility/) and see where you can help.

== External Services ==

This plugin connects to one external services under the conditions described below. No data is ever sent without a clear user action or explicit opt-in.

= 1. Usage Analytics / Data Insights (dashboard.wowdevs.com) =

**What it does:** Sends non-sensitive plugin usage data to help improve the plugin. This is part of the optional Data Insights program powered by the DCI SDK.

**When it connects:** **Only if you explicitly opt in** when prompted. No data is ever sent without your consent. You can opt out at any time from the Sky Addons dashboard.

**Data sent:** Plugin version, WordPress version, active theme, site language, and similar non-personal environment data. No passwords, user content, or personally identifiable information is transmitted.

**Service:** wowDevs Data Insights, operated by wowDevs.
Service URL: https://dashboard.wowdevs.com/
Privacy Policy: https://wowdevs.com/privacy-policy/
Terms of Service: https://wowdevs.com/terms-and-conditions/

== Screenshots ==

1. **Visibility Settings**: Per-block visibility controls in the Gutenberg Inspector sidebar.
2. **Mobile & Desktop Visibility**: A block shown on desktop but hidden on mobile.
3. **Custom Breakpoints**: The Settings → Responsive Visibility page for adding and editing unlimited breakpoints.
4. **Gutenberg Editor**: Plugin controls integrated directly into the block editor.

== Changelog ==

= 1.2.0 =
* Added: Login-status visibility — show any block to everyone, logged-in users only, or logged-out visitors only, from the new "Visibility Conditions" panel
* Added: Login rules are evaluated server-side, so member-only content is never exposed in the page source for guests
* Improved: Fully backward compatible — the new condition defaults to "Everyone", so existing blocks are unchanged

= 1.1.0 [21st April 2026] =
* Added: Custom breakpoints — configure any number of device breakpoints (mobile, tablet, desktop, widescreen, etc.) from Settings → Responsive Visibility (Thanks to prionkor)
* Added: Dynamic CSS generation from saved breakpoints — no more hardcoded pixel values
* Added: "Customize breakpoints" link inside the block Inspector panel
* Improved: Fully backward compatible — existing blocks with legacy hide attributes continue to work unchanged
* Requires: WordPress 6.2+ (uses WP_HTML_Tag_Processor)

= 1.0.6 [10th December 2025] =
* System improved

= 1.0.5 [26th July 2025] =
* System improved

= 1.0.4 [21th June 2025] =
* System improved

= 1.0.3 [9th March 2025] =
* System improved

= 1.0.2 [11th Sep 2024] =
* System improved

= 1.0.1 [11th Sep 2024] =
* System improved

= 1.0.0 =
* Initial Release

== Upgrade Notice ==

= 1.2.0 =
Adds login-status visibility (show blocks to logged-in or logged-out users). Fully backward compatible — existing blocks are unchanged. Safe to update.

= 1.1.0 =
Adds unlimited custom breakpoints and dynamic CSS generation. Fully backward compatible — existing hidden blocks keep working. Safe to update.
