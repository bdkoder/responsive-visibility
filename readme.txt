=== Responsive Visibility — Show or Hide Blocks by Device, Custom Breakpoints & Conditions ===
Version: 1.2.1
Author: wowdevs
Author URI: https://wowdevs.com/
Contributors: wowdevs, bdkoder, hashibali
Donate link: https://buy.stripe.com/8x214f0XKf0cfop6a14wM02
Tags: block visibility, conditional blocks, visibility, responsive, gutenberg
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.2.1
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Show or hide any Gutenberg block by device, custom breakpoint, or condition — login, user role, post type & more. No CSS, no code.

== Description ==

**Responsive Visibility** adds **block visibility** controls to every Gutenberg block. You choose which screen sizes a block shows on, and the plugin hides it on the rest with CSS media queries. There is no custom CSS to write and nothing to configure in your theme. To **hide a block on mobile** but keep it on desktop, open the block settings in the editor sidebar and switch the device off.

Most visibility plugins give you three fixed sizes. Responsive Visibility lets you define your own **custom breakpoints** instead, so you can add a widescreen rule for large monitors, a small-phone rule, or whatever your layout needs, and name each one. You manage them on a single settings page, the way Elementor handles breakpoints.

A few things people use it for: hiding a large hero image on phones to cut load time, showing a different call to action on desktop, or keeping a sidebar widget off small screens. Any block becomes a **conditional block** without a shortcode or a line of code.

For a walkthrough, watch this tutorial:

https://youtu.be/uEtQ4CSHMOY

https://youtu.be/g7My09gTghI

= How It Works (3 Steps) =

1. **Edit any block** in the Gutenberg editor and open the Responsive Visibility panel in the sidebar.
2. **Pick the devices** to hide it on — mobile, tablet, desktop, or any custom breakpoint you defined.
3. **Save.** The block is hidden on exactly those screens — no custom CSS, no theme edits, no shortcodes.

= Why Responsive Visibility? =

* **Unlimited custom breakpoints** — not just mobile/tablet/desktop. Define any number of named device ranges and the plugin generates the matching CSS media queries automatically.
* **Cache-friendly** — visibility uses CSS classes and media queries, so every visitor gets the same HTML. It works with any caching plugin and updates live as the screen resizes.
* **Works with any theme** — block themes or classic themes, with no template edits and no dependencies.
* **Backward compatible** — blocks hidden with earlier versions keep working after an update, and your saved breakpoints are not lost.
* **Built for Gutenberg** — controls live in the native block Inspector, right where you already edit.

= Key Features =

* **Hide or show blocks by device** (desktop, tablet, mobile, and any custom breakpoint)
* **Visibility Conditions** — show or hide blocks by login status, user role, specific user or post, post type, page type, or shortcode, with All/Any logic
* **Unlimited custom breakpoints** — add, rename, reorder, and set pixel widths from Settings → Responsive Visibility
* **Dynamic CSS generation** — pixel values come from your saved breakpoints, never hardcoded
* **Cache-safe responsive design** — CSS-based hiding that survives full-page caching
* **Per-block control** — settings are applied per block, not per page
* **Seamless Gutenberg integration** with native WordPress blocks and most third-party blocks
* **Improve page experience** by hiding unnecessary or heavy elements on smaller screens

= Visibility Conditions — Show or Hide Blocks by More Than Device =

Version 1.2.0 adds **visibility conditions**, so you can hide or show a block based on the visitor or the page, not only the screen size. Add one or more conditions to a block, decide whether all of them or any of them have to match, and set whether a match shows the block or hides it. Conditions run on the server, so a hidden block is left out of the page instead of being hidden with CSS. That keeps member-only **conditional content** out of the page source where it could otherwise be read.

* **Login status** — show member-only blocks to **logged-in** users, or a sign-up call-to-action only to **logged-out** visitors.
* **User role** — show or hide blocks by **user role**: administrator, editor, author, subscriber, customer, or any custom role.
* **Specific user** — personalize a block for one exact user by ID.
* **Specific post or page** — show a block only on a chosen post or page.
* **Post type** — target posts, pages, WooCommerce products, or any **custom post type** (single view or archive).
* **Page type** — show on the front page, blog, single posts, archives, search results, or the 404 page.
* **Shortcode** — gate a block on any custom PHP logic using a shortcode — a flexible, developer-friendly escape hatch.

You can use conditions and device rules together on the same block. Login and role conditions work with caching plugins as long as logged-in visitors get an uncached page, which is the default in most caching setups.

= Ideal For =

* **Content creators** who want to hide large or secondary blocks on phones and tablets.
* **Designers** building responsive Gutenberg layouts without writing one-off CSS.
* **Site owners** tailoring what each device sees to boost engagement and speed.
* **Membership & WooCommerce sites** showing content by login status and user role — members, customers, and subscribers see exactly what's meant for them.
* **Agencies and developers** who need simple, reliable block visibility and conditional content on client sites.

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

= What are Visibility Conditions? =

Conditions let you show or hide a block based on the visitor or the page: Login Status, User Role, Specific User, Specific Post/Page, Post Type, Page Type, or Shortcode. Enable conditions in the block's "Visibility Conditions" panel, choose Show or Hide, add one or more conditions, and pick whether All or Any of them must match. Unlike device hiding (which uses CSS), conditions are evaluated on the server, so gated content is never placed in the page source.

= Do Visibility Conditions work with caching plugins? =

Conditions based on the logged-in user (Login Status, User Role, Specific User) work as long as your full-page cache serves a different (or no) cache to logged-in users — every major caching plugin does this by default. Device and breakpoint rules remain CSS-based and are unaffected by caching.

= Can I show content only to logged-in users or specific user roles? =

Yes. Use the **Login Status** condition to show or hide a block for logged-in or logged-out visitors, and the **User Role** condition to target administrators, editors, subscribers, customers, or any role. It's an easy way to build membership content, personalized blocks, and role-based layouts without extra plugins.

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

= 1.2.1 [7th July 2026] =
* Fixed: Minor bug fixes and performance improvements

= 1.2.0 [8th June 2026] =
* Added: Visibility Conditions engine — show or hide any block based on conditions, from the new "Visibility Conditions" panel
* Added: Conditions — Login Status, User Role, Specific User, Specific Post/Page, Post Type, Page Type, and Shortcode, combined with All/Any logic and is/is-not operators
* Added: Conditions are evaluated server-side, so gated content is never exposed in the page source
* Improved: Fully backward compatible — conditions are disabled by default, so existing blocks are unchanged
* Improved: Dashboard UI/UX for managing conditions and breakpoints
* Requires: WordPress 6.2+ (uses WP_HTML_Tag_Processor)

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
Adds the Visibility Conditions engine (login status, user role, post type, page type, and more). Fully backward compatible — conditions are off by default, so existing blocks are unchanged. Safe to update.

= 1.1.0 =
Adds unlimited custom breakpoints and dynamic CSS generation. Fully backward compatible — existing hidden blocks keep working. Safe to update.
