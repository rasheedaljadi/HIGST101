<?php

namespace App\Http\Controllers\AliExpress;

use App\Http\Controllers\Controller;
use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;
use Webkul\Fulfillment\Services\Application\OutboxEventProcessor;

class AliExpressSyncController extends Controller
{
    /**
     * Render the Synchronization Management page.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search', '');

        // 1. Paginated imports
        $query = AliExpressProductImport::with('product')
            ->orderBy('id', 'desc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('aliexpress_product_id', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('product_id', $search);
            });
        }

        $imports = $query->paginate(15, ['*'], 'imports_page')->withQueryString();

        // Calculate statistics for the LAST sync session (within 10 minutes of the latest updated record)
        $latestImport = AliExpressProductImport::orderBy('updated_at', 'desc')->first();

        if ($latestImport) {
            $latestTime = $latestImport->updated_at;
            $threshold = $latestTime->copy()->subMinutes(10);

            $lastSyncImports = AliExpressProductImport::where('updated_at', '>=', $threshold)->get();

            $totalCount = $lastSyncImports->count();
            $successCount = $lastSyncImports->where('status', 'success')->count();
            $failedCount = $lastSyncImports->where('status', 'failed')->count();
        } else {
            $totalCount = 0;
            $successCount = 0;
            $failedCount = 0;
        }

        // 2. Paginated Sync Runs logs (Sprint 2)
        $syncRuns = SyncRun::orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'runs_page')
            ->withQueryString();

        // 3. Paginated Outbox Events (Sprint 2)
        $outboxEvents = DB::table('domain_outbox_events')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'outbox_page')
            ->withQueryString();

        // Fetch latest error message details for outbox events on this page
        $outboxIds = $outboxEvents->pluck('id')->toArray();
        $outboxErrors = [];
        if (! empty($outboxIds)) {
            $attempts = DB::table('domain_outbox_event_attempts')
                ->whereIn('outbox_event_id', $outboxIds)
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy('outbox_event_id');
            foreach ($attempts as $outboxId => $group) {
                $outboxErrors[$outboxId] = $group->first()->error_message;
            }
        }

        // 4. Paginated Inbox Events (Sprint 2)
        $inboxEvents = DB::table('external_inbox_events')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'inbox_page')
            ->withQueryString();

        return view('aliexpress.sync', [
            'imports'      => $imports,
            'search'       => $search,
            'totalCount'   => $totalCount,
            'successCount' => $successCount,
            'failedCount'  => $failedCount,
            'syncRuns'     => $syncRuns,
            'outboxEvents' => $outboxEvents,
            'outboxErrors' => $outboxErrors,
            'inboxEvents'  => $inboxEvents,
        ]);
    }

    /**
     * Run synchronization for a single product import record.
     */
    public function runSingle(int $id, AliExpressProductSyncer $syncer): JsonResponse
    {
        try {
            $import = AliExpressProductImport::findOrFail($id);

            $syncer->sync($import);

            // Reload import to get updated values
            $import->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تمت المزامنة بنجاح.',
                'status' => $import->status,
                'updated_at' => $import->updated_at->diffForHumans(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->error('AliExpress manual sync failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            $import = AliExpressProductImport::find($id);
            $errorMessage = $import ? ($import->error ?? $e->getMessage()) : $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => 'فشلت المزامنة: ' . $errorMessage,
                'status' => 'failed',
                'updated_at' => $import ? $import->updated_at->diffForHumans() : 'الآن',
                'error' => $errorMessage,
            ], 422);
        }
    }

    /**
     * Get all syncable import IDs.
     */
    public function getAllSyncable(): JsonResponse
    {
        $ids = AliExpressProductImport::where('status', 'success')
            ->whereNotNull('product_id')
            ->pluck('id');

        return response()->json([
            'ids' => $ids,
        ]);
    }

    /**
     * Replay a single failed/processed outbox event manually (Sprint 2).
     */
    public function replayOutbox(int $id, OutboxEventProcessor $processor): JsonResponse
    {
        try {
            DB::transaction(function () use ($id) {
                DB::table('domain_outbox_events')
                    ->where('id', $id)
                    ->update([
                        'status'   => 'pending',
                        'attempts' => 0,
                    ]);
            });

            // Trigger execution immediately
            $processor->processPending();

            // Fetch the updated event status
            $event = DB::table('domain_outbox_events')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'تمت إعادة تشغيل حدث الصادر بنجاح.',
                'status'  => $event ? $event->status : 'unknown',
            ]);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->error('AliExpress outbox event manual replay failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشلت إعادة تشغيل الحدث: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Replay a single failed/processed inbox event manually (Sprint 2).
     */
    public function replayInbox(int $id, InboxEventProcessor $processor): JsonResponse
    {
        try {
            DB::transaction(function () use ($id) {
                DB::table('external_inbox_events')
                    ->where('id', $id)
                    ->update([
                        'status'   => 'pending',
                        'attempts' => 0,
                    ]);
            });

            // Trigger execution immediately
            $processor->processPending();

            // Fetch the updated event status
            $event = DB::table('external_inbox_events')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'تمت إعادة معالجة حدث الوارد بنجاح.',
                'status'  => $event ? $event->status : 'unknown',
            ]);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->error('AliExpress inbox event manual replay failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشلت إعادة معالجة الحدث: ' . $e->getMessage(),
            ], 422);
        }
    }
}
