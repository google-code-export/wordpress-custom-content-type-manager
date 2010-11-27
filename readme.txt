=== Plugin Name ===
Contributors: fireproofsocks
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=355ULXBFXYL8N
Tags: cms, content management, custom post types, custom content types, custom fields, images, image fields, ecommerce, modx
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: 0.8.0

Create custom content types (aka post types), standardize custom fields for each type, including dropdowns and images. Gives WP CMS functionality.

== Description ==

The Custom Content Type Manager plugin allows users to create custom content types (also known as post types) and standardize custom fields for each content type, including dropdowns, checkboxes, and images. This gives WordPress CMS functionality making it easier to use WP for eCommerce or content-driven sites.

One of the problems with WordPress' custom fields is that they are not standardized: users must add them one at a time each time they create a new post. Furthermore, by default, WordPress' custom fields supports only text fields. This plugin lets user define a list of custom fields for each content type so that they always appear on each new post. 

For example, you can define a custom content type for "movie", then add a textarea field for "Plot Summary", an image field for "Poster Image", and a dropdown field for "Rating". All of these fields are available in the template's `single-movie.php` template file by using the included print_custom_field() function, e.g. `<?php print_custom_field('rating'); ?>`

Custom content types get their own link in the admin menus and their own URL structure.


== Installation ==

1. Upload this plugin's folder to the `/wp-content/plugins/` directory or install it using the traditional WordPress plugin installation.
1. Activate the plugin through the 'Plugins' menu in the WordPress manager.
1. Under the "Settings" menu, click the newly created "Custom Content Types" link, or click this plugin's "Settings" link on the Plugins page.
1. After clicking the Settings link, you'll see a list of content types -- there are two built-in types listed: post and page. To test this plugin, try adding a new content type named "movie" by clicking the "Add Custom Content Type" button at the top of the page.
1. There are a *lot* of options when setting up a new content type, but pay attention to the "Name", "Show Admin User Interface", and "Public" settings. "Show Admin User Interface" *must* be checked in order for you to be able to create or edit new instances of your custom content type. 
1. Save the new content by clicking the "Create New Content Type" button.
1. Your content type should now be listed under on the Custom Content Types Manager settings page. Activate your new content type by clicking the blue "Activate" link.
1. Once you have activated the content type, you should see a new menu item in the left-hand admin menu. E.g. "Movies" in our example.
1. Try adding some custom fields to your new content type by clicking on the "Manage Custom Fields" link on the settings page.
1. You can add as many custom fields as you want by clicking the "Add Custom Field" button at the top of the page, e.g. try adding a "plot_summary" field using a "textarea" input type, and try adding a "rating" dropdown. 
1. When you are finished configuring your custom fields, click the "Save Changes" button.
1. Now try adding a new instance of your content type ("Movies" in this example). Click the link in the left-hand admin menu to add a movie.
1. Your new "Movie" post will have the custom fields you defined.


== Frequently Asked Questions ==

= What does activating a custom content type do? =

When you activate a custom content type, you ensure that it gets registered with WordPress. Once the content type is registered, a menu item will get created (so long as you checked the "Show Admin User Interface" box) and you ensure that its custom fields become standardized. If the "Public" box was checked for this content type, then the general public can access posts created under this content type using the URL structure defined by the "Permalink Action" and "Query Var" settings, e.g. http://site.com/?post_type=book&p=39

"Activating" a built-in post-type (i.e. pages or posts) will force their custom fields to be standardized. If you do not intended to standardize the custom fields for pages or posts, then there is no reason for you to activate them. 

= What does deactivating a custom content type do? =

If you deactivate a custom content type, its settings remain in the database, but every other trace of it vanishes: any published posts under this content type will not be visible to the outside world, and the WordPress manager will no longer generate a link in the admin menu for you to create or edit posts in this content type.

Deactivating a built-in post-type (a.k.a. content type) merely stops standardizing the custom fields; deactivating a built-in post-type has no other affect.

= What types of custom fields are supported? =

Text fields, textarea, WYSIWYG, dropdowns (with customizable options), checkboxes, and media fields (which allow the user to select an image, video, or audio clip).

= How do I make my custom field values show up in my templates? =

Content and templates must go hand in hand. If you have defined custom fields, you have to modify your theme files to accommodate them.  There are two included theme functions intended to help you with this task:

* get_custom_field() -- gets the value
* print_custom_field() -- prints the value

In this plugin's settings area, each content-type has a link to "View Sample Template" -- this page gives you a fully customized example showing you how to create a custom theme file for your custom content type.

See the includes/functions.php file in this plugin's directory for some other theme functions that are in development.


= How can I use this plugin to support an eCommerce site? =

There are many ways to structure a site depending on what you are selling. For an example, let's say you are selling both T-shirts. You could create a "shirt" content type, then you could define custom fields for size, color, and perhaps several image fields. Once you had defined the custom content type, you could simply create several "shirt" posts for each shirt design that you are selling.

== Known Bugs ==

* You cannot add menu items to navigation menus when this plugin is enabled. The Ajax call to wp-admin/admin-ajax.php encounters a 403 error: "Are you sure you want to do this?".  I don't know if this is a WordPress bug or a bug with this plugin.
* Possible bug? Don't use the same name for a taxonomy and a content-type (post-type). Saving a content-type now against registered taxonomies, but nothing prevents you from registering other taxonomies with other plugins.

== Screenshots ==

