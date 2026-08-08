# Zoid Technologies Website

The Zoid Technologies website is the primary hub for the Zoid Technologies ecosystem, built on the BBSEngine6 and Zoid6 frameworks. This directory contains the website's PHP application, styling, templates, and build infrastructure.

## Project Overview

**Domain:** https://zoidtechnologies.com/
**Project Type:** Dynamic website with game integration
**Framework Stack:** BBSEngine6 (core), Zoid6 (shared utilities), Smarty (templating)
**Build System:** Make-based with SCSS compilation and rsync deployment

## Directory Structure

```
www/
├── php/                    # PHP application logic
│   ├── index.php          # Main entry point
│   └── Makefile           # PHP deployment rules
│
├── skin/                  # Frontend assets (styling, templates, images)
│   ├── css/              # Compiled CSS output (generated from SCSS)
│   ├── scss/             # SCSS source files (compiles to CSS)
│   │   ├── www.scss      # Main stylesheet
│   │   ├── _vars.scss    # Variables (imports zoid6vars)
│   │   └── Makefile      # SCSS compilation rules
│   ├── tmpl/             # Smarty templates
│   │   ├── page.tmpl     # Base page template
│   │   ├── index.tmpl    # Homepage
│   │   └── sections/     # Content sections
│   ├── js/               # JavaScript files
│   ├── art/              # Images, icons, artwork
│   └── Makefile          # Skin subsystem orchestrator
│
├── smarty/               # Custom Smarty plugins (if needed)
│   └── Makefile          # Plugin deployment rules
│
├── config-dev.php        # Development configuration
├── config-prod.php       # Production configuration
├── htaccess-prod         # Apache rewrite rules
├── .gitignore           # Git exclusions
├── Makefile             # Root build orchestrator
├── run_tests.sh         # Configuration test runner
├── test_config.php      # Comprehensive config validation
├── test_config_constants.php  # Constant validation
└── test_config_integration.php # Integration tests
```

## Setup and Development

### Prerequisites

- Apache with mod_rewrite enabled
- PHP 7.4+ with required extensions
- sass (SCSS compiler): `sudo apt-get install sass` or `npm install -g sass`
- rsync (for deployment): `sudo apt-get install rsync`
- Make: `sudo apt-get install build-essential`

### Local Development Setup

1. **Verify PHP environment:**
   ```bash
   php -v
   php -m | grep required-extension
   ```

2. **Check that the staging directories exist:**
   ```bash
   ls -la /srv/www/vhosts/zoidtechnologies.com/html/
   ```
   If not, create them:
   ```bash
   sudo mkdir -p /srv/www/vhosts/zoidtechnologies.com/html/templates_c
   sudo chmod g=rwxs /srv/www/vhosts/zoidtechnologies.com/html/templates_c
   ```

3. **Verify configuration loads correctly:**
   ```bash
   cd zoid6/sites/www
   php -r "require 'config-dev.php'; echo 'Config loaded OK\n';"
   ```

4. **Run configuration tests:**
   ```bash
   ./run_tests.sh
   ```

## Building and Deployment

### Build Process Overview

The Makefile orchestrates a multi-stage build process:

1. **SCSS Compilation** - Converts `skin/scss/*.scss` to `skin/css/*.css`
2. **File Staging** - Copies PHP, templates, assets to staging directory
3. **Configuration** - Deploys environment-specific config files
4. **Deployment** - Rsync staging to production (remote server)

### Build Commands

```bash
# Clean build artifacts (backup files, compiled CSS, etc.)
make clean

# Deploy to production (full pipeline)
make prod

# Compile SCSS only
make -C skin scss
```

### Make Targets Explained

- **`make prod`** - Full production deployment
  - Runs `make -C php stage` - Copy PHP files to staging
  - Runs `make -C smarty stage` - Copy Smarty plugins to staging
  - Runs `make -C skin stage` - Compile SCSS and copy templates/artwork
  - Tries `make -C js stage` - Copy JavaScript (if any .js files exist)
  - Copies config-prod.php → config.php
  - Copies htaccess-prod → .htaccess
  - Syncs staging directory to production server via rsync

