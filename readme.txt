=== XSLT RSS ===
Contributors: jixor
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=stephen%40margni%2ecom&item_name=XSLT%20RSS&no_shipping=1&return=http%3a%2f%2fwww%2ejixor%2ecom%2fthankyou%2ehtm&cancel_return=http%3a%2f%2fjp%2ejixor%2ecom&no_note=1&tax=0&currency_code=AUD&lc=AU&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: widget,rss,rss2,atom,xml,sidebar,feeds,feed
Requires at least: 2.2
Tested up to: 2.6.2
Stable tag: I dont know waht this means

Create advanced, fully customizable RSS feed widgets. You can use this to
replace the built in RSS widget or along side it. It is substantially more
powerful than the built in widget in that you have complete control over how the
feed is displayed via XSL template files.



== Description ==

This plugin is no longer supported, please use
[Advanced RSS](http://wordpress.org/extend/plugins/advanced-rss) instead.

This plugin creates a new RSS widget. You can use this to replace the built in
RSS widget or along side it. It is substantially more powerful than the built in
widget in that you have complete control over how the feed is displayed via XSL
template files. The plugin also includes an admin page for creating, editing and
deleting the xsl files, along with running tests to ensure your environment is
correctly setup.

You will need PHP 5, DOM extention and XSL extentions loaded on your server. If
you don't know what you have then activate the plugin and view the configuration
page, it will test your environment.

The plugin comes with a few example XSL files to get you started. You may not
even need to edit or create your own depending on what feeds you want to
syndicate. XSL is not the easiest thing, however once once you understand the
basics its fairly strait forward.

The included XSL teaplates demonstrate various XSLT methods, such as conditional
statemenets, variable use, XPath expressions and namespace access. I strongly
encourage you to analyse the included templates when learning xslt.

== Installation ==

1. Upload the xsltrss plugin directory, along with its contents, to your
   worpress plugins directory: `/wp-content/plugins/`.
2. Modify the xsl directory to enable write access (only if you want to use the
   admin interface to make changes to the installed tampltes, or create new
   template.)
3. In your wp-content folder ensure that there is a folder anmed "cache" which
   is writable. (The plugin will however attempt to create this automatically.)
4. Activate the plugin and follow the instructions in the description.



== Frequently Asked Questions ==

= What is XSL/XSLT? =

(Excerpt from w3schools)
XSL stands for EXtensible Stylesheet Language.

The World Wide Web Consortium (W3C) started to develop XSL because there was a
need for an XML-based style sheet language.

XSLT stands for XSL Transformations.

To put it another way, XSL is an advanced templateing language.

Read more about XSLT at [w3Schools](http://www.w3schools.com/xsl), or at
[w3](http://www.w3.org/TR/xslt).

= Do I need to know XSLT to use this plugin? =

The plugin includes an increasing number of default templates. If one of these
templates doesn't do what you need feel free to leave me a comment and I might
create one for you.



== Screenshots ==

1. The Widget configuration panel
2. The XSL template mangement screen
