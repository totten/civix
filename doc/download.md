# Download

<!-- The instructions for `cv` and `civix` are nearly identical. Consider updating them in tandem. -->

`civix` is available as an executable binary (`civix.phar`) or source-code (`civix.git`).  It may be deployed as a system-wide tool, or it may be deployed as part of an
existing web-project. Below, we give general download summary and several example procedures.

* [Download URLs](#urls)
* [Comparison](#comparison)
* [Install `civix.phar` as system-wide tool (Linux/BSD/macOS)](#phar-unix)
* [Install `civix.phar` as project tool (composer)](#phar-composer)
* [Install `civix.phar` as project tool (phive)](#phar-phive)
* [Install `civix.git` as system-wide tool (Linux/BSD/macOS)](#src-unix)
* [Install `civix.git` as system-wide tool (Windows)](#src-win)
* [Install `civix.git` as project tool (composer)](#src-composer)

<a name="urls"></a>
## Download URLs

| Format            | Version(s)           | URLs |
| --                | --                   | --   |
| Executable binary | Latest               | PHAR: https://download.civicrm.org/civix/civix.phar<br/>GPG: https://download.civicrm.org/civix/civix.phar.asc<br/>SHA256: https://download.civicrm.org/civix/civix.SHA256SUMS |
|                   | Edge (*autobuild*)   | PHAR: https://download.civicrm.org/civix/civix-EDGE.phar<br/>Logs: https://test.civicrm.org/view/Tools/job/Tool-Publish-civix/ |
|                   | Historical           | Browse: https://github.com/civicrm/civix/releases/<br/>PHAR: `https://download.civicrm.org/civix/civix-X.Y.Z.phar`<br/>GPG: `https://download.civicrm.org/civix/civix-X.Y.Z.phar.asc`<br/>SHA256: `https://download.civicrm.org/civix/civix-X.Y.Z.SHA256SUMS`<br/><br/>(*Note: Prior to v23.08, binaries were not posted to Github.*) |
| Source code       | All versions         | Git: https://github.com/totten/civix |

<a name="comparison"></a>
## Comparison

There are a few procedures for installing. Here are key differences:

* __Executable binary vs source code__:
    * __Executable binary (`civix.phar`)__: The PHAR executable is designed to be portable.  It is a single file which can be plugged into many
      configurations/environments.
    * __Source code (`civix.git`)__:  The source-code is used for developing and debugging `civix`.  It requires more tooling and lacks some of the portability
      features in the PHAR.  (However, if there is a compatibility bug, then the source-code may make it easier to work-around or diagnose.)
* __System-wide tool vs project tool__:
    * __System-wide tool__: Install one copy of `civix` on each workstation or server. (You may need to re-install for each additional workstation or server.)
    * __Project tool__: Include a copy of `civix` with each web-project that you develop. (You may need to re-install for each additional project or site.)

<a name="phar-unix"></a>
## Install `civix.phar` as system-wide tool (Linux/BSD/macOS)

Choose the appropriate download (eg `https://download.civicrm.org/civix/civix.phar`). You may place the file directly in `/usr/local/bin`:

```bash
sudo curl -LsS https://download.civicrm.org/civix/civix.phar -o /usr/local/bin/civix
sudo chmod +x /usr/local/bin/civix
```

That is the quickest procedure, but it does not defend against supply-chain attacks. For improved security, split the process into three steps:

1. Download a specific binary (such as `civix-X.Y.Z.phar`):

    ```bash
    curl -LsS https://download.civicrm.org/civix/civix-X.Y.Z.phar -o civix-X.Y.Z.phar
    ```

2. Verify the binary with GPG or SHA256:

    ```bash
    ## Verify with GPG
    curl -LsS https://download.civicrm.org/civix/civix-X.Y.Z.phar.asc -o civix-X.Y.Z.phar.asc
    gpg --keyserver hkps://keys.openpgp.org --recv-keys 61819CB662DA5FFF79183EF83801D1B07A1E75CB
    gpg --verify civix-X.Y.Z.phar.asc civix-X.Y.Z.phar

    ## Verify with SHA256
    curl -LsS https://download.civicrm.org/civix/civix-X.Y.Z.SHA256SUMS -o civix-X.Y.Z.SHA256SUMS
    sha256sum -c < civix-X.Y.Z.SHA256SUMS
    ```

    (*GPG provides stronger verification. However, in some build-systems, it is easier and sufficient to integrate SHA256 checksums.*)

3. Finally, install the binary:

    ```bash
    chmod +x civix-X.Y.Z.phar
    sudo mv civix-X.Y.Z.phar /usr/local/bin/civix
    sudo chown root:root /usr/local/bin/civix   # For most Linux distributions
    sudo chown root:wheel /usr/local/bin/civix  # For most BSD/macOS distributions
    ```

<a name="phar-composer"></a>
## Install `civix.phar` as project tool (composer)

If you have are developing a web-project with [`composer`](https://getcomposer.org) (e.g.  Drupal 8/9/10/11) and wish to add `civix.phar` to your project,
then use the [composer-downloads-plugin](https://github.com/civicrm/composer-downloads-plugin).

```bash
composer require civicrm/composer-downloads-plugin
```

Add the binary URL to your top-level `composer.json`:

```javascript
{
  "extra": {
    "downloads": {
      "civix": {"url": "https://download.civicrm.org/civix/civix-X.Y.Z.phar", "path": "bin/civix", "type": "phar"}
    }
  }
}
```

And finally run `composer` to download the file. Either of these commands will work:

```bash
composer install
composer update --lock
```

<a name="phar-phive"></a>
## Install `civix.phar` as project tool (phive)

Like `composer`, [`phive`](https://phar.io/) allows you to add tools to a web-project. It has built-in support
for downloading, verifying, caching, and upgrading PHARs. Add `civix` to your project by running:

```bash
phive install totten/civix
```

By default, this will download the latest binary in `./tools/civix` and update the settings in `.phive/`.

<a name="src-unix"></a>
## Install `civix.git` as system-wide tool (Linux/BSD/macOS)

To download the source tree and all dependencies, use [`git`](https://git-scm.com) and [`composer`](https://getcomposer.org/).
For example, you might download to `$HOME/src/civix`:

```bash
git clone https://github.com/totten/civix $HOME/src/civix
cd $HOME/src/civix
composer install
./bin/civix --help
```

You may then add `$HOME/src/civix/bin` to your `PATH`. The command will be available in other folders:

```bash
export PATH="$HOME/src/civix/bin:$PATH"
cd /var/www/example.com/sites/default/files/civicrm/ext
civix generate:module mystuff
```

__TIP__: If your web-site uses Symfony components (as in D8/9/10/11), then you may see dependency-conflicts. You can resolve these by [building a custom PHAR](develop.md).

<a name="src-win"></a>
## Install `civix.git` as system-wide tool (Windows)

```
# Install composer
In a browser, visit http://getcomposer.org
Click on the download button.
Scroll down to Windows Installer and click on Composer-Setup.exe.
Choose Run when prompted.

# Install git
If you don't already have git, then in a browser visit http://git-scm.com/download/win.
Choose Run when prompted.
Leave all the defaults.

# Download civix
Decide where you want to install civix. You might want to put it in C:\Program Files, but you might get hassled about admin rights, in which case you can pick somewhere else, like C:\users\<your name>.
From the start menu choose All Programs -> Git -> Git Bash.
In the window that appears, type:
  cd "/c/users/<your name>"
  (note the forward slashes)
git clone git://github.com/totten/civix.git
exit

# Download dependencies
In windows explorer, navigate to C:\users\<your name> (or whereever you installed civix).
Shift-right-click on the civix folder.
Choose open command window here.
In the window that appears, type:
  composer install

# Add civix to the PATH
Either temporarily add it:
set PATH=%PATH%;C:\users\<your name>\civix\bin

OR permanently:
Start Menu -> Control Panel -> System -> Advanced -> Environment Variables
```

<a name="src-composer"></a>
## Install `civix.git` as project tool (composer)

If you have are developing a web-project with [`composer`](https://getcomposer.org) (e.g.  Drupal 8/9/10/11) and wish to add the `civix.git` source-code` to your project,
then use `composer require`:

```bash
cd /var/www/example.com
composer require civicrm/civix
```

By default, this will create the command `./vendor/bin/civix`.

__TIP__: If your web-site uses Symfony components (as in D8/9/10/11), then `composer` will attempt to reconcile the versions.  However, this is a
moving target, and the results may differ from the standard binaries.  It may occasionally require overrides or patches for compatibility.
