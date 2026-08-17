<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;



class ImportController extends Controller
{
    public function index()
    {
        return view('imports.index');
    }
      public function store(Request $request)
    {
        $validated = $request->validate([
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ]);

        $file = $validated['csv_file'];

        /*
         * Save the uploaded CSV file.
         */
        $filePath = $file->store('imports','local');

        /*
         * Create import history record.
         */
        $import = Import::create([
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => 0,
            'processed_records' => 0,
            'failed_records' => 0,
        ]);

        /*
         * Process the CSV in the background.
         */
        ImportUsersJob::dispatch($import->id);

        return redirect()
            ->route('imports.index')
            ->with(
                'success',
                'CSV import started successfully.'
            );
    }
      public function history()
    {
        $imports = Import::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('imports.history', compact('imports'));
    }

    public function show(Import $import)
    {
        abort_unless($import->user_id === auth()->id(), 403);

        $errors = $import->errors()
            ->latest()
            ->paginate(20);

        return view('imports.show', compact('import', 'errors'));
    }
}
