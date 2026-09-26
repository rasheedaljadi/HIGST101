<?php

namespace Webkul\MobileApi\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\MobileApi\DataGrids\MobileApiKeyDataGrid;
use Webkul\MobileApi\Repositories\MobileApiKeyRepository;

class KeyController extends Controller
{
    public function __construct(
        protected MobileApiKeyRepository $mobileApiKeyRepository,
        protected ChannelRepository $channelRepository
    ) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(MobileApiKeyDataGrid::class)->process();
        }

        $channels = $this->channelRepository->all();

        return view('mobile_api::admin.keys.index', compact('channels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'channel_id' => 'nullable|exists:channels,id',
        ]);

        $this->mobileApiKeyRepository->generateKey(
            $request->input('name'),
            $request->input('channel_id') ? (int) $request->input('channel_id') : null
        );

        session()->flash('success', trans('mobile_api::app.admin.keys.create-success'));

        return redirect()->route('admin.settings.mobile_api_keys.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->mobileApiKeyRepository->delete($id);

            return response()->json([
                'message' => trans('mobile_api::app.admin.keys.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
