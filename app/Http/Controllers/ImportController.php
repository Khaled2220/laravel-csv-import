<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(private ImportService $importservice) 
    {
        //
    }

    /**
     * Display the import page.
     */
    public function index(): View
    {
        return view('imports.index');
    }

    /**
     * Store a new CSV import.
     */
    public function store(ImportCsvRequest $request): RedirectResponse
    {
        $this->importservice->createImport(
            $request->file('csv_file'),
            $request->user()->id
        );

        return redirect()->route('imports.index')
            ->with('success', 'CSV import started successfully.');
    }

    /**
     * Display import history.
     */
    public function history(Request $request): View
    {
        $imports = Import::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('imports.history', ['imports' => $imports,]);
    }

    /**
     * Display a specific import.
     */
    public function show(Import $import): View
    {
        $errors = $import->errors()
            ->latest()
            ->paginate(20);

        return view('imports.show', ['import' => $import,'errors' => $errors,]);
    }

    /**
     * Cancel an import.
     */
    public function cancel(Import $import): RedirectResponse
    {
        $this->importservice->cancelImport($import);

        return redirect()->route('imports.history')
            ->with('success', 'Import cancelled successfully.');
    }

    /**
     * Retry a failed import.
     */
    public function retry(Import $import): RedirectResponse
    {
        $this->importservice->retryImport($import);

        return redirect()->route('imports.history')
            ->with('success', 'Import retry started successfully.');
    }
}
