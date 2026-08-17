<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Import History
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">
                            Previous Imports
                        </h3>

                        <a
                            href="{{ route('imports.index') }}"
                            class="px-4 py-2 bg-gray-800 text-white rounded"
                        >
                            New Import
                        </a>
                    </div>

                    @if ($imports->count())

                        <div class="overflow-x-auto">
                            <table class="min-w-full border">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-3 text-left border">
                                            File Name
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Status
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Total
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Processed
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Failed
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Started
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Completed
                                        </th>

                                        <th class="px-4 py-3 text-left border">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($imports as $import)
                                        <tr>
                                            <td class="px-4 py-3 border">
                                                {{ $import->file_name }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ ucfirst($import->status) }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ $import->total_records }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ $import->processed_records }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ $import->failed_records }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ $import->started_at?->format('Y-m-d H:i:s') ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                {{ $import->completed_at?->format('Y-m-d H:i:s') ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 border">
                                                <a
                                                    href="{{ route('imports.show', $import) }}"
                                                    class="text-blue-600 hover:underline"
                                                >
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $imports->links() }}
                        </div>

                    @else

                        <p class="text-gray-600">
                            No imports found.
                        </p>

                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>