# Installation instructions

> **Side note for Linux users**: Please avoid doing this installation as `root`, since your web server probably runs with another user (`www-data` on Debian/Ubuntu, `apache` on Redhat/CentOS/Fedora).
> 
> If you still want to do this as `root`, then ensure that your webserver has at least write access in the `app/` directory.

## Glossary
* /eZ/Publish/5/root/: The file system path where eZ Publish 5 is installed in, like "/home/myuser/www/" or "/var/sites/ezpublish/"
* /eZ/Publish/5/legacy/root/:
	* "Legacy" aka "Legacy Stack" refers to the eZ Publish 4.x installation which is bundled with eZ Publish 5 inside "app/ezpublish_legacy/"
	* The Legacy root is thus the root eZ Publish 5 path + the sub path mentioned above, example:  "/var/sites/ezpublish/app/ezpublish_legacy/"

## Installation

### A: From Archvie (tar.gz)
1. Extract the archive

   **For upgrading from eZ Publish Enterprise Edition 4.7**: Upgrade documentation can be found on http://doc.ez.no/eZ-Publish/Upgrading/Upgrading-to-5.0/Upgrading-from-4.7-to-5.0

### B: From GIT **Development ONLY**
1. You can get eZ Publish using GIT with the following command:
       ```bash
       git clone https://github.com/ezsystems/ezpublish5.git
       ```

2. Get eZ Publish Legacy
       ```bash
       cd /eZ/Publish/5/root/app
       git clone https://github.com/ezsystems/ezpublish.git ezpublish_legacy
       ```

3. *Optional* Upgrade eZ Publish Community Project installation
    1. Start from / upgrade to [latest](http://share.ez.no/downloads/downloads) eZ Publish CP installation.

    2. Follow normal eZ Publish upgrade procedures for upgrading the distribution files and moving over extensions.

**4. Install the dependencies with [Composer](http://getcomposer.org): the recommanded way**

       Download composer and install dependencies by running:
       $ bash
       $ cd /path/to/ezpublish5/
       $ curl -s http://getcomposer.org/installer | php
       $ ./composer.phar create-project  ezsystems/ezpublish5-standard ezpublish5-standard
       
using composer's method keeps your code update. Everytime you want to get last updates of all your bundles, just run this command:
```
$ composer.phar update
```
## Setup files
1. Configure:
    * Copy `app/config/parameters.yml.dist` to `app/config/parameters.yml`
    * Edit `app/config/parameters.yml` and configure

         * `ezpublish.api.storage_engine.legacy.dsn`: DSN to your database connection (only MySQL and PostgreSQL are supported at the moment)
         * `ezpublish.siteaccess.default`: Should be a **valid siteaccess** (preferably the same than `[SiteSettings].DefaultAccess` set in your `settings/override/site.ini.append.php`

2. Dump your assets in your webroot:

    ```bash
    php app/console assets:install --symlink web
    php app/console ezpublish:legacy:assets_install
    ```
    The first command will symlink all the assets from your bundles in the `web/` folder, in a `bundles/` sub-folder.

    The second command will symlink assets from your eZ Publish legacy directory and add wrapper scripts around the legacy front controllers
    (basically `index_treemenu.php`, `index_rest.php` and `index_cluster.php`)

3. *Optional* - Configure a VirtualHost:

    ```apache
    <VirtualHost *:80>
        ServerName your-host-name
        DocumentRoot /path/to/ezpublish5/web/

        <Directory /path/to/ezpublish5/>
            Options FollowSymLinks
            order allow,deny
            allow from all
        </Directory>

        RewriteEngine On
        RewriteRule ^/api/ /index_rest.php [L]

        # If using cluster, uncomment the following two lines:
        #RewriteRule ^/var/([^/]+/)?storage/images(-versioned)?/.* /index_cluster.php [L]
        #RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* /index_cluster.php [L]
        
        RewriteRule ^/var/([^/]+/)?storage/.* - [L]
        RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* - [L]
        RewriteRule ^/design/([^/]+/)?(stylesheets|images|javascript)/.* - [L]
        RewriteRule ^/share/icons/.* - [L]
        RewriteRule ^/extension/[^/]+/design/[^/]+/(lib|stylesheets|images|javascripts?)/.* - [L]
        RewriteRule ^/packages/styles/.+/(stylesheets|images|javascript)/[^/]+/.* - [L]
        RewriteRule ^/packages/styles/.+/thumbnail/.* - [L]
        RewriteRule ^/var/storage/packages/.* - [L]
        RewriteRule ^/favicon\.ico - [L]
        RewriteRule ^/robots\.txt - [L]
   
        # Following rule is needed to correctly display assets from bundles
        RewriteRule ^/bundles/ - [L]
        RewriteRule .* /index.php
    </VirtualHost>
    ```

## Run eZ Publish

1. *Optional*, **Development ONLY** - Take advantage of PHP 5.4 build-in web server:

    ```bash
    php app/console server:run localhost:8000
    ```
    The command above will run the built-in web server on localhost, on port 8000.
    You will have access to eZ Publish by going to `http://localhost:8000` from your browser.

### Clean installation using Setup wizard
1. Run Setup wizard:

    There is currently a know issue in eZ Publish 5's Symfony based stack when it comes to Setup wizard, so you will need to execute it directly from the  /eZ/Publish/5/legacy/root/ by exposing that as a internal wirtual host  as well.
    This can be done in same way as described on doc.ez.no for Virtual host setups where "eZ Publish" path will be: /eZ/Publish/5/legacy/root/

##### Troubleshooting during Setup wizard
You might get the following error:
> Retrieving remote site packages list failed. You may upload packages manually.
>
> Remote repository URL: http://packages.ez.no/ezpublish/5.0/5.0.0[-alpha1]/

This should only happen when you install from GIT or use pre realease packages
To fix it, tweak your `settings/package.ini` by overriding it with a valid version:

```ini
[RepositorySettings]
RemotePackagesIndexURL=http://packages.ez.no/ezpublish/4.7/4.7.0
```
