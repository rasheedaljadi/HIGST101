# Operational Runbook: Rebuilding Variant Projections

## Purpose
Explains how to safely backfill `external_variant_projections` for imported products.

## Execution Steps
1. SSH into production server.
2. Change directory to project root: `cd /home/highest-ye/htdocs/highest-ye.store`
3. Execute dry-run to preview mappings:
   `php8.3 artisan aliexpress:rebuild-projections --dry-run`
4. Execute projection rebuild:
   `php8.3 artisan aliexpress:rebuild-projections`
5. Verify total projection count:
   `php8.3 artisan tinker --execute="echo DB::table('external_variant_projections')->count();"`
