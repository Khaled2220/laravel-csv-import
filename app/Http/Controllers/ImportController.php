<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Models\Import;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Requests\ShowImportRequest;
use App\Services\ImportService;



class ImportController extends Controller
{
    public function index()
    {
        return view('imports.index');
    }
      public function store(ImportCsvRequest $request)
    {
        $file = $request->file('csv_file');

        
        $this->importservice->createImport(
        $file,
        $request->user()->id
        );
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

        return view('imports.history', [
            'imports' => $imports,
    ]);
    }

    public function show(ShowImportRequest $request,Import $import)
    {
        $errors = $import->errors()
            ->latest()
            ->paginate(20);

        return view('imports.show',[
            'import' => $import,
            'errors' => $errors,
            ]);
    }
    public function __construct(private ImportService $importservice 
    ){

    }
}
