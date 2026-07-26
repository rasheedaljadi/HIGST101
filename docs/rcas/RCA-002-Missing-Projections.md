# RCA-002: Missing External Variant Projections

## Executive Summary
Variant identity projections (`external_variant_projections`) were not created during initial product import due to missing `aliexpress_sku_id` attribute in the EAV attributes table. This caused all subsequent synchronization runs to output outbox events with `variant_id = null`, triggering early returns in catalog listeners and preventing price and inventory updates.

## Root Cause
`AliExpressProductImporter.php` line 1596 contained a guard clause checking for `aliexpress_sku_id` attribute ID in EAV. Because the attribute did not exist (`attribute_id = 0`), projection creation was aborted.

## Resolution
1. Created `AliExpressRebuildProjections` artisan command that reads `payload_snapshot` JSON directly as Source of Truth, independent of EAV attributes.
2. Backfilled 21 variant projections in `external_variant_projections`.
3. Created `AliExpressProjectionEndToEndTest` to verify variant_id propagation and listener execution.