1. After activating this plugin, you can create custom content types (post types) by using the configuration page for this plugin. Click the "Custom Content Types" link under the Settings menu or click this plugin's "Settings" shortcut link in on the Plugin administration page.
2. You can create a new content type by clicking the button at the top of the settings page.
3. There are a lot of options available when you create a new content type, only some of them are pictured.
4. You can define new custom fields by clicking on the "Manage Custom Fields" link for any content type.
5. Clicking the "activate" link for any content type will cause its fields to be standardized and it will show up in the administration menus.
6. Once you have defined custom fields for a content type and you have activated that content type, those custom fields will show up when you edit a new post. Here's what the custom fields look like when I create a new "Movie" post.

== Changelog ==

= 0.8.0 =
* Initial public release

== Requirements ==

* WordPress 3.0.1 or greater
* PHP 5.2.6 or greater
* MySQL 5.0.41 or greater

These requirements are tested during WordPress initialization; the plugin will not load if these requirements are not met. Error messaging will fail if the user is using pre WP 2.0.11. 


== About ==

This plugin was written in part for the forthcoming book "Beginning WordPress Plugin Development" published by Packt. It was inspired by the [Custom-Post Type UI](http://wordpress.org/extend/plugins/custom-post-type-ui/) plugin written by Brad Williams. The Custom-Post Type UI plugin offers some of the same features, but I felt that its architecture was flawed: it stores data as taxonomical terms, which is conceptually awkward at a development level, and more importantly, it limits the each custom field to 200 characters of data, making it impossible to store certain types of custom content.

On the surface, this plugin is similar, but this plugin "correctly" stores custom field data as post meta data, which allows allows for different input types (e.g. checkboxes, dropdowns, and images) and the custom fields offered by this plugin can support data of virtually unlimited size. For example, you could define a WYSIWYG custom field for your custom content type and it could hold many pages of data.

The architecture for this plugin was also inspired by [MODx](http://modxcms.com/). WordPress is making progress as a viable content management system, but even after the thousands of lines of code in this plugin, it still does not support all the features in a system like MODx. WordPress templates are particularly limited by comparison. WordPress is great system for many scenarios, but if you're feeling that WordPress is starting to tear apart at the seams when it comes to custom content, it may be worth a look at another plugin or some of the other available systems.

== Future TO-DO == 

If you are eager to see one of these features implemented in a future release, please share your feedback!

* Improve UI (there are some monstrous forms in there... sorry!)
* Optionally allow users to add additional custom fields beyond the standardized fields.
* Wrap the "media" type fields with media specific input-types: "image", "audio", "video".
That will make more sense to the end users who are setting up custom fields for their
content types.
* Enable additional filters media browser (post-selector.php), including a post-type filter. At the end of the day, the post-selector.php coughs up a ID from wp_posts. You could have a field for "link" or "related item" or "next page" in a chain -- all you'd do is use the post-selector.php to select a post or page post_type instead of an attachment.
* Crack into the attribute meta box so you can optionally select a parent post that is a post_type other than the child. E.g. imagine a post_type of "Theater" with children with a post_type of "Movie" -- currently WP prevents that type of relationship in the page attribute meta box because they are 2 different content types. However, attatchment post_types *can* have a parent_id that is a post or page.
* Full permalink support.  WP does not make this easy with custom post types, but ideally I'd like more options under the "Permalink Action" dropdown:
	* Off -- URLs will be simple GET style params
	* Inherit -- use the same permalink structure used by the rest of the site
	* Custom -- Fire off a secondary input that lets you define a permalink structure
		for each custom content type that mirrors the built-in permalink options.
http://xplus3.net/2010/05/20/wp3-custom-post-type-permalinks/ has some good info on this.
* Integrated taxonomy manager. So far, the "Simple Taxonomy" plugin is the only taxonomy plugin that I've found that is relatively few of bugs and is sensibly architected: http://redmine.beapi.fr/projects/show/simple-taxonomy
* (questionably architecturally) Allow "list" fields -- e.g. you define a custom field that's a media type, if you check a box specifying that it's a list, it would allow you to add multiple instances of that field to your post.  That's a lot trickier than what I'm doing now, but I think my architecture is sensible enough to support it. Those data patterns start looking a lot like taxonomies though, so it's gonna require rock-solid explanations to avoid confusing people (including myself).
* Supply more template functions, perhaps via a static class, e.g. CCTM::image('move_poster'); It might also be possible to spin this off to function names that are more familiar to WP template authors, e.g. "CCTM::the_movie_poster()". See includes/functions.php for some functions in development.
* Archive Support: optionally define whether a content type shows up in the normal site archive menus.
* Pimp out the search box, INCLUDING the ability to specify a post_type when you create a relation field, e.g. for the products referencing a look, I should prime the form so that it only displays look type posts.  The architecture is there and already can do this, but I was having problems piping that stuff through javascript when fields are created dynamically.
* Sample template: Include a link in the manager somewhere for each content type that would generate a sample template for that content type, e.g. it could generate the contents of single-look.php including all custom fields. 
* Show-hide options for each custom field -- the custom field manager is way crowded.
* Permissions on editing custom content types -- lock it down! You don't want 2 people editing the same thing at the same time.

== See also and References ==
* http://kovshenin.com/archives/extending-custom-post-types-in-wordpress-3-0/
* http://axcoto.com/blog/article/307
* Attachments in Custom Post Types:
http://xplus3.net/2010/08/08/archives-for-custom-post-types-in-wordpress/
* Taxonomies:
http://net.tutsplus.com/tutorials/wordpress/introducing-wordpress-3-custom-taxonomies/
* Editing Attachments
http://xplus3.net/2008/11/17/custom-thumbnails-wordpress-plugin/