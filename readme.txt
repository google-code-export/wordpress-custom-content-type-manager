=== Plugin Name ===
Contributors: fireproofsocks
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=355ULXBFXYL8N
Tags: cms, content management, custom post types, custom content types, custom fields, images, image fields, ecommerce
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: 4.3

Create custom content types (aka post types), standardize custom fields for each type, including dropdowns and images. Gives WP CMS functionality.

== Description ==

The Custom Content Type Manager plugin allows users to create custom content types (also known as post types) and standardize custom fields for each content type, including dropdowns, checkboxes, and images. This gives WordPress CMS functionality making it easier to use WP for eCommerce or content-driven sites.

One of the problems with WordPress' custom fields is that they are not standardized: users must add them one at a time each time they create a post. Furthermore, by default, WordPress' custom fields supports only text fields. This plugin lets user define a list of custom fields for each content type so that they always appear on each new post. 

For example, you can define a custom content type for "movie", then add a textarea field for "Plot Summary", an image field for "Poster Image", and a dropdown field for "Rating". All of these fields are available in the template's `single-movie.php` template file by using the included print_custom_field() function, e.g. `<?php print_custom_field('rating'); ?>`

Custom content types get their own link in the left-hand admin menu and their own URL structure.

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

1. Upload this plugin's folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Under the "Settings" menu, click the newly created "Custom Content Types" link, or click this plugin's "Settings" link on the Plugins page.
1. To test this, try adding a new content type named "movie" by clicking the "Add Custom Content Type" button at the top of the page.
1. There are a LOT of options when setting up a new content type, but pay attention to the "Name", "Show Admin User Interface", and "Public" settings. "Show Admin User Interface" *must* be checked in order for you to be able to create or edit new instances of your custom content type. 
1. Save the new content by clicking the "Create New Content Type" button.
1. Your content type should now be listed under on the Custom Content Types Manager settings page. Activate your new content type by clicking the blue "Activate" link.
1. Once you have activated the content type, you should see a new menu item in the left-hand admin menu. E.g. "Movies" in our example.
1. Try adding some custom fields to your new content type by clicking on the "Manage Custom Fields" link on the settings page.
1. You can add as many custom fields as you want by clicking the "Add Custom Field" button at the top of the page, e.g. try adding a "plot_summery" field using a "textarea" input type, and try adding a "rating" dropdown. 
1. When you are finished configuring your custom fields, click the "Save Changes" button.
1. Now try adding a new instance of your content type ("Movies" in this example). Click the link in the left-hand admin menu to add a movie.
1. Your new "Movie" post will have the custom fields you defined.


== Frequently Asked Questions ==

= What does activating a custom content type do? =

When you activate a custom content type, you ensure that it gets registered with WordPress. Once the content type is registered, a menu item will get created (so long as you checked the "Show Admin User Interface" box) and you ensure that its custom fields become standardized. If the "Public" box was checked for this content type, then the general public can access posts created under this content type using the URL structure defined by the "Permalink Action" and "Query Var" settings, e.g. http://site.com/?post_type=book&p=39

= What does deactivating a custom content type do? =

If you deactivate a custom content type, its settings remain in the database, but every other trace of it vanishes: any published posts under this content type will not be visible to the outside world, and the WordPress manager will no longer generate a link in the admin menu for you to create or edit posts in this content type.

= What types of custom fields are supported? =

Text fields, textarea, WYSIWYG, dropdowns (with customizable options), checkboxes, and media fields (which allow the user to select an image, video, or audio clip).

= How do I make my custom field values show up in my templates? =

Content and templates must go hand in hand.

= How can I use this plugin to support an eCommerce site? =

There are many ways to structure a site depending on what you are selling. For an example, let's say you are selling both T-shirts and Hats. You could create a single "product" content type, or you could create two different content types: "shirt" and "hat".


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Requirements ==

* WordPress 3.0.1 or greater
* PHP 5.2.6 or greater
* MySQL 5.0.41 or greater