- **`make clean`** - Remove temporary files
  - Deletes `*~` backup files
  - Delegates to subdirectory clean targets

### Configuration Management

Two configurations are provided:

#### Development (config-dev.php)
- SITEURL: `http://localhost/`
- VHOSTDIR: `/srv/www/vhosts/zoidtechnologies.com/html/`
- LOGENTRYPREFIX: `wwwdev`

Load in development:
```bash
# Manually in PHP scripts
require "config-dev.php";
```

#### Production (config-prod.php)
- SITEURL: `https://zoidtechnologies.com/`
- VHOSTDIR: `/srv/www/vhosts/zoidtechnologies.com/html/`
- LOGENTRYPREFIX: `wwwprod`

Deployed automatically by Makefile:
```bash
$(RSYNC) config-prod.php $(DOCROOT)config.php
```

### SCSS Build Process

SCSS files are compiled to CSS during `make prod` and `make -C skin scss`:

```bash
sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
     --load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
     --load-path /home/opencode/data/work/zoid6/scss/ \
     --load-path ./skin/scss/ \
     --sourcemap=none --stop-on-error --trace --style expanded \
     --update skin/scss/ skin/css/
```

**Load Path Order (variable resolution):**
1. `zoid6/shared/skin/scss/` - Framework shared variables and mixins
2. `bbsengine6/skin/scss/` - Engine-level styles
3. `zoid6/scss/` - Zoid6 project-wide styles
4. `skin/scss/` - Site-specific overrides (highest priority)

**Important:** The `_vars.scss` file imports `zoid6vars` from the shared paths. Never import "vars" in `_vars.scss` itself (prevents infinite import loop).

## Testing

### Configuration Tests

The project includes comprehensive configuration validation tests modeled after zoid6:

```bash
# Run all tests at once
./run_tests.sh

# Or run individual tests
php test_config_constants.php      # Validates all config\ constants exist
php test_config_integration.php    # Validates configuration integration
php test_config.php                # Comprehensive validation suite
```

**Test Coverage:**
- ✓ Constants defined (all config\ namespace constants)
- ✓ Constants have correct types (string, array, etc.)
- ✓ Include paths configured correctly
- ✓ Path constants properly formed and end with slashes
- ✓ URL constants use http/https
- ✓ Smarty paths include local and fallback directories
- ✓ Analytics and logging configured

### Live Integration Tests

`test_topbar_article2.py` fetches the live `https://zoidtechnologies.com/` homepage, extracts the `<div id="topbar">` block, and asserts that the `article2` link is rendered. It runs as Test 3 in `./run_tests.sh` and is skipped if `python3` is not available.

```bash
./test_topbar_article2.py        # standalone
./run_tests.sh                   # includes Test 3
```

Exit codes: `0` pass, `1` content mismatch (article2 missing from rendered topbar), `2` network failure (cannot reach `https://zoidtechnologies.com/`). Requires network access to the production URL.

### Manual Testing

After deployment, verify the site works:

```bash
# Test with curl
curl https://zoidtechnologies.com/

# Check that config loads
php -r "require '/srv/www/vhosts/zoidtechnologies.com/html/config.php'; echo 'OK';"

# Check file permissions
ls -la /srv/www/vhosts/zoidtechnologies.com/html/
```

## URL Routing

The `.htaccess` file defines URL rewrite rules for clean URLs. Add rules like:

```apache
RewriteRule ^about[/]?$ /page.php?name=about [last,qsappend]
RewriteRule ^games[/]?$ /page.php?name=games [last,qsappend]
```

Uncomment relevant rules in `htaccess-prod` as you add new pages.

**HTTPS Enforcement:**
All requests are automatically redirected to HTTPS except `.well-known` (for SSL verification).

## Architecture

### PHP Application

