# ADR-001: Payload Snapshot as Source of Truth for Variant Projections

## Status
**ACCEPTED** (2026-07-26)

## Context
During initial product imports, variant identity projections (`external_variant_projections`) were failing to be created because the `aliexpress_sku_id` attribute did not exist in the EAV attributes table (`attribute_id = 0`). Consequently, outbox events were generated with `variant_id = null`, preventing catalog listeners from applying price and stock updates.

## Decision
We decided to decouple `ExternalVariantProjection` backfills from EAV attributes entirely. The artisan command `aliexpress:rebuild-projections` parses the raw `payload_snapshot` JSON stored in `aliexpress_product_imports` as the single Source of Truth to construct `external_variant_projections`.

## Consequences
- **Positive**: Projections can be rebuilt deterministically regardless of EAV attribute state.
- **Positive**: Eliminates circular dependency between EAV attributes and projection mapping.
- **Negative**: Rebuild command must handle variant structure changes across different snapshot payload versions.
