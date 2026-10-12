# Security review — 2026-10-13

Prepared by i729. This review records tested source changes; it is not a certification of the deployed service.

## Change

Require a private administrator key for visitor statistics, move analytics out of the web root, deny access to old public analytics paths, remove workstation paths, and pin SSH host keys/workflow actions.

## Validation

PHP syntax checks and three admin-key cases passed; workflow shell fragments parsed successfully.

## Required before rollout

Configure VISIT_ADMIN_KEY and private_bot storage before enabling analytics. Deploy index.php/security.php/.htaccess together through a controlled rollout; the existing deploy allowlist intentionally excludes protected renderers. Review/retire old analytics files and historical workstation/hosting-account metadata.

## Scope

Source and locally fetched Git branches/tags/available pull-request heads were inspected. Private production settings, actual database policies, deployed binaries, backup stores, and third-party service permissions were not changed. Current-source cleanup does not remove data from Git history, existing forks, cached copies, or previously downloaded assets. Sensitive values are intentionally omitted.