- **Entry Point:** `php/index.php` - Main controller
- **Config Loading:** `config.php` (auto-generated from config-prod.php)
- **Include Path:** Loads from `/srv/www/zoid6/php/`, `/srv/www/bbsengine6/php/`, `/srv/www/smarty/`

### Smarty Templating

Templates use inheritance:

```smarty
{extends file="page.tmpl"}

{block name="content"}
  <h1>Page Title</h1>
  {include file="section.tmpl" data=$data}
{/block}
```

**Template Search Path** (in config):
1. `skin/tmpl/` - Site-specific templates
2. `tmpl/` - Legacy template location
3. `/srv/www/zoid6/shared/skin/tmpl/` - Shared zoid6 templates
4. `/srv/www/zoid6/skin/tmpl/` - Zoid6 project templates
5. `/srv/www/bbsengine6/skin/tmpl/` - Framework templates (fallback)

### Styling (SCSS)

Main stylesheets:
- **`skin/scss/www.scss`** - Main stylesheet (imports others)
- **`skin/scss/_vars.scss`** - Variables (imports zoid6vars)

Compiled to `skin/css/*.css` by build process.

### JavaScript

JavaScript files should be placed in `skin/js/`:

Include in templates:
```smarty
<script src="{$smarty.const.JSURL}mymodule.js"></script>
```

### Assets

Images and artwork go in `skin/art/`:
- Reference via `{$smarty.const.IMAGESURL}` for static CDN images
- Or `{$smarty.const.SKINURL}art/` for site-specific artwork

## Deployment

### Production Deployment

Full deployment to production server (merlin):

```bash
cd zoid6/sites/www
make prod
```

This:
1. Compiles SCSS to CSS
2. Stages all files to `/srv/www/vhosts/zoidtechnologies.com/html/`
3. Deploys to `merlin:/srv/www/vhosts/zoidtechnologies.com/html/` via rsync

### Rsync Options

The Makefile uses rsync with:
- `--delete-after` - Remove files from destination not in source
- `--checksum` - Verify changes by checksum (not timestamp)
- `--backup` - Create backups of changed files
- `--chmod=Dg=rwxs,Fgu=rw,Fo=r` - Set proper permissions
- `--archive` - Preserve permissions and timestamps

### CSS Deployment - Shared Framework Resources

**⚠️ IMPORTANT:** After running `make prod`, you must also deploy the shared CSS:

```bash
cd /home/opencode/data/work/zoid6
make shared
```

