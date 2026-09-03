<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Import Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Back --}}
            <div class="mb-4">
                <a
                    href="{{ route('imports.history') }}"
                    class="text-blue-600 hover:underline"
                >
                    ← Back to Import History
                </a>
            </div>

            {{-- Import Details --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    {{-- Header + Actions --}}
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

                        <h3 class="text-lg font-semibold">
                            {{ $import->file_name }}
                        </h3>

                        <div class="flex gap-2">

                            {{-- Cancel --}}
                            @can('cancel', $import)
                                <form
                                    method="POST"
                                    action="{{ route('imports.cancel', $import) }}"
                                    onsubmit="return confirm('Are you sure you want to cancel this import?');"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                                    >
                                        Cancel Import
                                    </button>
                                </form>
                            @endcan

                            {{-- Retry --}}
                            @can('retry', $import)
                                <form
                                    method="POST"
                                    action="{{ route('imports.retry', $import) }}"
                                    onsubmit="return confirm('Are you sure you want to retry this import?');"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
                                    >
                                        Retry Import
                                    </button>
                                </form>
                            @endcan

                        </div>
                    </div>

                    {{-- Import Information --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <strong>Status:</strong>

                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'cancelled' => 'bg-gray-100 text-gray-800',
                                ];
                            @endphp

                            <span
                                class="inline-block px-2 py-1 rounded text-sm
                                {{ $statusClasses[$import->status] ?? 'bg-gray-100 text-gray-800' }}"
                            >
                                {{ ucfirst($import->status) }}
                            </span>
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

                        <div>
                            <strong>Batch ID:</strong>
                            {{ $import->batch_id ?? '-' }}
                        </div>

                    </div>

                    {{-- Progress --}}
                    @if ($import->total_records > 0)

                        @php
                            $completedRecords =
                                $import->processed_records +
                                $import->failed_records;

                            $progress =
                                ($completedRecords / $import->total_records) * 100;

                            $progress = min(100, max(0, $progress));
                        @endphp

                        <div class="mt-6">

                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">
                                    Progress
                                </span>

                                <span>
                                    {{ number_format($progress, 1) }}%
                                </span>
                            </div>

                            <div class="w-full bg-gray-200 rounded-full h-4">

                                <div
                                    class="bg-blue-600 h-4 rounded-full"
                                    style="width: {{ $progress }}%"
                                ></div>

                            </div>

                            <div class="mt-2 text-sm text-gray-600">
                                {{ $completedRecords }}
                                of
                                {{ $import->total_records }}
                                records processed
                            </div>

                        </div>

                    @endif

                    {{-- Import Error --}}
                    @if ($import->error_message)

                        <div class="mt-6 p-4 bg-red-100 text-red-800 rounded">

                            <strong>Import Error:</strong>
                            {{ $import->error_message }}

                        </div>

                    @endif

                </div>
            </div>

            {{-- Import Errors --}}
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