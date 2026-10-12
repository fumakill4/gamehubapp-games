# GameHubApp Games

A PHP website for cricket coverage, sports culture, and game guides for an Indian audience. The repository contains the homepage, article data, guide templates, frontend assets, and GitHub workflows for content updates and controlled deployment.

## Features

- A responsive homepage with featured stories and guide categories.
- Guide listing and individual article pages generated from PHP data.
- Local CSS, JavaScript, image assets, and SEO metadata.
- Visitor statistics and optional content automation.
- An explicit deployment allowlist that protects application and configuration files.

## Requirements

- PHP 8.3 for the local workflow used by this repository.
- Apache with `mod_rewrite` and `mod_headers` for the supplied production `.htaccess` rules.
- Node.js 22 and configured repository secrets only if running the optional Codex content workflow.

The website itself has no npm package manifest or frontend compilation step.

## Local preview

```sh
git clone https://github.com/fumakill4/gamehubapp-games.git
cd gamehubapp-games
php -S 127.0.0.1:8080 -t .
```

Open [the local homepage](http://127.0.0.1:8080/). The PHP development server does not apply Apache `.htaccess` rules; use it only on the loopback interface for a local preview. Use Apache when validating HTTPS redirects, security headers, and production routing.

## Repository layout

| Path | Purpose |
| --- | --- |
| `index.php` | Homepage rendering and visitor statistics |
| `guide-data.php` | Guide records and homepage article/category data |
| `guides.php` | Guide listing |
| `guide.php` | Individual guide rendering |
| `assets/` | Stylesheets, scripts, and images |
| `.htaccess` | Apache routing, caching, and response headers |
| `.github/workflows/` | Scheduled content updates and manual deployment |
| `.github/scripts/` | Automation safety checks |
| `sitemap.xml`, `robots.txt`, `llms.txt` | Discovery and crawler resources |

## Content updates

Edit guide records in `guide-data.php`, keep each slug unique, and check that images and internal links resolve. Update the sitemap when publishing a new guide. The scheduled cricket workflow uses its own guard script; it does not authorize arbitrary changes to protected renderers or configuration.

Check modified PHP files before publishing:

```sh
php -l index.php
php -l guide-data.php
php -l guide.php
php -l guides.php
```

## Deployment

The **Manual Safe Deploy** workflow defaults to a dry run and uploads only its listed paths. Hosting credentials belong in GitHub Actions secrets, never in source files. Review the upload list and preserve backups before an authorized deployment. Protected PHP renderers and server configuration require a separate controlled rollout.

## Security and private data

Authentication and analytics storage changes are being reviewed in [security PR #1](https://github.com/fumakill4/gamehubapp-games/pull/1). Deploy the reviewed corrections before exposing administrative statistics publicly. Private analytics storage and administrator credentials must remain outside the public document root and outside Git.

A public source repository is not evidence that the deployed server has been security-cleared. Historical commits, existing copies, and hosting configuration require separate review.

## Maintenance

Maintained by **i729**. Preserve upstream attribution and check asset licensing before reusing or redistributing third-party material.
