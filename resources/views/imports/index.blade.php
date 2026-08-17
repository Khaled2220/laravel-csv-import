<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CSV Imports
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h3 class="text-lg font-semibold mb-4">
                        Import Users from CSV
                    </h3>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('imports.store') }}"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        <div class="mb-4">
                            <label for="csv_file" class="block font-medium text-sm text-gray-700">
                                CSV File
                            </label>

                            <input
                                id="csv_file"
                                type="file"
                                name="csv_file"
                                accept=".csv,text/csv"
                                required
                                class="mt-1 block w-full"
                            >
                        </div>

                       <div class="flex items-center gap-3">
                       <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded" >
                        Start Import
                       </button>
                       <a href="{{ route('imports.history') }}" class="px-4 py-2 bg-blue-600 text-white rounded">
                        Import History
                       </a>
                       </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>