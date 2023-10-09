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
* Users can sign in with their GitHub account

TODO:

* Screenshot uploads
* License (probably will be MPL-2.0)
* Footer, link to main site
* Example privacy policy
* More `echo`
* `s/column/record/g`
* Example server configuration

Source code layout:

* [`schema.sql`](schema.sql): SQL schema
* [`config.example.php`](config.example.php): configuration file example/documentation
* [`htdocs/`](htdocs/): public files, mainly static assets
* [`htdocs/index.php`](htdocs/index.php): sole entry point and router
* [`templates/`](templates/): templates and view/controller code
* [`include/`](include/): utility functions and model code

Setting up for development
--------------------------

Make sure you have git, PHP 8 and the SQLite 3 command-line interface installed.

1. `git clone https://github.com/hikari-no-yume/app-compatibility-db`
2. `cd app-compatibility-db`
3. Set up a configuration: `cp config.example.php config.php`, then make sure to edit `config.php` appropriately
4. Set up database: `sqlite3 app_db.sqlite3 -init schema.sql` (then type `.quit` to exit)

You can then do `cd htdocs && php -S localhost:8000` to start a local server.

Currently there is no editing possible, so you will need to use a tool like `sqlite3` to manually edit the database.
