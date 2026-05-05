 <x-app-layout>
    @section('page_title', 'Valuation Management')
    
    <!-- SortableJS for Drag & Drop functionality -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <!-- html2pdf for Direct PDF Downloading & Uploading -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <div x-data="{ editing: false }"> 
        <div class="max-w-[210mm] mx-auto mt-6 px-4 print:hidden" id="action-buttons">
            <div class="flex {{ $isPdfView ? 'justify-end' : 'justify-between' }} items-center bg-white p-5 rounded-xl shadow-lg border border-slate-200">
                @if(!$isPdfView)
                <div class="flex gap-3">
                    <a href="{{ route('valuation.edit', $valuation->id) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-bold text-xs uppercase transition-all shadow-md flex items-center">
                        ⬅️ Go Back & Edit
                    </a>
                    <button @click="if(editing) { saveAllChanges(); } else { editing = true; }" :class="editing ? 'bg-green-600' : 'bg-blue-600'" class="text-white px-6 py-2 rounded-lg font-bold text-xs uppercase transition-all shadow-md">
                        <span x-text="editing ? '✅ Save & Finish Adjustments' : '📝 Adjust Layout'"></span>
                    </button>
                </div>
                @endif

                <button onclick="printPdf()" class="bg-slate-900 text-white px-8 py-3 rounded-lg font-bold uppercase text-xs tracking-widest">
                    🖨️ Save PDF 
                </button>
            </div>
        </div>

        <div id="print-area" class="max-w-[210mm] mx-auto mt-8 px-4">
            <div class="report-wrapper">
            @foreach($chunks as $pageIndex => $currentItems)
                <div class="a4-sheet" :class="editing ? 'ring-4 ring-blue-400 ring-inset' : ''">
                    
                    <div class="header-box">
                        <h1 class="main-title">PHOTOGRAPHS OF JEWELLERY VALUED</h1>
                    </div>

                    <div class="date-header">
                        <span :contenteditable="editing" id="val_date">Date: {{ \Carbon\Carbon::parse($valuation->valuation_date)->format('d/m/Y') }}</span>
                    </div>

                    <div class="customer-section">
                        <div class="info-row">
                            <span class="label">Name of Owner(s) :</span>
                            <span class="value" :contenteditable="editing" id="val_name">{{ $valuation->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Address :</span>
                            <span class="value" :contenteditable="editing" id="val_address">{{ $valuation->address ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="photo-grid">
                        @foreach($currentItems as $index => $item)
                            <div class="jewelry-frame group relative" data-id="{{ $item->id }}">
                                <!-- Drag Handle -->
                                <div x-show="editing" class="print:hidden drag-handle absolute top-1 left-1 bg-slate-800 text-white w-6 h-6 flex items-center justify-center rounded-sm text-xs font-bold z-20 shadow-lg cursor-move">✥</div>
                                
                                <button x-show="editing" onclick="markForDeletion(this)" class="print:hidden absolute top-1 right-1 bg-red-600 text-white w-6 h-6 rounded-full text-xs font-bold z-20 shadow-lg">✕</button>

                                <div class="image-wrapper relative">
                                    <img src="{{ asset('storage/' . $item->image_path) }}" class="preview-img">
                                    
                                    <!-- Replace Image Overlay -->
                                    <label x-show="editing" class="print:hidden absolute inset-0 bg-black/60 text-white flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity z-10">
                                        <span class="text-xl mb-1">📷</span>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">Replace</span>
                                        <input type="file" class="hidden image-upload" accept="image/*" onchange="previewImage(this)">
                                    </label>
                                </div>
                                <div class="weight-label">
                                    <span class="item-index">{{ ($pageIndex * 6) + $loop->iteration }})</span> Gross Weight : 
                                    <span :contenteditable="editing" class="item-grams font-black px-1" onblur="formatWeight(this)">{{ number_format((float)($item->grams ?? 0), 3, '.', '') }}</span> GM
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="footer-section">
                        <div class="flex-1 text-left">DATE: {{ \Carbon\Carbon::parse($valuation->valuation_date)->format('d/m/Y') }}</div>
                        <div class="flex-1 text-center">PAGE {{ $pageIndex + 1 }} / {{ count($chunks) }}</div>
                        <div class="flex-1 text-right"></div>
                    </div>
                </div>
            @endforeach
            </div>
        </div> 
    </div>

     <script>
        // Initialize Drag and Drop when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.photo-grid').forEach(grid => {
                Sortable.create(grid, {
                    group: 'shared', // Allows dragging between different pages!
                    animation: 150,
                    handle: '.drag-handle', // Only drag by the handle
                    fallbackOnBody: true, // Prevents dragged item from disappearing behind grids
                    onEnd: function (evt) {
                        updateIndexes(); // Recount numbers after dropping
                    }
                });
            });
        });

        function updateIndexes() {
            document.querySelectorAll('.jewelry-frame:not(.is-deleted)').forEach((el, idx) => {
                let indexSpan = el.querySelector('.item-index');
                if (indexSpan) indexSpan.innerText = (idx + 1) + ')';
            });
        }

        let deletedIds = [];

        function markForDeletion(btn) {
            if(confirm('Delete this image from Database and Folder?')) {
                let frame = btn.closest('.jewelry-frame');
                // Add the ID to the list of items to be deleted on save
                deletedIds.push(frame.getAttribute('data-id'));
                // Mark the element with a class so we can filter it out
                frame.classList.add('is-deleted');
                // Now, reflow all items across the pages to fill the gap
                reflowItemsAcrossPages();
            }
        }

        function reflowItemsAcrossPages() {
            // 1. Gather all item elements that are not marked for deletion into a single array
            const allVisibleItems = Array.from(document.querySelectorAll('.jewelry-frame:not(.is-deleted)'));

            // 2. Get all the grid containers
            const allGrids = Array.from(document.querySelectorAll('.photo-grid'));

            // 3. Detach all items from the DOM temporarily.
            allVisibleItems.forEach(item => item.remove());

            // 4. Redistribute the items across the grids, 6 per page
            const itemsPerPage = 6;
            allVisibleItems.forEach((item, index) => {
                const pageIndex = Math.floor(index / itemsPerPage);
                if (allGrids[pageIndex]) {
                    allGrids[pageIndex].appendChild(item);
                }
            });

            // 5. After moving everything, update the numbering
            updateIndexes();
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    input.closest('.image-wrapper').querySelector('.preview-img').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function formatWeight(element) {
            let val = parseFloat(element.innerText.trim());
            if (!isNaN(val)) {
                // Truncate to 3 decimal places instead of rounding
                const truncatedVal = Math.trunc(val * 1000) / 1000;
                element.innerText = truncatedVal.toFixed(3);
            } else {
                element.innerText = "0.000";
            }
        }

        function saveAllChanges() {
            let formData = new FormData();
            formData.append('name', document.getElementById('val_name').innerText.trim());
            formData.append('address', document.getElementById('val_address').innerText.trim());
            formData.append('valuation_date', document.getElementById('val_date').innerText.replace('Date: ', '').trim());
            
            deletedIds.forEach(id => formData.append('deleted_ids[]', id));

            document.querySelectorAll('.jewelry-frame:not(.is-deleted)').forEach((el, index) => {
                let id = el.getAttribute('data-id');
                formData.append(`items[${id}][grams]`, el.querySelector('.item-grams').innerText.trim());
                formData.append(`items[${id}][sort_order]`, index);
                
                let fileInput = el.querySelector('.image-upload');
                if (fileInput && fileInput.files[0]) {
                    formData.append(`items[${id}][image]`, fileInput.files[0]);
                }
            });

            fetch("{{ route('valuation.updateAjax', $valuation->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    // Note: We deliberately leave out Content-Type so the browser sets it to multipart/form-data for the image files automatically!
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert('Database and Folders Updated Successfully!');
                window.location.reload(); // Reloads to fix the 6-image-per-page grid
            });
        }

        function printPdf() {
            window.scrollTo(0, 0); // Prevent scroll offset issues
            const element = document.getElementById('print-area');
            const filename = "{{ \Illuminate\Support\Str::slug($valuation->name) }}-{{ \Carbon\Carbon::parse($valuation->valuation_date)->format('d-m-Y') }}.pdf";

            const opt = {
                margin:       0,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
            pagebreak:    { mode: ['css', 'legacy'] },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true, 
                    scrollY: 0,
                    windowY: 0,
                    y: 0,
                    onclone: function(clonedDoc) {
                        // Apply the export styling ONLY to the hidden cloned document so the screen doesn't shift
                        let printArea = clonedDoc.getElementById('print-area');
                        printArea.classList.add('pdf-export-mode');
                        printArea.classList.remove('mt-8', 'px-4', 'mx-auto');
                        
                        // Fix offset without breaking the DOM tree (which caused the blank PDF)
                        printArea.style.position = 'absolute';
                        printArea.style.top = '0';
                        printArea.style.left = '0';
                        printArea.style.margin = '0';
                        printArea.style.padding = '0';

                        clonedDoc.body.style.margin = '0';
                        clonedDoc.body.style.padding = '0';
                        clonedDoc.documentElement.style.margin = '0';
                        clonedDoc.documentElement.style.padding = '0';
                    }
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Generate PDF as a Blob and upload it to the server
            html2pdf().set(opt).from(element).outputPdf('blob').then((pdfBlob) => {
                
                // 1. Trigger native browser download (the upward animation)
                const blobUrl = URL.createObjectURL(pdfBlob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);

                // 2. Upload to the server silently in the background
                let formData = new FormData();
                formData.append('pdf', pdfBlob, filename);

                fetch("{{ route('valuation.uploadPdf', $valuation->id) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('PDF successfully saved in the customer folder!');
                    } else {
                        console.error('Failed to save the PDF to the server.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        }
    </script> 

 <style> 
        /* DASHBOARD STYLING (Screen) */
        .report-wrapper { display: flex; flex-direction: column; align-items: center; gap: 30px; }
        .a4-sheet { 
            width: 210mm; height: 290mm; max-height: 290mm; background: white; padding: 10mm 15mm 10mm 15mm; 
            position: relative;
            display: flex; flex-direction: column; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box; overflow: hidden; 
        } 
         .header-box { text-align: center; margin-bottom: 8px; }
        .main-title { margin-top: 0; margin-bottom: 0; color: #991b1b; font-family: serif; font-size: 22px; font-weight: 900; text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .date-header { display: flex; justify-content: flex-end; font-weight: 800; font-size: 14px; margin-bottom: 10px; }
        .customer-section { font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 20px; }
        .info-row { margin-bottom: 6px; display: flex; }
        .label { min-width: 220px; }
        .value { border-bottom: 1px solid #000; flex-grow: 1; padding-left: 10px; white-space: pre-wrap; word-break: break-word; }
        .photo-grid { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: repeat(3, minmax(0, 1fr)); gap: 8px; flex-grow: 1; min-height: 0; height: 100%; margin-bottom: 10mm; }
        .jewelry-frame { border: 1.5px solid #000; display: flex; flex-direction: column; min-height: 0; height: 100%; background: #fff; position: relative; overflow: hidden; }
        .image-wrapper { flex: 1; position: relative; width: 100%; height: 100%; box-sizing: border-box; overflow: hidden; display: flex; justify-content: center; align-items: center; padding: 12px; }
        .image-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
        .weight-label { border-top: 1.5px solid #000; text-align: center; font-weight: 900; font-size: 11px; padding: 4px 0; }
        .footer-section { margin-top: auto; width: 100%; display: flex; justify-content: space-between; font-size: 11px; font-weight: 900; border-top: 1px solid #cbd5e1; padding-top: 8px; z-index: 10; background: white; }

        [contenteditable="true"] { background: #fefce8; outline: 2px solid #3b82f6; border-radius: 2px; }

        /* PDF Export Styling to prevent extra blank pages */
        #print-area.pdf-export-mode { margin: 0 !important; padding: 0 !important; width: 210mm !important; max-width: 210mm !important; overflow: hidden !important; display: block !important; background: white !important; }
        .pdf-export-mode .report-wrapper { gap: 0 !important; margin: 0 !important; padding: 0 !important; display: block !important; background: white !important; }
        .pdf-export-mode .a4-sheet { 
            box-shadow: none !important; 
            margin: 0 !important; 
            width: 210mm !important;
            height: 290mm !important; /* Buffer strictly prevents overflowing to next page */
            max-height: 290mm !important;
            box-sizing: border-box !important;
            overflow: hidden !important; 
            border: none !important;
            border-radius: 0 !important;
            padding: 10mm 15mm 10mm 15mm !important;
            position: relative !important;
            page-break-after: always !important;
            page-break-inside: avoid !important;
        }
        .pdf-export-mode .a4-sheet:last-of-type {
            page-break-after: auto !important;
        }

        /* Ensure contenteditable fields and controls don't show in the generated PDF */
        .pdf-export-mode [contenteditable="true"] { background: transparent !important; outline: none !important; }
        .pdf-export-mode .print\:hidden { display: none !important; }

        /* THE PRINT ENGINE (PDF Generation Settings) */
        @media print {
            @page {
                size: A4;
                margin: 0; /* This forces the browser to hide the default Header (Title) and Footer (Date/URL) */
            }

            /* 1. Hide Dashboard Sidebar, Header, and Action Buttons */
            /* Fallback for older browsers (kept separate to prevent invalidation) */
            aside, header, nav, footer {
                display: none !important;
            }
            
            /* Magic bullet: Hides ALL layout wrappers/sidebars regardless of HTML tag! */
            body *:not(:has(#print-area)):not(#print-area):not(#print-area *) {
                display: none !important;
            }
            
            /* Hide the action buttons specifically */
            .print\:hidden, button, #print-area .drag-handle, #action-buttons {
                display: none !important;
            }

            /* 2. Reset Layout Constraints from App Layout */
            body, html, main, .min-h-screen { 
                background: white !important;
                margin: 0 !important; 
                padding: 0 !important;
                height: auto !important;
                overflow: visible !important; 
            }
                
            #print-area {
                margin: 0 !important;
                padding: 0 !important;
            }

            .h-screen, .min-h-screen, .flex, .overflow-hidden,.flex-1, .min-w-0 { 
                display: block !important; 
                height: auto !important; 
                overflow: visible !important; 
                position: static !important; 
                margin: 0 !important;
                padding: 0 !important;
            }


            /* 3. Force Colors & Borders in Chrome/Safari PDF */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* 4. Formatting the A4 Pages */
            .report-wrapper { margin: 0 !important; padding: 0 !important; gap: 0 !important; }
            .a4-sheet { 
                margin: 0 !important; 
                box-shadow: none !important; 
                page-break-after: always !important; 
                page-break-inside: avoid !important;
                width: 210mm !important;
                height: 290mm !important; /* Strict height to prevent native print bleed */
                max-height: 290mm !important;
                padding: 10mm 15mm 10mm 15mm !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                position: relative !important;
            }
            
            .a4-sheet:last-of-type {
                page-break-after: auto !important; /* Prevent blank page at the very end */
            }
            [contenteditable="true"] { background: transparent !important;
             outline: none !important; }
        }
    </style> 


</x-app-layout> 
 
