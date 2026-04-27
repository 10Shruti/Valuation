<?php

namespace App\Http\Controllers;

use App\Models\Valuation;
use App\Models\ValuationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ValuationController extends Controller
{
    private function getStorageFolder(Valuation $valuation)
    {
        // Remove ' (Copy)' so duplicated valuations stay in the original customer's folder
        $cleanName = str_replace(' (Copy)', '', $valuation->name);

        // Remove special characters to create a safe folder name (keeps spaces, letters, and numbers)
        $safeName = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanName));
        $date = Carbon::parse($valuation->valuation_date)->format('d-m-Y');
        
        return "valuations/{$safeName}/{$date}";
    }

    public function index(Request $request)
    {
        $query = Valuation::latest();

        // Check if the user submitted a search query
        if ($request->filled('search')) {
            $search = $request->input('search');
            
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
                  
                if (preg_match('/^\d{2}[\/\-]\d{2}[\/\-]\d{4}$/', $search)) {
                    $searchDate = Carbon::parse(str_replace('/', '-', $search))->format('Y-m-d');
                    $q->orWhere('valuation_date', $searchDate);
                } else {
                    $q->orWhere('valuation_date', 'like', "%{$search}%");
                }
            });
        }

        // Check if the user submitted a specific date filter
        if ($request->filled('date')) {
            try {
                $filterDate = Carbon::parse(str_replace('/', '-', $request->input('date')))->format('Y-m-d');
                $query->whereDate('valuation_date', $filterDate);
            } catch (\Exception $e) {
                $query->whereDate('valuation_date', $request->input('date'));
            }
        }

        $valuations = $query->get();
        return view('valuation.report', compact('valuations'));
    }

    public function report(Request $request)
    {
        // Pass the request along to the index method so search works on both routes
        return $this->index($request);
    }

    public function create()
    {
        return view('valuation.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'valuation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'items.*.grams' => 'required|numeric|min:0',
        ]);

        $valuation = Valuation::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'valuation_date' => $validated['valuation_date'],
        ]);

        if ($request->has('items')) {
            $itemsData = $request->input('items');
            $itemsFiles = $request->file('items');
            
            $sortOrder = 0;
            $folder = $this->getStorageFolder($valuation);

            foreach ($itemsData as $index => $itemData) {
                if (isset($itemsFiles[$index]['image'])) {
                    // Store image
                    $imagePath = $itemsFiles[$index]['image']->store($folder, 'public');
                    
                    // Create relation
                    $valuation->items()->create([
                        'image_path' => $imagePath,
                        'grams' => number_format((float) $itemData['grams'], 3, '.', ''),
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        return redirect()->route('valuation.show', $valuation->id)
                         ->with('success', 'Valuation created successfully!');
    }

    public function show(Valuation $valuation)
    {
        // Load items ordered by their sort_order
        $items = $valuation->items()->orderBy('sort_order')->orderBy('id')->get();
        
        // Chunk items by 6 to fit neatly onto A4 pages for printing
        $chunks = $items->chunk(6);
        $isPdfView = false;
        
        return view('valuation.store', compact('valuation', 'chunks', 'isPdfView'));
    }

    public function edit(Valuation $valuation)
    {
        return view('valuation.edit', compact('valuation'));
    }

    public function update(Request $request, Valuation $valuation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'valuation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'items.*.grams' => 'required|numeric|min:0',
        ]);

        $oldFolder = $this->getStorageFolder($valuation);
        $oldName = $valuation->name;
        $oldDate = $valuation->valuation_date;

        $valuation->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'valuation_date' => $validated['valuation_date'],
        ]);

        $folder = $this->getStorageFolder($valuation);

        // Move existing files if the folder changed
        if ($oldFolder !== $folder) {
            foreach ($valuation->items as $item) {
                if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                    $newImagePath = $folder . '/' . basename($item->image_path);
                    Storage::disk('public')->move($item->image_path, $newImagePath);
                    $item->image_path = $newImagePath;
                    $item->save();
                }
            }

            // Move PDF if it exists
            $oldPdfName = Str::slug($oldName) . '-' . Carbon::parse($oldDate)->format('d-m-Y') . '.pdf';
            $oldPdfPath = $oldFolder . '/' . $oldPdfName;
            $newPdfName = Str::slug($valuation->name) . '-' . Carbon::parse($valuation->valuation_date)->format('d-m-Y') . '.pdf';
            $newPdfPath = $folder . '/' . $newPdfName;

            if (Storage::disk('public')->exists($oldPdfPath)) {
                Storage::disk('public')->move($oldPdfPath, $newPdfPath);
            }

            // Clean up the old folder if it's empty
            if (Storage::disk('public')->exists($oldFolder) && empty(Storage::disk('public')->files($oldFolder)) && empty(Storage::disk('public')->directories($oldFolder))) {
                Storage::disk('public')->deleteDirectory($oldFolder);
            }
        }

        // Process Deleted Items
        if ($request->has('deleted_ids') && is_array($request->input('deleted_ids'))) {
            $itemsToDelete = ValuationItem::whereIn('id', $request->input('deleted_ids'))->where('valuation_id', $valuation->id)->get();
            foreach ($itemsToDelete as $item) {
                Storage::disk('public')->delete($item->image_path);
                $item->delete();
            }
        }

        // Process Updated and New Items
        if ($request->has('items')) {
            $sortOrder = (int) $valuation->items()->max('sort_order') + 1;

            foreach ($request->input('items') as $id => $data) {
                if (str_starts_with($id, 'new_') && $request->hasFile("items.{$id}.image")) {
                    $valuation->items()->create([
                        'image_path' => $request->file("items.{$id}.image")->store($folder, 'public'),
                        'grams' => number_format((float) $data['grams'], 3, '.', ''),
                        'sort_order' => $sortOrder++,
                    ]);
                } else {
                    $item = ValuationItem::where('id', $id)->where('valuation_id', $valuation->id)->first();
                    if ($item) {
                        $item->grams = number_format((float) $data['grams'], 3, '.', '');
                        if ($request->hasFile("items.{$id}.image")) {
                            Storage::disk('public')->delete($item->image_path);
                            $item->image_path = $request->file("items.{$id}.image")->store($folder, 'public');
                        }
                        $item->save();
                    }
                }
            }
        }

        return redirect()->route('valuation.show', $valuation->id)
                         ->with('success', 'Valuation updated successfully!');
    }

    public function destroy(Valuation $valuation)
    {
        $folder = $this->getStorageFolder($valuation);

        // Delete physical images first
        foreach ($valuation->items as $item) {
            Storage::disk('public')->delete($item->image_path);
            $item->delete(); // Delete the item record from the database
        }
        
        // Delete PDF if it exists
        $pdfName = Str::slug($valuation->name) . '-' . Carbon::parse($valuation->valuation_date)->format('d-m-Y') . '.pdf';
        Storage::disk('public')->delete($folder . '/' . $pdfName);

        // Clean up the empty folder
        if (Storage::disk('public')->exists($folder) && empty(Storage::disk('public')->files($folder)) && empty(Storage::disk('public')->directories($folder))) {
            Storage::disk('public')->deleteDirectory($folder);
        }

        $valuation->delete(); // Delete the main valuation record

        return redirect()->route('valuation.index')->with('success', 'Valuation permanently deleted!');
    }

    public function updateAjax(Request $request, Valuation $valuation)
    {
        $oldFolder = $this->getStorageFolder($valuation);
        $oldName = $valuation->name;
        $oldDate = $valuation->valuation_date;

        $valuation->update([
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'valuation_date' => Carbon::parse(str_replace('/', '-', $request->input('valuation_date')))->format('Y-m-d'),
        ]);

        $folder = $this->getStorageFolder($valuation);

        // Move existing files if the folder changed
        if ($oldFolder !== $folder) {
            foreach ($valuation->items as $item) {
                if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                    $newImagePath = $folder . '/' . basename($item->image_path);
                    Storage::disk('public')->move($item->image_path, $newImagePath);
                    $item->image_path = $newImagePath;
                    $item->save();
                }
            }

            // Move PDF if it exists
            $oldPdfName = Str::slug($oldName) . '-' . Carbon::parse($oldDate)->format('d-m-Y') . '.pdf';
            $oldPdfPath = $oldFolder . '/' . $oldPdfName;
            $newPdfName = Str::slug($valuation->name) . '-' . Carbon::parse($valuation->valuation_date)->format('d-m-Y') . '.pdf';
            $newPdfPath = $folder . '/' . $newPdfName;

            if (Storage::disk('public')->exists($oldPdfPath)) {
                Storage::disk('public')->move($oldPdfPath, $newPdfPath);
            }

            // Clean up the old folder if it's empty
            if (Storage::disk('public')->exists($oldFolder) && empty(Storage::disk('public')->files($oldFolder)) && empty(Storage::disk('public')->directories($oldFolder))) {
                Storage::disk('public')->deleteDirectory($oldFolder);
            }
        }

        // Process Deleted Items
        if ($request->has('deleted_ids') && is_array($request->input('deleted_ids'))) {
            $itemsToDelete = ValuationItem::whereIn('id', $request->input('deleted_ids'))->get();
            foreach ($itemsToDelete as $item) {
                Storage::disk('public')->delete($item->image_path);
                $item->delete();
            }
        }

        // Process Updated Grams
        if ($request->has('items') && is_array($request->input('items'))) {

            foreach ($request->input('items') as $id => $data) {
                if (str_starts_with($id, 'new_')) {
                    // Safely insert new item that was appended in edit mode
                    if ($request->hasFile("items.{$id}.image")) {
                        $valuation->items()->create([
                            'image_path' => $request->file("items.{$id}.image")->store($folder, 'public'),
                            'grams' => number_format((float) ($data['grams'] ?? 0), 3, '.', ''),
                            'sort_order' => $data['sort_order'] ?? 0,
                        ]);
                    }
                } else {
                    $item = ValuationItem::find($id);
                    if ($item) {
                        if (isset($data['grams'])) {
                            $item->grams = number_format((float) $data['grams'], 3, '.', '');
                        }
                        
                        if (isset($data['sort_order'])) {
                            $item->sort_order = $data['sort_order'];
                        }
                        
                        if ($request->hasFile("items.{$id}.image")) {
                            Storage::disk('public')->delete($item->image_path); // Delete old image
                            $item->image_path = $request->file("items.{$id}.image")->store($folder, 'public'); // Save new
                        }
                        
                        $item->save();
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function duplicate(Valuation $valuation)
    {
        // 1. Clone the main valuation record
        $newValuation = $valuation->replicate();
        $newValuation->name = $valuation->name . ' (Copy)';
        $newValuation->valuation_date = Carbon::now()->format('Y-m-d'); // Set to today's date for the new folder
        $newValuation->save();

        // 2. Determine the folder for the new valuation's images
        $newFolder = $this->getStorageFolder($newValuation);

        // 3. Clone each associated item and its physical image
        foreach ($valuation->items as $item) {
            $newImagePath = $item->image_path;

            if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                $newFilename = Str::random(40) . '.' . pathinfo($item->image_path, PATHINFO_EXTENSION);
                $newImagePath = $newFolder . '/' . $newFilename;
                Storage::disk('public')->copy($item->image_path, $newImagePath);
            }

            $newItem = $item->replicate();
            $newItem->valuation_id = $newValuation->id;
            $newItem->image_path = $newImagePath;
            $newItem->save();
        }

        // 4. Copy the PDF if it exists
        $oldPdfName = Str::slug($valuation->name) . '-' . Carbon::parse($valuation->valuation_date)->format('d-m-Y') . '.pdf';
        $oldPdfPath = $this->getStorageFolder($valuation) . '/' . $oldPdfName;

        $newPdfName = Str::slug($newValuation->name) . '-' . Carbon::parse($newValuation->valuation_date)->format('d-m-Y') . '.pdf';
        $newPdfPath = $newFolder . '/' . $newPdfName;

        if (Storage::disk('public')->exists($oldPdfPath)) {
            Storage::disk('public')->copy($oldPdfPath, $newPdfPath);
        }

        return redirect()->route('valuation.index')->with('success', 'Valuation duplicated successfully!');
    }

    public function pdf(Valuation $valuation)
    {
        // Load items for PDF display without redirecting to edit view
        $items = $valuation->items()->orderBy('sort_order')->orderBy('id')->get();
        $chunks = $items->chunk(6);
        $isPdfView = true;
        
        return view('valuation.store', compact('valuation', 'chunks', 'isPdfView'));
    }

    public function uploadPdf(Request $request, Valuation $valuation)
    {
        if ($request->hasFile('pdf')) {
            $folder = $this->getStorageFolder($valuation);
            
            // Generate a clean filename for the PDF
            $filename = Str::slug($valuation->name) . '-' . Carbon::parse($valuation->valuation_date)->format('d-m-Y') . '.pdf';
            $path = $folder . '/' . $filename;
            
            // Explicitly delete any existing file with the exact same name to force an overwrite
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            $request->file('pdf')->storeAs($folder, $filename, 'public');
            return response()->json(['success' => true, 'path' => asset('storage/' . $path)]);
        }
        return response()->json(['success' => false], 400);
    }
}