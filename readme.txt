=== WCAG Admin Accessibility Tools ===
Contributors: apos37
Tags: accessibility, alt text, screen reader, WCAG, a11y
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Accessibility diagnostics and tools for alt text, contrast, vague links, and more.

== Description ==
**WCAG Admin Accessibility Tools** provides a dual approach to accessibility improvements in WordPress: practical diagnostic tools for admins and editors, and optional front-end visual enhancements for users.

This plugin is primarily designed to complement the WAVE browser extension by WebAIM by offering tools that WAVE doesn’t cover or that we wanted to improve on. For a more complete accessibility review, using both is recommended.

**Features:**
- **Accessibility Admin Bar Tools:** Adds a front-end admin bar menu with auto-check and toggleable visual checks for accessibility issues:
  - Missing Alt Text
  - Poor Color Contrast (AA/AAA)
  - Vague Link Text (e.g. “click here”)
  - Improper Heading Hierarchy (e.g. skipping from H2 to H4)
  - Links Missing Underlines (excluding buttons and navs)
- **Skip to Content Link:** Inserts a visually hidden "Skip to main content" link at the top of each page for improved keyboard navigation.
- **Alt Text Column & Inline Editing:** Adds an “Alt Text” column to the Media Library list view, including an edit option for quickly updating missing or incorrect image alt text.
- **Additional Media Columns:** Adds columns for image dimensions, MIME type (e.g. `image/png`, `application/zip`), and file size.
- **Frontend User Assistant:** Adds an accessibility toolbox that includes tools such as Text Resizer, Readable Font, and Modes (default, dark, and greyscale), optionally placed as:
  - A floating toggle
  - A navigation menu item
  - A shortcode (`[wcagaat_assistant]`)
- **Logo Swap in Dark Mode:** Optionally swap logos when dark mode is enabled.
- **Auto-Detect Device Dark Mode:** If the user’s device is set to dark mode, a prompt will ask if they would like to enable dark mode for this site.
- **Custom Visibility Rules:** Choose who can see the frontend mode switcher — everyone, logged-in users, or just admins.
- **Custom Vague Phrases:** Configure your own list of vague link texts to scan for (e.g. “read more, learn more, click here”).

WCAG Admin Accessibility Tools gives you clear, actionable insights directly in the WordPress UI to improve accessibility compliance faster.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/wcag-admin-accessibility-tools/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit Tools > WCAG Admin Accessibility Tools

== Frequently Asked Questions ==
= Does this plugin automatically make my site accessible? =
No — WCAG Admin Accessibility Tools is not a one-click solution. It offers some very basic enhancements and provides tools that help you identify and resolve common accessibility issues more efficiently. We recommend also using the WAZE browser extension by WebAIM to identify further issues like missing aria labels, etc.

= Will this add a fully-ready Dark Mode to my site? =
No. This plugin applies basic dark mode styling to standard elements and provides tools to help you implement a dark mode. However, due to variations in theme structures and styles, you will need to write additional custom CSS to ensure full compatibility across your site. You may also disable the dark mode CSS stylesheets completely if you want to configure your own from scratch.

= How do I get a standalone tool instead of the whole assistant? =
Simply disable all other tools in the User Assistant. If a single tool is enabled, then the assistant changes to a standalone tool without having to open a modal to access it.

= Why isn't there a "High Contrast" mode? =
High contrast requirements vary significantly depending on user needs and content structure. Instead of enforcing a single high contrast scheme, this plugin provides a color contrast checker and markup tools so you can evaluate and customize contrast levels according to WCAG AA or AAA guidelines. If your site's color contrast is implemented correctly, a separate "High Contrast" mode is unnecessary. For users who prioritize stronger contrast, enabling AAA standards in the plugin settings is recommended.

= Where can I request features and get further support? =
We recommend using our [website support forum](https://pluginrx.com/support/plugin/wcag-admin-accessibility-tools/) as the primary method for requesting features and getting help. You can also reach out via our [Discord support server](https://discord.gg/3HnzNEJVnR) or the [WordPress.org support forum](https://wordpress.org/support/plugin/wcag-admin-accessibility-tools/), but please note that WordPress.org doesn’t always notify us of new posts, so it’s not ideal for time-sensitive issues.

== Changelog ==
= 1.2.0 =
* Update: Added a full User Assistant instead of just a modes selector, with a text resizer and readable font option (more to come)
* Deprecated: [wcagaat_modes] has been changed to [wcagaat_assistant]
* Update: Added an auto-detection for modes to prompt user if they want to switch to dark mode
* Tweak: Only load files where they are needed
* Update: Added an option to clean up on uninstall

= 1.0.4 =
* Fix: Some elements marked as underline issues when they have divs or spans inside
* Update: Added option to disable built-in dark mode stylesheets
* Update: Added option to turn off console logging, and only show it when admin bar toggle is enabled
* Fix: Turning off admin bar toggle didn't actually turn it off

= 1.0.3 =
* Tweak: Console log full path of images without alt text to find hidden elements

= 1.0.2 =
* Fixes: Prepare for deployment on WP.org repo

= 1.0.1 =
* Initial Release on June 18, 2025