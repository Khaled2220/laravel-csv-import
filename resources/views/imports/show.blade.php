<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Import Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4">
                <a
                    href="{{ route('imports.history') }}"
                    class="text-blue-600 hover:underline"
                >
                    ← Back to Import History
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h3 class="text-lg font-semibold mb-6">
                        {{ $import->file_name }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <strong>Status:</strong>
                            {{ ucfirst($import->status) }}
                        </div>

                        <div>
                            <strong>File Name:</strong>
                            {{ $import->file_name }}
                        </div>

                        <div>
                            <strong>Total Records:</strong>
                            {{ $import->total_records }}
                        </div>

                        <div>
                            <strong>Processed Records:</strong>
                            {{ $import->processed_records }}
                        </div>

                        <div>
                            <strong>Failed Records:</strong>
                            {{ $import->failed_records }}
                        </div>

                        <div>
                            <strong>Started At:</strong>
                            {{ $import->started_at?->format('Y-m-d H:i:s') ?? '-' }}
                        </div>

                        <div>
                            <strong>Completed At:</strong>
                            {{ $import->completed_at?->format('Y-m-d H:i:s') ?? '-' }}
                        </div>

                    </div>

                    @if ($import->error_message)
                        <div class="mt-6 p-4 bg-red-100 text-red-800 rounded">
                            <strong>Import Error:</strong>
                            {{ $import->error_message }}
                        </div>
                    @endif

                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h3 class="text-lg font-semibold mb-6">
                        Import Errors
                    </h3>

                    @if ($errors->count())

                        <div class="overflow-x-auto">
                            <table class="min-w-full border">

                                <thead>
                                    <tr class="bg-gray-100">

                                        <th class="px-4 py-3 text-left border">
                                            Row
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Data
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Error
                                        </th>

                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($errors as $error)

                                        <tr>

                                            <td class="px-4 py-3 border">
                                                {{ $error->row_number }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                <pre class="text-sm">{{ json_encode($error->row_data, JSON_PRETTY_PRINT) }}</pre>
                                            </td>

                                            <td class="px-4 py-3 border text-red-600">
                                                {{ $error->error_message }}
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $errors->links() }}
                        </div>

                    @else

                        <p class="text-gray-600">
                            No errors found.
                        </p>

                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>

