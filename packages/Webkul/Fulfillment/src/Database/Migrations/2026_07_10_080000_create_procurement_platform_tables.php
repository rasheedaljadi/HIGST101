<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        // 1. provider_accounts
        Schema::create('provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->string('app_key')->nullable();
            $table->text('app_secret')->nullable(); // encrypted cast
            $table->text('access_token')->nullable(); // encrypted cast
            $table->text('refresh_token')->nullable(); // encrypted cast
            $table->string('status')->default('ACTIVE'); // ACTIVE, EXPIRED, DISABLED
            $table->timestamps();
        });

        // 2. procurement_sagas
        Schema::create('procurement_sagas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('state');
            $table->string('correlation_id');
            $table->string('causation_id');
            $table->string('trace_id')->nullable();
            $table->string('span_id')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('correlation_id');
        });

        // 3. procurement_aggregates
        Schema::create('procurement_aggregates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->timestamps();

            $table->index('purchase_order_id');
        });

        // 4. external_payload_archives
        Schema::create('external_payload_archives', function (Blueprint $table) {
            $table->id();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('normalized_dto')->nullable();
            $table->string('request_hash')->nullable();
            $table->string('response_hash')->nullable();
            $table->string('provider_version')->nullable();
            $table->string('contract_version')->nullable();
            $table->timestamps();
        });

        // 5. procurement_sessions
        Schema::create('procurement_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_aggregate_id')->nullable();
            $table->unsignedBigInteger('order_allocation_id');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->unsignedBigInteger('external_payload_archive_id')->nullable();
            $table->string('state')->default('CREATED');
            $table->string('contract_version')->nullable();
            $table->string('policy_version')->nullable();
            $table->string('policy_hash')->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->json('supplier_snapshot')->nullable();
            $table->json('shipping_snapshot')->nullable();
            $table->json('price_snapshot')->nullable();
            $table->string('snapshot_hash')->nullable();
            $table->timestamp('snapshot_finalized_at')->nullable();
            $table->json('metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->string('correlation_id');
            $table->string('causation_id');
            $table->string('trace_id')->nullable();
            $table->string('span_id')->nullable();
            $table->timestamps();

            $table->index('procurement_aggregate_id');
            $table->index('order_allocation_id');
            $table->index('provider_account_id');
            $table->index('external_payload_archive_id');
            $table->index('correlation_id');
        });

        // 6. procurement_commands
        Schema::create('procurement_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command_type');
            $table->string('idempotency_key')->unique();
            $table->unsignedBigInteger('procurement_session_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // 7. procurement_inbox_events
        Schema::create('procurement_inbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id');
            $table->string('event_type');
            $table->string('external_order_id')->nullable();
            $table->string('payload_hash');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id'], 'inbox_provider_event_unique');
        });

        // 8. procurement_dead_letters
        Schema::create('procurement_dead_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_session_id');
            $table->text('reason')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('retries')->default(0);
            $table->text('stack')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();

            $table->index('procurement_session_id');
            $table->index('correlation_id');
        });

        // 9. outgoing_requests
        Schema::create('outgoing_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_hash');
            $table->string('endpoint');
            $table->string('idempotency_key');
            $table->json('response_payload')->nullable();
            $table->string('response_hash')->nullable();
            $table->timestamp('sent_at');

            $table->index('request_hash');
            $table->index('idempotency_key');
        });

        // 10. external_orders
        Schema::create('external_orders', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->unsignedBigInteger('provider_account_id');
            $table->string('external_order_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('procurement_session_id');
            $table->string('status');
            $table->string('raw_reference')->nullable();
            $table->timestamps();

            $table->index('provider_account_id');
            $table->index('external_order_id');
            $table->index('purchase_order_id');
            $table->index('procurement_session_id');
        });

        // 11. external_order_projections
        Schema::create('external_order_projections', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_id')->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('status');
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
        });

        // 12. procurement_dashboard_projections
        Schema::create('procurement_dashboard_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->unique();
            $table->string('supplier_code');
            $table->string('current_step');
            $table->string('current_status');
            $table->unsignedInteger('progress_percent')->default(0);
            $table->string('tracking_number')->nullable();
            $table->unsignedInteger('retries_count')->default(0);
            $table->string('health_status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
        });

        // 13. external_api_logs
        Schema::create('external_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('endpoint');
            $table->string('method');
            $table->string('api_version')->nullable();
            $table->string('provider_api_version')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedInteger('status_code')->nullable();
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->string('correlation_id')->nullable();
            $table->string('causation_id')->nullable();
            $table->string('trace_id')->nullable();
            $table->string('span_id')->nullable();
            $table->unsignedBigInteger('procurement_session_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');

            $table->index('procurement_session_id');
            $table->index('purchase_order_id');
            $table->index('provider_account_id');
            $table->index('correlation_id');
        });

        // 14. procurement_timelines
        Schema::create('procurement_timelines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_session_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('stage');
            $table->json('payload')->nullable();
            $table->string('correlation_id');
            $table->string('causation_id');
            $table->string('trace_id')->nullable();
            $table->string('span_id')->nullable();
            $table->timestamp('created_at');

            $table->index('procurement_session_id');
            $table->index('purchase_order_id');
        });

        // 15. procurement_metrics
        Schema::create('procurement_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->decimal('average_submit_time', 10, 2)->default(0.00);
            $table->decimal('average_shipping_time', 10, 2)->default(0.00);
            $table->decimal('failure_rate', 5, 2)->default(0.00);
            $table->text('last_failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('procurement_metrics');
        Schema::dropIfExists('procurement_timelines');
        Schema::dropIfExists('external_api_logs');
        Schema::dropIfExists('procurement_dashboard_projections');
        Schema::dropIfExists('external_order_projections');
        Schema::dropIfExists('external_orders');
        Schema::dropIfExists('outgoing_requests');
        Schema::dropIfExists('procurement_dead_letters');
        Schema::dropIfExists('procurement_inbox_events');
        Schema::dropIfExists('procurement_commands');
        Schema::dropIfExists('procurement_sessions');
        Schema::dropIfExists('external_payload_archives');
        Schema::dropIfExists('procurement_aggregates');
        Schema::dropIfExists('procurement_sagas');
        Schema::dropIfExists('provider_accounts');

        Schema::enableForeignKeyConstraints();
    }
};
