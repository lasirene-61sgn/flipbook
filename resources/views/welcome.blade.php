<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Flipbook Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen antialiased flex flex-col justify-between">

    <!-- Navbar -->
    <header class="border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto h-16 px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-lg shadow-lg shadow-indigo-500/30">F</span>
                <h1 class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Flipbook Library</h1>
            </div>
            <span class="text-xs text-slate-500 font-mono bg-slate-900 px-2.5 py-1 rounded-md border border-slate-800">Chunk Upload v2.0</span>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="max-w-6xl mx-auto w-full px-4 py-8 flex-grow grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Left Side: Upload & Action Panel -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-sm">
                <h2 class="text-xl font-bold mb-1 text-slate-100">Add New Flipbook</h2>
                <p class="text-xs text-slate-400 mb-4">Upload a high-resolution PDF or bulk images to create your flipbook</p>
                
                <!-- Tabs -->
                <div class="flex border-b border-slate-800 mb-6">
                    <button type="button" id="tab-pdf" class="px-4 py-2 text-sm font-medium text-indigo-400 border-b-2 border-indigo-400">PDF Upload</button>
                    <button type="button" id="tab-images" class="px-4 py-2 text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300">Bulk Images</button>
                </div>

                <!-- PDF Form -->
                <form id="upload-form" class="space-y-4 block">
                    @csrf
                    <div>
                        <label for="book-title" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Book Custom Title (Optional)</label>
                        <input type="text" id="book-title" name="title" placeholder="Leave empty to use file name"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>

                    <!-- Drag & Drop Zone -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select PDF File</label>
                        <div id="drop-zone" class="border-2 border-dashed border-slate-800 hover:border-indigo-500/60 transition-colors rounded-2xl p-6 text-center cursor-pointer bg-slate-950/40 relative group">
                            <input type="file" id="pdf-file" accept="application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="space-y-3">
                                <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center mx-auto text-slate-400 group-hover:text-indigo-400 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium text-indigo-400">Click to upload</span> or drag and drop
                                </div>
                                <p class="text-xs text-slate-500">Only standard PDF format supported</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selected File Info -->
                    <div id="file-info" class="hidden bg-slate-950 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                        <div class="truncate mr-3">
                            <p id="info-name" class="text-sm font-medium text-slate-200 truncate">document.pdf</p>
                            <p id="info-size" class="text-xs text-slate-500">0.0 MB</p>
                        </div>
                        <button type="button" id="btn-remove" class="text-slate-500 hover:text-red-400 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                    <!-- Uploading Progress State -->
                    <div id="progress-container" class="hidden space-y-2">
                        <div class="flex justify-between text-xs font-semibold">
                            <span id="progress-status" class="text-indigo-400">Uploading Chunk...</span>
                            <span id="progress-percent" class="text-slate-400">0%</span>
                        </div>
                        <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                            <div id="progress-bar" class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 w-0 transition-[width] duration-150"></div>
                        </div>
                    </div>

                    <button type="submit" id="btn-submit" disabled class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-800 disabled:text-slate-500 text-white font-medium py-3 rounded-xl shadow-lg transition duration-150 flex items-center justify-center gap-2">
                        <span>Compile & Upload PDF</span>
                    </button>
                </form>

                <!-- Images Form -->
                <form id="upload-images-form" class="space-y-4 hidden">
                    @csrf
                    <div>
                        <label for="book-title-images" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Book Custom Title (Optional)</label>
                        <input type="text" id="book-title-images" name="title" placeholder="Enter flipbook title"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>

                    <!-- Drag & Drop Zone Images -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select Bulk Images</label>
                        <div id="drop-zone-images" class="border-2 border-dashed border-slate-800 hover:border-indigo-500/60 transition-colors rounded-2xl p-6 text-center cursor-pointer bg-slate-950/40 relative group">
                            <input type="file" id="images-file" accept="image/*" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="space-y-3">
                                <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center mx-auto text-slate-400 group-hover:text-indigo-400 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium text-indigo-400">Click to select images</span> or drag and drop
                                </div>
                                <p class="text-xs text-slate-500">Multiple JPG/PNG files supported</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Files Info -->
                    <div id="images-info" class="hidden bg-slate-950 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                        <div class="truncate mr-3">
                            <p id="images-name" class="text-sm font-medium text-slate-200 truncate">0 images selected</p>
                            <p id="images-size" class="text-xs text-slate-500">0.0 MB</p>
                        </div>
                        <button type="button" id="btn-remove-images" class="text-slate-500 hover:text-red-400 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                    <!-- Uploading Progress State -->
                    <div id="progress-container-images" class="hidden space-y-2">
                        <div class="flex justify-between text-xs font-semibold">
                            <span id="progress-status-images" class="text-indigo-400">Uploading Images...</span>
                            <span id="progress-percent-images" class="text-slate-400">0%</span>
                        </div>
                        <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                            <div id="progress-bar-images" class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 w-0 transition-[width] duration-150"></div>
                        </div>
                    </div>

                    <button type="submit" id="btn-submit-images" disabled class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-800 disabled:text-slate-500 text-white font-medium py-3 rounded-xl shadow-lg transition duration-150 flex items-center justify-center gap-2">
                        <span>Upload Images</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Side: Library Gallery -->
        <div class="md:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-100">Your Shelf</h2>
                <span class="text-xs font-medium text-slate-400 bg-slate-900 border border-slate-800 px-3 py-1 rounded-full">{{ count($books) }} Books</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($books as $book)
                    <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition duration-150 group flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 bg-indigo-500/10 text-indigo-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <h3 class="font-bold text-slate-100 group-hover:text-white transition-colors truncate mb-1" title="{{ $book->title }}">{{ $book->title }}</h3>
                            <p class="text-xs text-slate-500 font-mono mb-6 truncate">{{ basename($book->pdf_path) }}</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400 bg-slate-950 border border-slate-800/80 px-2 py-1 rounded">PDF</span>
                            <a href="{{ route('books.show', $book->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                                Open in Flipbook
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full border border-dashed border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <p class="text-sm">No flipbooks currently compiled on your shelf.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-900 py-6 text-center text-xs text-slate-600">
        &copy; {{ date('Y') }} PDF-Flipbook Core. All Rights Reserved.
    </footer>

    <!-- JS Managed Chunked Upload Implementation -->
    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('pdf-file');
        const fileInfo = document.getElementById('file-info');
        const infoName = document.getElementById('info-name');
        const infoSize = document.getElementById('info-size');
        const btnRemove = document.getElementById('btn-remove');
        const btnSubmit = document.getElementById('btn-submit');
        const uploadForm = document.getElementById('upload-form');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressStatus = document.getElementById('progress-status');
        const progressPercent = document.getElementById('progress-percent');

        // Images elements
        const tabPdf = document.getElementById('tab-pdf');
        const tabImages = document.getElementById('tab-images');
        const uploadImagesForm = document.getElementById('upload-images-form');
        const dropZoneImages = document.getElementById('drop-zone-images');
        const imagesFile = document.getElementById('images-file');
        const imagesInfo = document.getElementById('images-info');
        const imagesName = document.getElementById('images-name');
        const imagesSize = document.getElementById('images-size');
        const btnRemoveImages = document.getElementById('btn-remove-images');
        const btnSubmitImages = document.getElementById('btn-submit-images');
        const progressContainerImages = document.getElementById('progress-container-images');
        const progressBarImages = document.getElementById('progress-bar-images');
        const progressStatusImages = document.getElementById('progress-status-images');
        const progressPercentImages = document.getElementById('progress-percent-images');

        let activeFile = null;
        let activeImages = [];
        const CHUNK_SIZE = 1 * 1024 * 1024; // 1MB chunks

        // Tab Switching
        tabPdf.addEventListener('click', () => {
            tabPdf.classList.replace('text-slate-400', 'text-indigo-400');
            tabPdf.classList.replace('border-transparent', 'border-indigo-400');
            tabImages.classList.replace('text-indigo-400', 'text-slate-400');
            tabImages.classList.replace('border-indigo-400', 'border-transparent');
            uploadForm.classList.replace('hidden', 'block');
            uploadImagesForm.classList.replace('block', 'hidden');
        });

        tabImages.addEventListener('click', () => {
            tabImages.classList.replace('text-slate-400', 'text-indigo-400');
            tabImages.classList.replace('border-transparent', 'border-indigo-400');
            tabPdf.classList.replace('text-indigo-400', 'text-slate-400');
            tabPdf.classList.replace('border-indigo-400', 'border-transparent');
            uploadImagesForm.classList.replace('hidden', 'block');
            uploadForm.classList.replace('block', 'hidden');
        });

        // Handle File Dragging UI states
        ['dragenter', 'dragover'].forEach(event => {
            dropZone.addEventListener(event, (e) => {
                e.preventDefault();
                dropZone.classList.add('border-indigo-500', 'bg-indigo-500/5');
            });
            dropZoneImages.addEventListener(event, (e) => {
                e.preventDefault();
                dropZoneImages.classList.add('border-indigo-500', 'bg-indigo-500/5');
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            dropZone.addEventListener(event, (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-indigo-500', 'bg-indigo-500/5');
            });
            dropZoneImages.addEventListener(event, (e) => {
                e.preventDefault();
                dropZoneImages.classList.remove('border-indigo-500', 'bg-indigo-500/5');
            });
        });

        // Track selected file
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files[0]);
            }
        });

        dropZone.addEventListener('drop', (e) => {
            if (e.dataTransfer.files.length > 0) {
                handleFileSelection(e.dataTransfer.files[0]);
            }
        });

        function handleFileSelection(file) {
            if (file.type !== 'application/pdf') {
                alert('Only PDF documents are accepted.');
                return;
            }
            activeFile = file;
            infoName.textContent = file.name;
            infoSize.textContent = `${(file.size / (1024 * 1024)).toFixed(2)} MB`;
            fileInfo.classList.remove('hidden');
            btnSubmit.removeAttribute('disabled');
        }

        btnRemove.addEventListener('click', () => {
            activeFile = null;
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            btnSubmit.setAttribute('disabled', 'true');
        });

        // Track selected images
        imagesFile.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleImagesSelection(e.target.files);
            }
        });

        dropZoneImages.addEventListener('drop', (e) => {
            if (e.dataTransfer.files.length > 0) {
                handleImagesSelection(e.dataTransfer.files);
            }
        });

        function handleImagesSelection(files) {
            activeImages = Array.from(files).filter(file => file.type.startsWith('image/'));
            if (activeImages.length === 0) {
                alert('Please select valid image files.');
                return;
            }
            let totalSize = activeImages.reduce((sum, file) => sum + file.size, 0);
            imagesName.textContent = `${activeImages.length} images selected`;
            imagesSize.textContent = `${(totalSize / (1024 * 1024)).toFixed(2)} MB`;
            imagesInfo.classList.remove('hidden');
            btnSubmitImages.removeAttribute('disabled');
        }

        btnRemoveImages.addEventListener('click', () => {
            activeImages = [];
            imagesFile.value = '';
            imagesInfo.classList.add('hidden');
            btnSubmitImages.setAttribute('disabled', 'true');
        });

        // Form Submit for PDF Chunking
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeFile) return;

            btnSubmit.setAttribute('disabled', 'true');
            progressContainer.classList.remove('hidden');

            const totalChunks = Math.ceil(activeFile.size / CHUNK_SIZE);
            const customTitle = document.getElementById('book-title').value;
            const uniqueFileName = `${Date.now()}_${activeFile.name.replace(/\s+/g, '_')}`;

            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, activeFile.size);
                const chunk = activeFile.slice(start, end);

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('patch', chunk);
                formData.append('chunkIndex', i);
                formData.append('totalChunks', totalChunks);
                formData.append('fileName', uniqueFileName);
                if (customTitle) formData.append('title', customTitle);

                progressStatus.textContent = `Uploading parts: ${i + 1}/${totalChunks}`;
                
                try {
                    const response = await fetch("{{ route('books.store') }}", {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.message || 'Chunk Upload failed.');
                    }

                    const percent = Math.round(((i + 1) / totalChunks) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressPercent.textContent = `${percent}%`;

                    if (result.redirect) {
                        progressStatus.textContent = 'Compiling elements...';
                        window.location.href = result.redirect;
                        return;
                    }

                } catch (error) {
                    alert(`Upload Error: ${error.message}`);
                    btnSubmit.removeAttribute('disabled');
                    progressContainer.classList.add('hidden');
                    return;
                }
            }
        });

        // Form Submit for Bulk Images
        uploadImagesForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (activeImages.length === 0) return;

            btnSubmitImages.setAttribute('disabled', 'true');
            progressContainerImages.classList.remove('hidden');

            const customTitle = document.getElementById('book-title-images').value;
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            if (customTitle) formData.append('title', customTitle);
            
            activeImages.forEach((image) => {
                formData.append('images[]', image);
            });

            progressStatusImages.textContent = 'Uploading...';
            progressBarImages.style.width = '10%';
            progressPercentImages.textContent = '10%';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', "{{ route('books.storeImages') }}", true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBarImages.style.width = `${percentComplete}%`;
                    progressPercentImages.textContent = `${percentComplete}%`;
                    if (percentComplete === 100) {
                        progressStatusImages.textContent = 'Compiling elements...';
                    }
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success && result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        alert(`Upload Error: ${result.message || 'Failed'}`);
                        btnSubmitImages.removeAttribute('disabled');
                        progressContainerImages.classList.add('hidden');
                    }
                } else {
                    alert('Upload Failed with status: ' + xhr.status);
                    btnSubmitImages.removeAttribute('disabled');
                    progressContainerImages.classList.add('hidden');
                }
            };

            xhr.onerror = function() {
                alert('An error occurred during the upload');
                btnSubmitImages.removeAttribute('disabled');
                progressContainerImages.classList.add('hidden');
            };

            xhr.send(formData);
        });
    </script>
</body>
</html>