This deploys `/srv/www/zoid6/shared/` to the vhost, which includes critical CSS files like `zoid6.css` that provides the page background color (#3a5a40) and flexbox layout.

See `CSS_DEPLOYMENT_REQUIRED.md` for detailed information.

### Troubleshooting Deployment

**CSS Background Color Missing:**
If the page loads but has no background color, the shared CSS files weren't deployed. See `CSS_DEPLOYMENT_REQUIRED.md` for the fix.

**Permission Issues:**
If deployment fails due to permissions, ensure the user running `make prod` and `make shared` has:
- Write access to `/srv/www/vhosts/zoidtechnologies.com/html/`
- Membership in the `www` group

**Staging Directory Issues:**
```bash
# Verify staging directory exists and is writable
ls -la /srv/www/vhosts/zoidtechnologies.com/html/

# Create if missing
sudo mkdir -p /srv/www/vhosts/zoidtechnologies.com/html/
sudo chmod g=rwxs /srv/www/vhosts/zoidtechnologies.com/html/
```

## Common Tasks

### Add a New Page

1. Create template: `skin/tmpl/mypage.tmpl`
2. Add rewrite rule in `htaccess-prod`:
   ```apache
   RewriteRule ^mypage[/]?$ /page.php?name=mypage [last,qsappend]
   ```
3. Add logic in `php/index.php` or create `php/mypage.php`
4. Deploy: `make prod`

### Add JavaScript

1. Create file: `skin/js/mymodule.js`
2. Include in template: `<script src="{$smarty.const.JSURL}mymodule.js"></script>`
3. Deploy: `make prod`

### Customize Styles

1. Edit `skin/scss/www.scss` or create new SCSS file
2. Import new file in main stylesheet or override in `_vars.scss`
3. Build and test: `make -C skin scss`
4. Deploy: `make prod`

### Add Custom Smarty Plugin

1. Create plugin: `smarty/function.myplugin.php`
2. Use in templates: `{myplugin param1="value"}`
3. Deploy: `make prod`

## Git and Version Control

The `.gitignore` file excludes:
- `*~` - Editor backup files
- `.sass-cache/` - SCSS compiler cache
- `skin/css/*.css` - Compiled CSS (regenerate from SCSS)
- `skin/scss/skin/` - SCSS build output
- `templates_c/` - Smarty compiled templates

**Best Practices:**
- Don't commit compiled CSS or templates_c
- Always update source files (SCSS, templates)
- Run `make clean` before committing
- Test with `./run_tests.sh` before pushing

## Configuration Constants Reference

All configuration is defined in `config.php` (auto-generated from config-{dev,prod}.php):

| Constant | Type | Purpose |
|----------|------|---------|
| `config\SITENAME` | string | Internal site identifier ("www") |
| `config\SITETITLE` | string | HTML title tag content |
| `config\SITEURL` | string | Base URL with trailing slash |
| `config\VHOSTDIR` | string | Server filesystem path to site root |
| `config\SKINDIR` | string | Path to skin directory |
| `config\SKINURL` | string | URL to skin directory |
| `config\JSURL` | string | URL to JavaScript directory |
| `config\IMAGESURL` | string | CDN URL for static images |
| `config\SMARTYTEMPLATESDIR` | array | Template search paths |
| `config\SMARTYPLUGINSDIR` | array | Plugin search paths |
| `config\SMARTYCOMPILEDTEMPLATESDIR` | string | Compiled template cache path |
| `config\LOGENTRYPREFIX` | string | Log prefix for identifying entries |
| `GOOGLEANALYTICSACCOUNT` | string | GA tracking code |

## Troubleshooting

### Configuration Test Failures

```bash
# Run tests with verbose output
php test_config.php

# Check include paths are correct
php -r "echo implode(\":\n\", explode(\":\", get_include_path())); echo \"\n\";"
```

### SCSS Compilation Fails

```bash
# Check sass is installed
sass --version

# Verify load paths exist
ls /home/opencode/data/work/zoid6/shared/skin/scss/
ls /home/opencode/data/work/bbsengine6/skin/scss/
ls /home/opencode/data/work/zoid6/scss/

# Compile with verbose output
cd skin/scss
sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
     --load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
     --load-path /home/opencode/data/work/zoid6/scss/ --trace www.scss
```

### Templates Not Rendering

```bash
# Check Smarty cache directory exists and is writable
ls -la /srv/www/vhosts/zoidtechnologies.com/html/templates_c/

# Create if missing
mkdir -p /srv/www/vhosts/zoidtechnologies.com/html/templates_c/
chmod g=rwxs /srv/www/vhosts/zoidtechnologies.com/html/templates_c/
```

### Rsync Permission Denied

```bash
# Verify SSH key setup
ssh-keyscan -t rsa merlin >> ~/.ssh/known_hosts

# Check you can SSH to merlin
ssh merlin "echo OK"

# Verify remote directory is writable
ssh merlin "ls -la /srv/www/vhosts/zoidtechnologies.com/html/"
```

## Further Reading

- **BBSEngine6:** `/srv/www/bbsengine6/` - Core application framework
- **Zoid6:** `/srv/www/zoid6/` - Shared utilities and base site
- **Smarty Documentation:** https://www.smarty.net/docs/en/
- **SCSS/Sass:** https://sass-lang.com/documentation

## Contributing

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Make your changes
3. Test: `./run_tests.sh` and `make clean && make prod`
4. Commit with clear messages
5. Push and create a pull request

## License

This project is part of the Zoid Technologies ecosystem.

---

**Last Updated:** 2026-04-01
**Version:** 1.0
