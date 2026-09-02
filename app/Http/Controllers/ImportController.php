<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Models\Import;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Requests\ShowImportRequest;
use App\Services\ImportService;
use Illuminate\Http\Request;



class ImportController extends Controller
{
    public function __construct(
        private ImportService $importservice){
            //
        }
    public function index()
    {
        return view('imports.index');
    }
      public function store(ImportCsvRequest $request): RedirectResponse
    {
        //$file = $request->file('csv_file');
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
      public function history(Request $request):
    View {

       $imports = Import::where(
        'user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('imports.history', [
            'imports' => $imports,
        ]);
        
    }

    public function show(Request $request,Import $import):
    view{
        $errors = $import->errors()
            ->latest()
            ->paginate(20);

        return view('imports.show',[
            'import' => $import,
            'errors' => $errors,
            ]);
    }
}
