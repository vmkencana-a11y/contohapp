<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Models\Secret;
use App\Services\LoggingService;
use App\Services\SecretManager;
use App\Services\SecretRuntimeConfig;
use App\Support\SecretCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class SecretController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private LoggingService $logger,
        private SecretManager $secrets,
        private SecretRuntimeConfig $runtimeConfig,
    ) {
    }

    public function index(): View
    {
        $canManage = $this->currentAdmin()->hasPermission('secrets.manage');

        $secrets = Secret::query()
            ->orderBy('service')
            ->orderBy('secret_key')
            ->get();

        $configuredMap = $secrets->keyBy(fn (Secret $secret) => SecretCatalog::composeDefinitionValue($secret->service, $secret->secret_key));

        $serviceSummary = collect(SecretCatalog::definitions())->mapWithKeys(function (array $definition, string $service) use ($secrets) {
            $configured = $secrets->where('service', $service)->count();

            return [$service => [
                'label' => $definition['label'],
                'configured' => $configured,
                'expected' => count($definition['keys']),
            ]];
        });

        return view('admin.secrets.index', [
            'secrets' => $secrets,
            'serviceSummary' => $serviceSummary,
            'configuredMap' => $configuredMap,
            'canManage' => $canManage,
        ]);
    }

    public function create(): View
    {
        $usedDefinitions = Secret::query()
            ->get()
            ->map(fn (Secret $secret) => SecretCatalog::composeDefinitionValue($secret->service, $secret->secret_key))
            ->all();

        $definitions = collect(SecretCatalog::flatDefinitions())
            ->reject(fn (array $definition) => in_array($definition['value'], $usedDefinitions, true))
            ->values();

        return view('admin.secrets.create', [
            'definitions' => $definitions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $baseValidator = Validator::make($request->all(), [
            'definition' => ['required', 'string', Rule::in(collect(SecretCatalog::flatDefinitions())->pluck('value')->all())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($baseValidator->fails()) {
            return back()
                ->withErrors($baseValidator)
                ->withInput($request->except('value'));
        }

        $validated = $baseValidator->validated();

        try {
            ['service' => $service, 'secret_key' => $secretKey] = SecretCatalog::parseDefinitionValue($validated['definition']);
        } catch (InvalidArgumentException) {
            return back()
                ->with('error', 'Secret key tidak didukung.')
                ->withInput($request->except('value'));
        }

        $valueValidator = Validator::make($request->all(), [
            'value' => array_merge(['required'], SecretCatalog::valueRules($service, $secretKey)),
        ]);

        if ($valueValidator->fails()) {
            return back()
                ->withErrors($valueValidator)
                ->withInput($request->except('value'));
        }

        $validated['value'] = $valueValidator->validated()['value'];

        $existingSecret = Secret::withTrashed()
            ->where('service', $service)
            ->where('secret_key', $secretKey)
            ->first();

        if ($existingSecret && !$existingSecret->trashed()) {
            return back()
                ->withInput($request->except('value'))
                ->with('error', 'Secret untuk service dan key tersebut sudah ada.');
        }

        DB::transaction(function () use ($service, $secretKey, $validated, $adminId) {
            $secret = $this->secrets->upsert(
                $service,
                $secretKey,
                $validated['value'],
                (bool) ($validated['is_active'] ?? true),
                $adminId
            );

            $this->logger->logAdminActivity(
                $adminId,
                'secret.create',
                'Secret',
                (string) $secret->id,
                'Created secret entry',
                [
                    'service' => $service,
                    'secret_key' => $secretKey,
                ]
            );
        });

        $this->runtimeConfig->reload();

        return redirect()->route('admin.secrets.index')
            ->with('success', 'Secret berhasil dibuat.');
    }

    public function edit(Secret $secret): View
    {
        return view('admin.secrets.edit', [
            'secret' => $secret,
            'definition' => SecretCatalog::definition($secret->service, $secret->secret_key),
            'serviceLabel' => SecretCatalog::serviceLabel($secret->service),
        ]);
    }

    public function update(Request $request, Secret $secret): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $valueProvided = $request->filled('value') || $request->input('value') === '0';

        $validator = Validator::make($request->all(), [
            'is_active' => ['nullable', 'boolean'],
            'value' => ['nullable'],
        ]);

        $validator->sometimes('value', SecretCatalog::valueRules($secret->service, $secret->secret_key), fn () => $valueProvided);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('value'));
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($request, $secret, $validated, $adminId, $valueProvided) {
            if ($valueProvided) {
                $secret = $this->secrets->upsert(
                    $secret->service,
                    $secret->secret_key,
                    $validated['value'],
                    (bool) ($validated['is_active'] ?? $secret->is_active),
                    $adminId
                );
            } else {
                $secret = $this->secrets->updateMetadata(
                    $secret,
                    (bool) ($validated['is_active'] ?? $secret->is_active),
                    $adminId
                );
            }

            $this->logger->logAdminActivity(
                $adminId,
                'secret.update',
                'Secret',
                (string) $secret->id,
                'Updated secret entry',
                [
                    'service' => $secret->service,
                    'secret_key' => $secret->secret_key,
                ]
            );
        });

        $this->runtimeConfig->reload();

        return redirect()->route('admin.secrets.index')
            ->with('success', 'Secret berhasil diperbarui.');
    }

    public function destroy(Request $request, Secret $secret): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        DB::transaction(function () use ($secret, $adminId) {
            $this->logger->logAdminActivity(
                $adminId,
                'secret.delete',
                'Secret',
                (string) $secret->id,
                'Deleted secret entry',
                [
                    'service' => $secret->service,
                    'secret_key' => $secret->secret_key,
                ]
            );

            $this->secrets->delete($secret);
        });

        $this->runtimeConfig->reload();

        return redirect()->route('admin.secrets.index')
            ->with('success', 'Secret berhasil dihapus.');
    }

    public function refreshCache(Request $request): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);
        $this->runtimeConfig->reload();

        $this->logger->logAdminActivity(
            $adminId,
            'secret.refresh_cache',
            'Secret',
            'global',
            'Reloaded secret cache'
        );

        return redirect()->route('admin.secrets.index')
            ->with('success', 'Cache secret berhasil direfresh.');
    }
}
