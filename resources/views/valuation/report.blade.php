<x-app-layout>
    @section('page_title', 'Valuation Reports')

    <!-- Flatpickr for Date Formatting -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <div class="container mx-auto py-6">
        <!-- Search Bar Section -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <div class="text-slate-600 font-bold text-sm">
                Total Found: <span class="text-blue-600">{{ $valuations->count() }}</span>
            </div>
            
            <form action="{{ url()->current() }}" method="GET" class="flex w-full sm:w-auto gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, address..." class="w-full sm:w-64 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-black">
                <input type="text" id="date-filter" name="date" value="{{ request('date') }}" placeholder="DD-MM-YYYY" class="w-full sm:w-36 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-black bg-white" title="Filter by Valuation Date">
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-bold text-sm shadow hover:bg-blue-700 transition">
                    🔍 Search
                </button>
                @if(request('search') || request('date'))
                    <a href="{{ url()->current() }}" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg font-bold text-sm shadow-sm hover:bg-slate-300 transition flex items-center">
                        ✕ Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto bg-slate-900 rounded-lg shadow-xl border border-slate-800">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-slate-300 uppercase text-[11px] font-bold tracking-widest">
                        <th class="px-6 py-4 w-12 text-center">#</th>
                        <th class="px-6 py-4">Customer Name</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Valuation Date</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-300 divide-y divide-slate-800">
                    @forelse($valuations as $valuation)
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="px-6 py-4 text-center text-slate-500 font-mono">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 font-medium">{{ $valuation->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-400 whitespace-pre-wrap">{{ $valuation->address ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-mono">{{ \Carbon\Carbon::parse($valuation->valuation_date)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-4">
                                <a href="{{ route('valuation.pdf', $valuation->id) }}" class="hover:scale-125 transition-transform" title="View PDF">
                                    📄
                                </a>
                                
                                <a href="{{ route('valuation.edit', $valuation->id) }}" class="hover:scale-125 transition-transform" title="Edit">
                                    ✏️
                                </a>

                                <form action="{{ route('valuation.duplicate', $valuation->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="hover:scale-125 transition-transform" title="Duplicate">
                                        👯
                                    </button>
                                </form>

                                <form action="{{ route('valuation.destroy', $valuation->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this valuation permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hover:scale-125 transition-transform" title="Delete">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">No valuations found matching your search.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#date-filter", {
                dateFormat: "d-m-Y",
                allowInput: true
            });
        });
    </script>
</x-app-layout>