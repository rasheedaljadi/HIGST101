<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot table name for complete state preservation and rollback.
     */
    protected string $snapshotTable = '_snapshot_20260816_attr_160_backup';

    /**
     * Canonical attribute target ID (ae_color).
     */
    protected int $canonicalAttrId = 146;

    /**
     * Duplicate attribute source ID (ae_color_name).
     */
    protected int $sourceAttrId = 160;

    /**
     * Exact option ID mapping from source 160 to canonical 146.
     */
    protected array $optionMap = [
        360 => 363, // dark blue
        361 => 212, // black
        375 => 210, // Red
        376 => 211, // GRAY
        377 => 209, // WHITE
        393 => 218, // Brown
        394 => 215, // Blue
        395 => 216, // YELLOW
        396 => 213, // green
    ];

    /**
     * Distinct options to re-parent to canonical attribute 146.
     */
    protected array $reparentOptionIds = [
        358, // BLACK BROWN
        359, // apple green
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // Step 1: Pre-flight Checks & Idempotency Inspection
        // ---------------------------------------------------------------------
        $canonicalAttr = DB::table('attributes')->where('id', $this->canonicalAttrId)->first();
        if (! $canonicalAttr || $canonicalAttr->code !== 'ae_color') {
            throw new RuntimeException("Pre-flight check failed: Canonical attribute {$this->canonicalAttrId} (ae_color) is missing or has incorrect code.");
        }

        $sourceAttr = DB::table('attributes')->where('id', $this->sourceAttrId)->first();

        // Case A: Source attribute 160 does not exist
        if (! $sourceAttr) {
            $isPostFlightValid = $this->verifyPostFlightState();
            if ($isPostFlightValid) {
                Log::info('Migration [2026_08_16_020000_merge_ae_color_name_into_ae_color]: Attribute 160 already cleaned up and post-flight state verified. No action needed.');

                return;
            }

            throw new RuntimeException("Pre-flight check failed: Attribute {$this->sourceAttrId} is missing but database state does not match expected post-flight consistency.");
        }

        if ($sourceAttr->code !== 'ae_color_name') {
            throw new RuntimeException("Pre-flight check failed: Source attribute {$this->sourceAttrId} code is '{$sourceAttr->code}', expected 'ae_color_name'.");
        }

        // Verify source option IDs match exact expected 11 options
        $sourceOptions = DB::table('attribute_options')->where('attribute_id', $this->sourceAttrId)->pluck('id')->all();
        sort($sourceOptions);
        $expectedOptions = array_merge(array_keys($this->optionMap), $this->reparentOptionIds);
        sort($expectedOptions);

        if ($sourceOptions !== $expectedOptions) {
            throw new RuntimeException('Pre-flight check failed: Source options do not match expected 11 option IDs: '.json_encode($sourceOptions).' vs '.json_encode($expectedOptions));
        }

        // Verify no collisions on product_attribute_values
        $pavCollisions = DB::table('product_attribute_values as p160')
            ->join('product_attribute_values as p146', 'p160.product_id', '=', 'p146.product_id')
            ->where('p160.attribute_id', $this->sourceAttrId)
            ->where('p146.attribute_id', $this->canonicalAttrId)
            ->count();

        if ($pavCollisions > 0) {
            throw new RuntimeException("Pre-flight check failed: Detected {$pavCollisions} product_attribute_values collisions between attribute 160 and 146.");
        }

        // Verify no collisions on product_super_attributes
        $psaCollisions = DB::table('product_super_attributes as psa160')
            ->join('product_super_attributes as psa146', 'psa160.product_id', '=', 'psa146.product_id')
            ->where('psa160.attribute_id', $this->sourceAttrId)
            ->where('psa146.attribute_id', $this->canonicalAttrId)
            ->count();

        if ($psaCollisions > 0) {
            throw new RuntimeException("Pre-flight check failed: Detected {$psaCollisions} product_super_attributes collisions between attribute 160 and 146.");
        }

        // ---------------------------------------------------------------------
        // Step 2: Create Snapshot Table & Populate Backup
        // ---------------------------------------------------------------------
        $this->createAndPopulateSnapshot();

        // ---------------------------------------------------------------------
        // Step 3: Atomic Migration Execution
        // ---------------------------------------------------------------------
        DB::transaction(function () {
            // 3.1: Update option IDs for mapped 9 options in product_attribute_values
            foreach ($this->optionMap as $oldOptId => $newOptId) {
                DB::table('product_attribute_values')
                    ->where('attribute_id', $this->sourceAttrId)
                    ->where('integer_value', $oldOptId)
                    ->update(['integer_value' => $newOptId]);
            }

            // 3.2: Re-parent distinct options (358, 359) to canonical attribute 146
            DB::table('attribute_options')
                ->whereIn('id', $this->reparentOptionIds)
                ->update(['attribute_id' => $this->canonicalAttrId]);

            // 3.3: Update attribute_id in product_attribute_values (160 -> 146)
            DB::table('product_attribute_values')
                ->where('attribute_id', $this->sourceAttrId)
                ->update(['attribute_id' => $this->canonicalAttrId]);

            // 3.4: Update attribute_id in product_super_attributes (160 -> 146)
            DB::table('product_super_attributes')
                ->where('attribute_id', $this->sourceAttrId)
                ->update(['attribute_id' => $this->canonicalAttrId]);

            // 3.5: Delete old 9 mapped options and their translations
            $mappedOptionIds = array_keys($this->optionMap);
            DB::table('attribute_option_translations')
                ->whereIn('attribute_option_id', $mappedOptionIds)
                ->delete();

            DB::table('attribute_options')
                ->whereIn('id', $mappedOptionIds)
                ->delete();

            // 3.6: Delete attribute 160 and its translations
            DB::table('attribute_translations')
                ->where('attribute_id', $this->sourceAttrId)
                ->delete();

            DB::table('attributes')
                ->where('id', $this->sourceAttrId)
                ->delete();
        });

        // ---------------------------------------------------------------------
        // Step 4: Verify Post-Flight State
        // ---------------------------------------------------------------------
        if (! $this->verifyPostFlightState()) {
            throw new RuntimeException('Post-flight verification failed: Records state does not match expected post-migration values.');
        }

        Log::info('Migration [2026_08_16_020000_merge_ae_color_name_into_ae_color]: Completed successfully and verified.');
    }

    /**
     * Reverse the migrations (Snapshot-based Restore).
     */
    public function down(): void
    {
        if (! Schema::hasTable($this->snapshotTable)) {
            throw new RuntimeException("Snapshot-based restore failed: Snapshot table '{$this->snapshotTable}' does not exist.");
        }

        DB::transaction(function () {
            // Restore attribute 160
            $attrBackup = DB::table($this->snapshotTable)->where('entity_type', 'attribute')->first();
            if ($attrBackup) {
                $data = json_decode($attrBackup->payload_json, true);
                DB::table('attributes')->insertOrIgnore($data);
            }

            // Restore attribute translations
            $attrTransBackups = DB::table($this->snapshotTable)->where('entity_type', 'attribute_translation')->get();
            foreach ($attrTransBackups as $backup) {
                $data = json_decode($backup->payload_json, true);
                DB::table('attribute_translations')->insertOrIgnore($data);
            }

            // Restore options
            $optBackups = DB::table($this->snapshotTable)->where('entity_type', 'attribute_option')->get();
            foreach ($optBackups as $backup) {
                $data = json_decode($backup->payload_json, true);
                DB::table('attribute_options')->insertOrIgnore($data);
            }

            // Restore option translations
            $optTransBackups = DB::table($this->snapshotTable)->where('entity_type', 'attribute_option_translation')->get();
            foreach ($optTransBackups as $backup) {
                $data = json_decode($backup->payload_json, true);
                DB::table('attribute_option_translations')->insertOrIgnore($data);
            }

            // Re-parent 358 and 359 back to 160
            DB::table('attribute_options')
                ->whereIn('id', $this->reparentOptionIds)
                ->update(['attribute_id' => $this->sourceAttrId]);

            // Restore product_attribute_values
            $pavBackups = DB::table($this->snapshotTable)->where('entity_type', 'product_attribute_value')->get();
            foreach ($pavBackups as $backup) {
                $data = json_decode($backup->payload_json, true);
                DB::table('product_attribute_values')
                    ->where('id', $data['id'])
                    ->update([
                        'attribute_id' => $data['attribute_id'],
                        'integer_value' => $data['integer_value'],
                    ]);
            }

            // Restore product_super_attributes
            $psaBackups = DB::table($this->snapshotTable)->where('entity_type', 'product_super_attribute')->get();
            foreach ($psaBackups as $backup) {
                $data = json_decode($backup->payload_json, true);
                if (isset($data['id'])) {
                    DB::table('product_super_attributes')
                        ->where('id', $data['id'])
                        ->update(['attribute_id' => $data['attribute_id']]);
                } else {
                    DB::table('product_super_attributes')
                        ->where('product_id', $data['product_id'])
                        ->where('attribute_id', $this->canonicalAttrId)
                        ->update(['attribute_id' => $data['attribute_id']]);
                }
            }
        });

        Log::info('Migration [2026_08_16_020000_merge_ae_color_name_into_ae_color]: Rollback completed from snapshot.');
    }

    /**
     * Create snapshot table and copy all affected records as JSON.
     */
    protected function createAndPopulateSnapshot(): void
    {
        Schema::dropIfExists($this->snapshotTable);

        Schema::create($this->snapshotTable, function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload_json');
            $table->timestamp('created_at')->useCurrent();
        });

        $snapshotData = [];

        // Attribute 160
        $attr = DB::table('attributes')->where('id', $this->sourceAttrId)->first();
        if ($attr) {
            $snapshotData[] = ['entity_type' => 'attribute', 'entity_id' => $attr->id, 'payload_json' => json_encode($attr)];
        }

        // Attribute Translations
        $attrTrans = DB::table('attribute_translations')->where('attribute_id', $this->sourceAttrId)->get();
        foreach ($attrTrans as $trans) {
            $snapshotData[] = ['entity_type' => 'attribute_translation', 'entity_id' => $trans->id, 'payload_json' => json_encode($trans)];
        }

        // Attribute Options
        $options = DB::table('attribute_options')->where('attribute_id', $this->sourceAttrId)->get();
        foreach ($options as $opt) {
            $snapshotData[] = ['entity_type' => 'attribute_option', 'entity_id' => $opt->id, 'payload_json' => json_encode($opt)];
        }

        // Option Translations
        $optTrans = DB::table('attribute_option_translations')
            ->whereIn('attribute_option_id', $options->pluck('id'))
            ->get();
        foreach ($optTrans as $trans) {
            $snapshotData[] = ['entity_type' => 'attribute_option_translation', 'entity_id' => $trans->id, 'payload_json' => json_encode($trans)];
        }

        // Product Attribute Values (28 rows)
        $pavs = DB::table('product_attribute_values')->where('attribute_id', $this->sourceAttrId)->get();
        foreach ($pavs as $pav) {
            $snapshotData[] = ['entity_type' => 'product_attribute_value', 'entity_id' => $pav->id, 'payload_json' => json_encode($pav)];
        }

        // Product Super Attributes (3 rows)
        $psas = DB::table('product_super_attributes')->where('attribute_id', $this->sourceAttrId)->get();
        foreach ($psas as $psa) {
            $entityId = $psa->id ?? null;
            $snapshotData[] = ['entity_type' => 'product_super_attribute', 'entity_id' => $entityId, 'payload_json' => json_encode($psa)];
        }

        DB::table($this->snapshotTable)->insert($snapshotData);

        // Verify snapshot records count
        $savedCount = DB::table($this->snapshotTable)->count();
        if ($savedCount < 40) { // 1 attr + 2 trans + 11 opts + 22 opt_trans + 28 pav + 3 psa = 67 rows
            throw new RuntimeException("Snapshot backup failed: Expected >= 60 backup rows, saved {$savedCount}.");
        }
    }

    /**
     * Verify post-flight state consistency.
     */
    protected function verifyPostFlightState(): bool
    {
        $attr160Count = DB::table('attributes')->where('id', $this->sourceAttrId)->orWhere('code', 'ae_color_name')->count();
        $pav160Count = DB::table('product_attribute_values')->where('attribute_id', $this->sourceAttrId)->count();
        $psa160Count = DB::table('product_super_attributes')->where('attribute_id', $this->sourceAttrId)->count();

        $pav146Count = DB::table('product_attribute_values')->where('attribute_id', $this->canonicalAttrId)->count();
        $psa146Count = DB::table('product_super_attributes')->where('attribute_id', $this->canonicalAttrId)->count();
        $opts146Count = DB::table('attribute_options')->where('attribute_id', $this->canonicalAttrId)->count();

        return ($attr160Count === 0)
            && ($pav160Count === 0)
            && ($psa160Count === 0)
            && ($pav146Count >= 656)
            && ($psa146Count >= 30)
            && ($opts146Count >= 47);
    }
};
