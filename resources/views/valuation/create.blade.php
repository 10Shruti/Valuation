<x-app-layout>
    @section('page_title', 'Create Valuation')

    <div class="max-w-4xl mx-auto mt-6 px-4 pb-10">
        <div class="bg-white p-8 rounded-xl shadow-lg border border-slate-200">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-3 border-gray-200">Create New Valuation</h2>

            <form action="{{ route('valuation.store') }}"  method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="grid grid-cols-1 gap-6 mb-8">
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Customer Name</label>
                        <input type="text" id="name" name="name" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Address Field -->
                    <div>
                        <label for="address" class="block text-sm font-bold text-gray-700 mb-1">Address</label>
                        <textarea id="address" name="address" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- Date Field -->
                    <div>
                        <label for="valuation_date" class="block text-sm font-bold text-gray-700 mb-1">Valuation Date</label>
                        <input type="date" id="valuation_date" name="valuation_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required class="block w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Dynamic Items Section (Alpine.js) -->
                <div x-data="{ items: [{ id: Date.now(), preview: null }] }" class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 border-gray-200">Jewelry Items (Images & Weights)</h3>
                    
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-4 p-3 border border-gray-200 rounded-lg bg-gray-50 shadow-sm">
                            
                            <!-- Numbering Badge -->
                            <div class="w-6 h-6 flex items-center justify-center bg-gray-800 text-white rounded-full text-xs font-bold flex-shrink-0" x-text="index + 1"></div>

                            <div class="w-full sm:w-1/4">
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Gross Weight (Grams)</label>
                                <input type="number" step="0.001" :name="`items[${index}][grams]`" required placeholder="00.000" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div class="w-full sm:w-2/5">
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Image</label>
                                <input type="file" :name="`items[${index}][image]`" accept="image/*" required 
                                    @change="const file = $event.target.files[0]; if(file){ const reader = new FileReader(); reader.onload = (e) => item.preview = e.target.result; reader.readAsDataURL(file); } else { item.preview = null; }"
                                    class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div class="w-16 h-16 sm:w-14 sm:h-14 flex-shrink-0 bg-white border border-gray-300 rounded flex items-center justify-center overflow-hidden">
                                <template x-if="item.preview">
                                    <img :src="item.preview" class="object-cover w-full h-full">
                                </template>
                                <template x-if="!item.preview">
                                    <span class="text-gray-400 text-[10px] text-center leading-tight">No<br>Image</span>
                                </template>
                            </div>

                            <div class="pt-1 sm:pt-0 ml-auto sm:ml-0">
                                <button type="button" @click="items = items.filter(i => i.id !== item.id)" x-show="items.length > 1" class="text-red-600 hover:text-red-800 font-bold px-2 py-1 text-sm bg-red-100 rounded-md hover:bg-red-200 transition">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="items.push({ id: Date.now(), preview: null })" class="mt-2 bg-indigo-100 text-indigo-700 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-indigo-200 transition shadow-sm">
                        ➕ Add More Items
                    </button>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <button type="reset" class="bg-slate-200 text-slate-700 px-6 py-2.5 rounded-lg font-bold uppercase text-sm shadow-sm hover:bg-slate-300 transition">
                        ↺ Reset
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg font-bold uppercase text-sm shadow-md hover:bg-blue-700 transition">
                        Next ➔
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>