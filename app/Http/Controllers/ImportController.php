<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImportService $importservice
    ) {
        //
    }

    public function index(): View
    {
        return view('imports.index');
    }

    public function store(
        ImportCsvRequest $request
    ): RedirectResponse {
        $this->importservice->createImport(
            $request->file('csv_file'),
            $request->user()->id
        );

        return redirect()
            ->route('imports.index')
            ->with(
                'success',
                'CSV import started successfully.'
            );
    }

    public function history(
        Request $request
    ): View {
        $imports = Import::where(
            'user_id',
            $request->user()->id
        )
            ->latest()
            ->paginate(10);

        return view('imports.history', [
            'imports' => $imports,
        ]);
    }

    public function show(
        Request $request,
        Import $import
    ): View {
        $this->authorize('view', $import);

        $errors = $import->errors()
            ->latest()
            ->paginate(20);

        return view('imports.show', [
            'import' => $import,
            'errors' => $errors,
        ]);
    }

    public function cancel(
        Import $import
    ): RedirectResponse {
        $this->authorize('cancel', $import);

        $this->importservice->cancelImport($import);

        return redirect()
            ->route('imports.history')
            ->with(
                'success',
                'Import cancelled successfully.'
            );
    }

    public function retry(
        Import $import
    ): RedirectResponse {
        $this->authorize('retry', $import);

        $this->importservice->retryImport($import);

        return redirect()
            ->route('imports.history')
            ->with(
                'success',
                'Import retry started successfully.'
            );
    }
}