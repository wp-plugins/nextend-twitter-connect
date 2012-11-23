=== Nextend Twitter Connect ===
Contributors: nextendweb 
Tags: twitter, register, login, social connect, social, twitter connect
Donate link: https://www.facebook.com/nextendweb
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.4.21
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

One click registration & login plugin for Twitter? Easy installation? Is it totally free and comes with support? Yeah!

== Description ==

Check the [DEMO](http://www.nextendweb.com/wp-login.php) on our site.

Also we created a [Social Connect button generator](http://www.nextendweb.com/social-connect-button-generator) for this plugin. This allows you to create fancy login buttons. 

Personally, I hate to fill out registration forms, waiting for confirmation e-mails, so we designed this plugin for our website. Now, we want to share this very usable plugin with everyone, for free!
 
**Why should you choose Nextend Twitter Connect plugin from the many social plugins?**

* If your visitors have a Twitter profiles, they can register your site with a single click, and later to log in too.
* The previously registered users can simply attach their existing Twitter profile to their account, so in the future, they can logging in with the one social button.
* The plugin has multiple desings, so it fits all kind of websites smoothly and elegantly. - Soon
* Very simple to use.
* Fast and helpful support.
* Totally free.

If you like our stuff donate a like to our [Facebook page](https://www.facebook.com/nextendweb) or follow us on [Twitter](https://twitter.com/nextendweb)!

#### Usage

After you activated the plugin, the plugin will autmatically 

* add the login buttons to the WordPress login page. See screenshot #1
* add the account linking buttons to the WordPress profile page. See screenshot #2


#### Advanced usage

**Simple link**

&lt;a href="*siteurl*?loginTwitter=1&redirect=*siteurl*" onclick="window.location = \'*siteurl*?loginTwitter=1&redirect=\'+window.location.href; return false;"&gt;Click here to login or register with Twitter&lt;/a&gt;

**Image button**

&lt;a href="*siteurl*?loginTwitter=1&redirect=*siteurl*" onclick="window.location = \'*siteurl*?loginTwitter=1&redirect=\'+window.location.href; return false;"&gt; &lt;img src="HereComeTheImage" /&gt; &lt;/a&gt;

== Installation ==

1.  Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.
2.  Create a Twitter app => https://dev.twitter.com/apps/new
3.  Choose an App Name, it can be anything you like. Fill out the description and your website home page with site_url
4.  Callback url must be: siteurl/index.php?loginTwitter=1
5.  Accept the rules and Click on Create your twitter application
6.  The next page contains the Consumer key and Consumer secret which you have to copy and past below.
7.  Save changes!

== Screenshots ==

1. Our Social Connect plugins on the main WP login page
2. Our Social Connect plugins in the profile page for account linking


== Changelog ==

= 1.4.21 =
* Bugfix for Wordpress 3.5RC1

= 1.4.18 =
* Register redirect bugfix

= 1.4.17 =
* Bugfix

= 1.4.16 =
* Buttons added to registration form

= 1.4.15 =
* Added the option for different redirect for Login and Registration

= 1.4.14 =
* Login page jQuery fix

= 1.4.13 =
* Some login fixes

= 1.4.12 =
* Fixed session check

= 1.4.11 =
* Fixed wrong login urls on the settings page
* 
= 1.4.10 =
* Added editProfileRedirect parameter for buddypress edit profile redirect. Usage: siteurl?editProfileRedirect=1

= 1.4.9 =
* https bugfix - author Michel Weimerskirch

= 1.4.8 =
* Added name and @twitter support.
* 
= 1.4.4 =
* Modified login redirect issue for wp-login.php - author Michel Weimerskirch
* Added fix redirect url support. If you leave it empty or "auto" it will try to redirect back the user to the last visited page. 
 
= 1.1 =
* Added Social button generator support

= 1.0.1 =
* Added linking option to the profile page, so an already registered user can easily link the profile with a Facebook profile.