These requirements are tested on load; the plugin will not load if these requirements are not met. Error messaging will fail if the user is using pre WP 2.0.11. 


== About ==

This plugin was written for the forthcoming book "Beginning WordPress Plugin Development" published by Packt. It was inspired by the [Custom-Post Type UI](http://wordpress.org/extend/plugins/custom-post-type-ui/) plugin written by Brad Williams. The Custom-Post Type UI plugin offers some of the same features, but I felt that its architecture was flawed: it stores data as taxonomical terms, which is conceptually awkward at a development level, and more importantly, it limits the amount of data of each field to 200 characters. 

On the surface, this plugin is similar, but this plugin stores custom field "correctly" as post meta data, which allows allows for different input types (e.g. checkboxes, dropdowns, and images) and the custom fields offered by this plugin can support data of virtually unlimited size. For example, you could define a WYSIWYG custom field for your custom content type and it could hold many pages of data.

The architecture for this plugin was also inspired by [MODx](http://modxcms.com/). WordPress is making progress as a viable content management system, but even after the thousands of lines of code in this plugin, it still does not support all the features in a system like MODx; WordPress templates are particularly limited by comparison.

== Future TO-DO == 

If you are eager to see one of these features implemented in a future release, please vote for it!

* Optionally allow users to add additional custom fields beyond the standardized fields.
* Wrap the "media" type fields with media specific input-types: "image", "audio", "video".
That will make more sense to the end users who are setting up custom fields for their
content types.
* Enable additional filters media browser (post-selector.php), including a post-type filter. At the end of the day, the post-selector.php coughs up a ID from wp_posts. You could have a field for "link" or "related item" or "next page" in a chain -- all you'd do is use the post-selector.php to select a post or page post_type instead of an attachment.
* Complete internationalization / localization
* Crack into the attribute meta box so you can optionally select a parent post that is a post_type other than the child. E.g. imagine a post_type of "Theater" with children with a post_type of "Movie" -- currently WP prevents that type of relationship in the page attribute meta box because they are 2 different content types. However, attachement post_types *can* have a parent_id that is a post or page.
* Full permalink support.  WP does not make this easy with custom post types, but 
ideally I'd like more options under the "Permalink Action" dropdown:
	* Off -- URLs will be simple GET style params
	* Inherit -- use the same permalink structure used by the rest of the site
	* Custom -- Fire off a secondary input that lets you define a permalink structure
		for each custom content type that mirrors the built-in permalink options.
http://xplus3.net/2010/05/20/wp3-custom-post-type-permalinks/ has some good info on this.
* Integrated taxonomy manager. So far, the "Simple Taxonomy" plugin is the only one 
I've found that is relatively few of bugs and is sensibly architected:
	http://redmine.beapi.fr/projects/show/simple-taxonomy
* Allow "list" fields -- e.g. you define a custom field that's a media type, if 
you check a box specifying that it's a list, it would allow you to add multiple 
instances of that field to your post.  That's a LOT trickier than what I'm doing 
now, but I think my architecture is sensible enough to support it. Those data patterns
start looking a lot like taxonomies though, so it's gonna require rock-solid explanations
to avoid confusing people.
* Supply more template functions, perhaps via a static class, e.g. CCTM::image('move_poster'); It might also be possible to spin this off to function names that are more familiar to WP template authors, e.g. "CCTM::the_movie_poster()". 
* Optionally define whether a content type shows up in the normal site archive menus.


== See also and References ==
* http://kovshenin.com/archives/extending-custom-post-types-in-wordpress-3-0/
* http://axcoto.com/blog/article/307


* Attachments in Custom Post Types:
http://xplus3.net/2010/08/08/archives-for-custom-post-types-in-wordpress/

* Taxonomies:
http://net.tutsplus.com/tutorials/wordpress/introducing-wordpress-3-custom-taxonomies/

* Editing Attachments
http://xplus3.net/2008/11/17/custom-thumbnails-wordpress-plugin/


== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`