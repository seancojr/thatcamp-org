=== Links shortcode ===
Contributors: maartenjs, Maarten Swemmer
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=79AKXNVRT8YSQ&lc=HK&item_name=Links%20Shortcode%20plugin%20by%20Maarten&item_number=Links%20Shortcode%20plugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: links, link, shortcode, category, Facebook, Like, Recommend, list of links, template, customizable
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.4.1

The plugin provides the shortcode 'links'. This shortcode shows all links having specified characteristics, following a specified template.

== Description ==

The plugin provides the shortcode 'links'. This shortcode displays a list of all links having specified characteristics, for example a link category name in your post. By default it includes a Facebook Like button for every link, but this can be disabled.

The plugin supports a customizable **template** for showing links. This enables you to use the shortcode to display links in any format you like, for example in a list or a table, with or without link images, etc. All relevant properties of a link are supported and listed on the Settings page of the plugin.

The typical format for the short code is 
> [links option1="x" option2="y" etc...]

The following options are available:

*   **fblike**: Show the facebook Like button (default '1', to disable set to any value other than '1')
*   **fbrecommend**: Show the Facebook Recommend botton (default '', to enable set to '1')
*   **orderby**: Order the links by (default 'name'). Use 'order' to order links by order specified with 'My Link Order' plugin.
*   **order**: How to order, ASC or DESC (default 'DESC')
*   **limit**: Limit the number of links shown (default '-1', which means no limit) 
*   **category**: Comma separated list of link category ID's
*   **category_name**: Category name of a catgeory of links to show. Overrides category parameter.
*   **hide_invisible**: Hide links marked as not visible (default '1', yes)
*   **include**: Comma separated list of numeric link IDs to include. If 'include' is used, the category, category_name, and exclude parameters are ignored. 
*   **exclude**: Comma separated list of numeric link IDs to exclude.,
*   **search**: Shows all links matching this search string. It searches url, link name and link description.

Dafault options can be changed on a 'Links Shortcode' page in the Settings menu.

Example: 
> [links category_name="Blogroll"]

Using the customizable template, all properties of a link can be displayed. An example template is included. This  template uses the Name, Web Address and Description of your links. The Name will link to the Web Address.

If the Name starts with a date, formatted as: yyyy-mm-dd followed by ':', a separate property  for the date is available.

Please note that the Description of a link has a limited length, but the Wordpress UI does not show this. After saving changes to a Link in the Links Wordpress only saves the first 255 characters. This has nothing to do with this Plugin.

== Installation ==

Just use the "Add New" button in Plugin section of your Wordpress blog's Control panel. To find the plugin there, search for 'Links Shortcode'. 

After installing you can use the shortocde anywhere in your blog as described in the description.

== Upgrade Notice ==

No special actions required before upgrading. 

== Screenshots ==

1. Settings page
2. Links Short code
2. Resulting list of links

== Changelog ==

= 1.4.1 (01-03-2013) = 
* Fixed issue where newly installed plugin does not contain a default template, resulting in no links being shown by default.

= 1.4 (27-02-2013) = 
* Made Facebook button SSL friendly as well, to prevent https security warnings
* Compatible with latest version of Wordpress
* Small bug fixes (making code neater)

= 1.3 (21-07-2012) =
* Corrected code to be SSL friendly.
* Added option to choose Facebook like button color (light or dark).
* Added functionality to save the date on which links are added or updated. This date is also available for sorting (use [links orderby="link_updated"]). The date is also availabel for display, where it can be overruled by adding the date in front of the link title as yyyy-mm-dd followed by ':'.
* Added the following fields to display dates in the links template: [date], [date_year], [date_month], [date_day]. 
* Added a link to the settings page to the Links menu, where they should be easier to find. The settings page can also still be found under Settings.
* Officially removed support for displaying category name, as this field is not supported by Wordpress in almost all cases. The value will still be supported if available.

= 1.2 =
* Fixed issue where settings were deleted when the plugin was deactivated. Now, settings will only be deleted on uninstall.
* Link_owner now displays the owner's display name instead of the useless owner ID.
* Added a new template tag "[link_rating_stars]" which displays the rating of a link using 0 to 5 stars.
* Improved the manual at http://blog.bigcircle.nl/about/wordpress-plugins/links-shortcode

= 1.1 =
* Added compatibility with 'My Link Order' plugin
* Fixed bug in link retrieving logic

= 1.0.1 =
* Added some example templates to choose from, including one that shows images (if you have entered image urls for your links)
* Added compatibility with what 'Andy's Link Last Edited Meta Box' does WordPress's link_updated field (thanks to wlindley)

= 1.0 =
* Added customizable template to display links in any way you like
* Added customizable alternative layout in case link property is empty

= 0.9 =
* Corrected spelling error

= 0.8 =
* Fixed issues where links showed too much space below and/or above a link.

= 0.7 =
* A Settings page has been added to set the most common default options.
* Translation support has been added for the Settings page.
* Stylesheet and layout have been improved to better display the links without the Facebook button.

= 0.6 =
* The first stable release at the Wordpress.org plugin repository.
