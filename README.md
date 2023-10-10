Web app for an emulator app compatibility database
==================================================

(WORK IN PROGRESS)

This is a simple web app for managing a public app compatibility database for an emulator project.

I am writing this for [touchHLE](https://touchhle.org/)'s use, but I'm trying to make it general enough that it could be used by other projects as well. To that end, most of the fields are defined by configuration (see `config.example.php`), so you can customise it to fit your needs. I've taken some inspiration from the [WINE AppDB](https://appdb.winehq.org/) and [Dolphin compatibility list](https://dolphin-emu.org/compat/).

It uses PHP with a SQLite database and no dependencies, so it's very lightweight and should be easy to set up.

Various features:

* Tracks three kinds of items, with a hierarchical relationship:
  * “Apps” (applications tested in the emulator)
  * “Versions” (versions of those applications)
  * “Reports” (different users' experiences with those versions)
* Users can sign in with their GitHub account.
* Reports can have a screenshot attached to them. Screenshots are compressed to ~50KB on the client side.
* Moderators have the power to approve or delete new reports.

TODO:

* License (probably will be MPL-2.0)

Source code layout:

* [`schema.sql`](schema.sql): SQL schema
* [`config.example.php`](config.example.php): configuration file example/documentation
* [`privacy.example.html`](privacy.example.html): example privacy policy
* [`nginx-config-example.conf`](nginx-config-example.conf): example nginx configuration
* [`htdocs/`](htdocs/): public files, mainly static assets
* [`htdocs/index.php`](htdocs/index.php): sole entry point and router
* [`templates/`](templates/): templates and view/controller code
* [`include/`](include/): utility functions and model code

Setting up for development
--------------------------

Make sure you have git, PHP 7.4 or PHP 8 and the SQLite 3 command-line interface installed.

1. `git clone https://github.com/hikari-no-yume/app-compatibility-db`
2. `cd app-compatibility-db`
3. Configure the web app: `cp config.example.php config.php`, then make sure to edit `config.php` appropriately
4. Create the database: `sqlite3 app_db.sqlite3 '.read schema.sql'`

You can then do `cd htdocs && php -S localhost:8000` to start a local server.

Deployment
----------

This web app assumes it has its own domain name. You might want to use a subdomain of your emulator project's main domain.

An example nginx configuration file is available, which uses PHP FPM. The only file that needs to be made writeable by the web server is the SQLite database.

Note that for a real deployment, you must provide a privacy policy, and you should be careful not to use the same GitHub API keys as for development.

If you're struggling to deploy this web app for yourself, feel free to contact me. I might be able to help.
