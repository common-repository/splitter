=== Splitter ===
Contributors: umka
Donate link: http://www.gig.ru/
Tags: post, split, pages, nextpage, automatic, valid, html
Requires at least: 3.0
Tested up to: 3.1
Stable tag: trunk

Automatically split a post into pages by adding a `nextpage` tag, with html validity maintenance.

== Description ==

This plugin helps you to split a long post into pages by adding a `<!--nextpage-->` tag.
To split a post, just open it in the editor and specify a maximum amount of characters per page.
The resulting html will be valid: no broken tags, no split within a tag.

== Installation ==

1. Upload `splitter` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

If navigation on your website  doesn't appear, check, if the followig code exists after `<?php the_content(); ?>` function call in the 'single.php' file:
`<?php
	wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:' ), 'after' => '</div>' ) );
?>`
If it's not, you can add it manually.
Also, this style should present in your 'style.css' file:
`.page-link a:link,
.page-link a:visited {
	background: #f1f1f1;
	color: #333;
	font-weight: normal;
	padding: 0.5em 0.75em;
	text-decoration: none;
}
.page-link a {
	background: #d9e8f7;
}
.page-link a:active,
.page-link a:hover {
	color: #ff4b33;
}`

== Changelog ==

= 0.2 =
* Russian language added

= 0.1 =
* The first version
