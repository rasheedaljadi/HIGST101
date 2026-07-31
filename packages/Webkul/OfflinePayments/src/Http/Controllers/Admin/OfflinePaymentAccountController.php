<?php

namespace Webkul\OfflinePayments\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\OfflinePayments\DataGrids\OfflinePaymentAccountDataGrid;
use Webkul\OfflinePayments\Http\Requests\OfflinePaymentAccountRequest;
use Webkul\OfflinePayments\Repositories\OfflinePaymentAccountRepository;
use Webkul\OfflinePayments\Repositories\OfflinePaymentDestinationRepository;

class OfflinePaymentAccountController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OfflinePaymentAccountRepository $accountRepository,
        protected OfflinePaymentDestinationRepository $destinationRepository,
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(OfflinePaymentAccountDataGrid::class)->toJson();
        }

        return view('offline_payments::admin.settings.offline_accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $channels = $this->channelRepository->all();
        $currencies = $this->currencyRepository->all();

        return view('offline_payments::admin.settings.offline_accounts.create', compact('channels', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(OfflinePaymentAccountRequest $request)
    {
        $data = $request->validated();

        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        if ($request->hasFile('logo_path')) {
            $data['logo_path'] = $request->file('logo_path')->store('offline_payment_accounts', 'public');
        }

        if (empty($data['code'])) {
            $data['code'] = 'offline_'.Str::random(8);
        }

        Event::dispatch('sales.offline_payment_account.create.before');

        DB::transaction(function () use ($data, $request) {
            $account = $this->accountRepository->create($data);

            if (! empty($request->input('destinations'))) {
                foreach ($request->input('destinations') as $destData) {
                    $destData['offline_payment_account_id'] = $account->id;
                    $destData['is_active'] = isset($destData['is_active']) ? (bool) $destData['is_active'] : true;
                    $this->destinationRepository->create($destData);
                }
            }

            Event::dispatch('sales.offline_payment_account.create.after', $account);
        });

        session()->flash('success', trans('offline_payments::app.admin.responses.create-success'));

        return redirect()->route('admin.settings.offline_accounts.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id)
    {
        $account = $this->accountRepository->with(['destinations.currency'])->findOrFail($id);
        $channels = $this->channelRepository->all();
        $currencies = $this->currencyRepository->all();

        return view('offline_payments::admin.settings.offline_accounts.edit', compact('account', 'channels', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(OfflinePaymentAccountRequest $request, $id)
    {
        $account = $this->accountRepository->findOrFail($id);
        $data = $request->validated();

        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        if ($request->hasFile('logo_path')) {
            if ($account->logo_path) {
                Storage::disk('public')->delete($account->logo_path);
            }
            $data['logo_path'] = $request->file('logo_path')->store('offline_payment_accounts', 'public');
        }

        Event::dispatch('sales.offline_payment_account.update.before', $id);

        DB::transaction(function () use ($data, $request, $account, $id) {
            $this->accountRepository->update($data, $id);

            $existingIds = [];
            if (! empty($request->input('destinations'))) {
                foreach ($request->input('destinations') as $destData) {
                    $destData['offline_payment_account_id'] = $account->id;
                    $destData['is_active'] = isset($destData['is_active']) ? (bool) $destData['is_active'] : true;

                    if (! empty($destData['id'])) {
                        $this->destinationRepository->update($destData, $destData['id']);
                        $existingIds[] = $destData['id'];
                    } else {
                        $newDest = $this->destinationRepository->create($destData);
                        $existingIds[] = $newDest->id;
                    }
                }
            }

            $account->destinations()->whereNotIn('id', $existingIds)->delete();

            Event::dispatch('sales.offline_payment_account.update.after', $account);
        });

        session()->flash('success', trans('offline_payments::app.admin.responses.update-success'));

        return redirect()->route('admin.settings.offline_accounts.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $account = $this->accountRepository->findOrFail($id);

        try {
            Event::dispatch('sales.offline_payment_account.delete.before', $id);

            if ($account->logo_path) {
                Storage::disk('public')->delete($account->logo_path);
            }

            $this->accountRepository->delete($id);

            Event::dispatch('sales.offline_payment_account.delete.after', $id);

            return new JsonResponse([
                'message' => trans('offline_payments::app.admin.responses.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('offline_payments::app.admin.responses.delete-failed'),
            ], 500);
        }
    }

    /**
     * Toggle the status of the account.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateStatus($id)
    {
        $account = $this->accountRepository->findOrFail($id);

        $account->is_active = ! $account->is_active;
        $account->save();

        return new JsonResponse([
            'message' => trans('offline_payments::app.admin.responses.status-updated'),
        ]);
    }
}
