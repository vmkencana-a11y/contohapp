<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlacklistTypeEnum;
use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Models\RegistrationBlacklist;
use App\Services\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlacklistController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private LoggingService $logger
    ) {}

    /**
     * Display list of blacklist entries.
     */
    public function index(Request $request): View
    {
        $query = RegistrationBlacklist::with('creator')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Search by value
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where('value', 'like', '%' . $search . '%');
        }

        $entries = $query->paginate(20)->withQueryString();
        $types = BlacklistTypeEnum::cases();

        return view('admin.blacklist.index', [
            'entries' => $entries,
            'types' => $types,
            'currentType' => $request->type,
            'search' => $request->search,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('admin.blacklist.create', [
            'types' => BlacklistTypeEnum::cases(),
        ]);
    }

    /**
     * Store new blacklist entry.
     */
    public function store(Request $request): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(BlacklistTypeEnum::class)],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        // Normalize value
        $validated['value'] = strtolower(trim($validated['value']));

        // Check for duplicate
        $exists = RegistrationBlacklist::where('type', $validated['type'])
            ->byValue($validated['value'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['value' => 'Entry dengan tipe dan nilai ini sudah ada.']);
        }

        $entry = RegistrationBlacklist::create([
            'type' => $validated['type'],
            'value' => $validated['value'],
            'reason' => $validated['reason'],
            'expires_at' => $validated['expires_at'],
            'created_by' => $adminId,
        ]);

        $this->logger->logAdminActivity(
            $adminId,
            'blacklist.create',
            'RegistrationBlacklist',
            (string)$entry->id,
            "Added {$entry->type->value}: {$entry->value}"
        );

        return redirect()->route('admin.blacklist.index')
            ->with('success', 'Blacklist entry berhasil ditambahkan.');
    }

    /**
     * Show edit form.
     */
    public function edit(RegistrationBlacklist $blacklist): View
    {
        return view('admin.blacklist.edit', [
            'entry' => $blacklist,
            'types' => BlacklistTypeEnum::cases(),
        ]);
    }

    /**
     * Update blacklist entry.
     */
    public function update(Request $request, RegistrationBlacklist $blacklist): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(BlacklistTypeEnum::class)],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date'],
        ]);

        // Normalize value
        $validated['value'] = strtolower(trim($validated['value']));

        // Check for duplicate (exclude current)
        $exists = RegistrationBlacklist::where('type', $validated['type'])
            ->byValue($validated['value'])
            ->where('id', '!=', $blacklist->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['value' => 'Entry dengan tipe dan nilai ini sudah ada.']);
        }

        $blacklist->update($validated);

        $this->logger->logAdminActivity(
            $adminId,
            'blacklist.update',
            'RegistrationBlacklist',
            (string)$blacklist->id,
            "Updated {$blacklist->type->value}: {$blacklist->value}"
        );

        return redirect()->route('admin.blacklist.index')
            ->with('success', 'Blacklist entry berhasil diperbarui.');
    }

    /**
     * Delete blacklist entry.
     */
    public function destroy(RegistrationBlacklist $blacklist): RedirectResponse
    {
        $adminId = $this->currentAdminId();

        $type = $blacklist->type->value;
        $value = $blacklist->value;

        $blacklist->delete();

        $this->logger->logAdminActivity(
            $adminId,
            'blacklist.delete',
            'RegistrationBlacklist',
            (string)$blacklist->id,
            "Deleted {$type}: {$value}"
        );

        return redirect()->route('admin.blacklist.index')
            ->with('success', 'Blacklist entry berhasil dihapus.');
    }
}
