=== SX User Name Security ===

Plugin Name:  SX User Name Security
Version:      2.2
Plugin URI:   http://www.seomix.fr
Description:  Prevents WordPress from showing User login and User ID. User Name Security filters the body_class function, User Nicename, Nickname and Display Name in order to avoid showing real User Login.
Usage: No configuration necessary. Upload, activate and done.
Availables languages : en_EN, fr_FR
Tags: security, protect, protection, login, user, users, nicename, nickname, display name, user id, user login, user nicename, user nickname, user display name, body_class
Author: Confridin
Author URI: http://www.seomix.fr
Contributors: Confridin, juliobox, secupress
Requires at least: 3.1
Tested up to: 3.9.1
Stable tag: trunk
License: GPL v3

SX User Name Security prevents WordPress from showing your real Login by overriding the body_class function, User Nicename, Nickname and Display Name.

== Description ==

WordPress automaticaly uses "User login" to fill in the "User Display Name". WordPress also allows everyone to use the same value for Nickname, Display Name and Login. A hacker can easily see then use your "NickName" or "Display Name" to find your real login. The body_class function also shows to everyone your User ID and Login on author pages.

Once activated, SX User Name Security will prevent WordPress from showing those informations.

**Features**

Body_class function :

* Removes User ID from body_class function (author pages)
* Removes User Nicename from body_class function (author pages)

User informations :

* The plugin changes "Display Name" and "Nickname" to a random value (like 'Ticibe T. Aduvoguripe', 'Lagubo N. Agigerovibe' or 'Datela N. Orejadavino') if they are equal to user login
* If not, it changes "Display Name" to "Nickname" or "Nickname" to "Display Name" if one of them is equal to user login

New Registration :

* Display Name and Nickname are changed to random value during user registration.
* Nicename is also changed (it's used to generate the user permalink on the front-end). For previous user, a notice has been added to use another plugin to safely change old nicenames. ;)

All functions are translated into french or english.

You can find me here : http://www.seomix.fr, and here is 2 french official posts about this plugin :
- http://www.seomix.fr/user-name-security/
- http://blog.secupress.fr/user-name-security-cachez-login-utilisateurs-61.html
Here is an english one : http://blog.secupress.fr/en/user-name-security-hide-user-login-67.html

== Installation ==

Upload and activate the plugin.

A notice and a button will be displayed to handle all users in order to hide their logins. Then, SX User Name Security will prevent WordPress from ever showing login and ID informations.

== Screenshots ==

1. "SX User Name Security" hides your author nicename and ID (body_class function).
2. When a user Nickname or Display Name are equals to Login, the plugin uses a random value instead.
3. During registration, WordPress won't use the same Display Name and Login for new users : "SX User Name Security" uses a random value.
4. An administrator is able to secure all users at once 

== Changelog ==

**2014/06/15**

* Minor fix for the admin profil URL link.

**2014/04/01**

* Add a button (JS only) to handle all users created before the plugin installation.
* Code improvements

**2014/03/26**

* Code improvement
* Bug fixes (in some cases, user "Display Name" and "Login" were not modified)
* Now an administrator can also trigger every function by saving a user (you don't have to wait every user to log-in)
* New alerts (admin notices)

**2013/03/08**

* first release

== Frequently Asked Questions ==

= Do I need to do anything else for this to work? =

Yes : just visit the admin user page to see if you have to modify some of your users.

It's also better to use SF Author URL Control (http://wordpress.org/plugins/sf-author-url-control/) combined with this plugin to also change current author permalinks (in order to hide login information).