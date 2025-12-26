@extends('layouts.theme')

@section('title', 'Edit Certificate Template')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit Certificate Template</h1>
            <a href="{{ route('admin.certificate-templates.index') }}"
                class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>

        <form action="{{ route('admin.certificate-templates.update', $certificateTemplate->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="row">
                <!-- Left Column: Main Editor -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div
                            class="card-header py-3 border-bottom-primary d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Template Design</h6>
                            <ul class="nav nav-pills nav-sm" id="designTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="editor-tab" data-toggle="tab" href="#editorPanel"
                                        role="tab" aria-controls="editorPanel" aria-selected="true">
                                        <i class="fas fa-code mr-1"></i> Code / Editor
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="preview-tab" data-toggle="tab" href="#previewPanel" role="tab"
                                        aria-controls="previewPanel" aria-selected="false">
                                        <i class="fas fa-eye mr-1"></i> Live Preview
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <!-- Template Name -->
                            <div class="form-group">
                                <label for="name" class="font-weight-bold">Template Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name', $certificateTemplate->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="tab-content mt-4" id="designTabContent">
                                <!-- Editor Panel -->
                                <div class="tab-pane fade show active" id="editorPanel" role="tabpanel"
                                    aria-labelledby="editor-tab">
                                    <label for="body" class="font-weight-bold">Certificate HTML <span
                                            class="text-danger">*</span></label>
                                    <div class="alert alert-info py-2 px-3 small mb-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Edit the text layout here. Switch to the <strong>Live Preview</strong> tab to see
                                        changes.
                                    </div>
                                    <textarea id="my-editor" name="body"
                                        class="form-control @error('body') is-invalid @enderror"
                                        rows="20">{{ old('body', $certificateTemplate->body) }}</textarea>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Preview Panel -->
                                <div class="tab-pane fade" id="previewPanel" role="tabpanel" aria-labelledby="preview-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="font-weight-bold m-0 text-primary">Preview Simulation</label>
                                        <span class="badge badge-warning text-dark"><i
                                                class="fas fa-exclamation-triangle"></i> Approximation only</span>
                                    </div>

                                    <div class="preview-container border bg-light p-3 text-center overflow-auto"
                                        style="min-height: 600px;">
                                        <iframe id="cert-preview" class="bg-white shadow-sm"
                                            style="border: 0; width: 210mm; height: 297mm; transform: scale(0.7); transform-origin: top center;"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings & Config -->
                <div class="col-lg-4">

                    <!-- Publishing / Save -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 font-weight-bold text-white">Publish</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-warning btn-block btn-lg">
                                <i class="fas fa-save mr-2"></i> Update Template
                            </button>
                        </div>
                    </div>

                    <!-- Page Settings -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Page Configuration</h6>
                        </div>
                        <div class="card-body">

                            <!-- Print Mode -->
                            <div class="form-group">
                                <label class="font-weight-bold text-dark mb-2">Print Mode</label>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modeOriginal" name="content_type" value="full"
                                        class="custom-control-input" {{ old('content_type', $certificateTemplate->content_type) == 'full' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="modeOriginal">
                                        <strong class="text-primary">Digital / Full Design</strong>
                                        <br><small class="text-muted">For generating PDFs with background images.</small>
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modeLetterhead" name="content_type" value="letterhead"
                                        class="custom-control-input" {{ old('content_type', $certificateTemplate->content_type) == 'letterhead' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="modeLetterhead">
                                        <strong class="text-success">Letterhead Print</strong>
                                        <br><small class="text-muted">Text only.</small>
                                    </label>
                                </div>
                            </div>

                            <hr>

                            <!-- Size & Orientation -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="paper_size" class="small font-weight-bold">Paper Size</label>
                                    <select class="form-control form-control-sm" name="paper_size" id="paper_size">
                                        <option value="a4" {{ old('paper_size', $certificateTemplate->paper_size) == 'a4' ? 'selected' : '' }}>A4</option>
                                        <option value="a5" {{ old('paper_size', $certificateTemplate->paper_size) == 'a5' ? 'selected' : '' }}>A5</option>
                                        <option value="letter" {{ old('paper_size', $certificateTemplate->paper_size) == 'letter' ? 'selected' : '' }}>Letter</option>
                                        <option value="legal" {{ old('paper_size', $certificateTemplate->paper_size) == 'legal' ? 'selected' : '' }}>Legal</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="orientation" class="small font-weight-bold">Orientation</label>
                                    <select class="form-control form-control-sm" name="orientation" id="orientation">
                                        <option value="portrait" {{ old('orientation', $certificateTemplate->orientation) == 'portrait' ? 'selected' : '' }}>Portrait
                                        </option>
                                        <option value="landscape" {{ old('orientation', $certificateTemplate->orientation) == 'landscape' ? 'selected' : '' }}>Landscape
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Backgroud Image Upload -->
                            <div class="form-group" id="bg-upload-wrapper">
                                <label for="background_image" class="small font-weight-bold">Background Image</label>
                                @if($certificateTemplate->background_image)
                                    <div class="mb-2 p-1 border rounded bg-light">
                                        <small class="d-block text-success"><i class="fas fa-check-circle"></i> Current
                                            Image:</small>
                                        <small class="text-muted">{{ basename($certificateTemplate->background_image) }}</small>
                                    </div>
                                @endif
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="background_image"
                                        name="background_image" accept="image/*">
                                    <label class="custom-file-label" for="background_image">Change file...</label>
                                </div>
                                <small class="form-text text-muted">Previewing:</small>
                                <!-- Store existing image URL in data attribute for JS -->
                                <img id="bg-preview-img"
                                    src="{{ $certificateTemplate->background_image ? asset('storage/' . $certificateTemplate->background_image) : '' }}"
                                    data-original-src="{{ $certificateTemplate->background_image ? asset('storage/' . $certificateTemplate->background_image) : '' }}"
                                    class="img-fluid mt-2 border {{ $certificateTemplate->background_image ? '' : 'd-none' }}"
                                    style="max-height: 150px;">
                            </div>

                            <!-- File Naming -->
                            <hr>
                            <div class="form-group">
                                <label for="filename_format" class="font-weight-bold">Filename Format</label>
                                <input type="text" class="form-control form-control-sm" id="filename_format"
                                    name="filename_format"
                                    value="{{ old('filename_format', $certificateTemplate->filename_format ?? '[student_name]-[template_name]') }}">
                                <small class="text-muted">Pattern for bulk export filenames.</small>
                            </div>

                        </div>
                    </div>

                    <!-- Margin Settings -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Content Margins (mm)</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-row justify-content-center">
                                <div class="form-group col-4 text-center">
                                    <label class="small mb-1">Top</label>
                                    <input type="number" name="margin_top" id="margin_top"
                                        class="form-control form-control-sm text-center margin-input"
                                        value="{{ old('margin_top', $certificateTemplate->margin_top ?? 10) }}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-4 text-center">
                                    <label class="small mb-1">Left</label>
                                    <input type="number" name="margin_left" id="margin_left"
                                        class="form-control form-control-sm text-center margin-input"
                                        value="{{ old('margin_left', $certificateTemplate->margin_left ?? 10) }}">
                                </div>
                                <div class="col-4 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                                <div class="form-group col-4 text-center">
                                    <label class="small mb-1">Right</label>
                                    <input type="number" name="margin_right" id="margin_right"
                                        class="form-control form-control-sm text-center margin-input"
                                        value="{{ old('margin_right', $certificateTemplate->margin_right ?? 10) }}">
                                </div>
                            </div>
                            <div class="form-row justify-content-center">
                                <div class="form-group col-4 text-center">
                                    <label class="small mb-1">Bottom</label>
                                    <input type="number" name="margin_bottom" id="margin_bottom"
                                        class="form-control form-control-sm text-center margin-input"
                                        value="{{ old('margin_bottom', $certificateTemplate->margin_bottom ?? 10) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholders -->
                    <div class="card shadow mb-4">
                        <a href="#collapsePlaceholders" class="d-block card-header py-3" data-toggle="collapse"
                            role="button" aria-expanded="true" aria-controls="collapsePlaceholders">
                            <h6 class="m-0 font-weight-bold text-primary">Available Codes</h6>
                        </a>
                        <div class="collapse show" id="collapsePlaceholders">
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-uppercase text-gray-500 font-weight-bold">Student Info</small>
                                    <div class="mt-1">
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[student_name]</span>
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[enrollment_number]</span>
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[father_name]</span>
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[dob]</span>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-uppercase text-gray-500 font-weight-bold">System</small>
                                    <div class="mt-1">
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[college_name]</span>
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[issue_date]</span>
                                        <span class="badge badge-light border p-1 mb-1 mr-1 copy-code"
                                            style="cursor:pointer" title="Click to copy">[college_logo_url]</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // --- State Management for Preview (Identical Logic) ---
        function updatePreview() {
            const iframe = document.getElementById('cert-preview');
            const doc = iframe.contentDocument || iframe.contentWindow.document;

            let content = '';
            if (CKEDITOR.instances['my-editor']) {
                content = CKEDITOR.instances['my-editor'].getData();
            } else {
                content = document.getElementById('my-editor').value;
            }

            const size = document.getElementById('paper_size').value;
            const orientation = document.getElementById('orientation').value;
            const marginTop = document.getElementById('margin_top').value || 0;
            const marginRight = document.getElementById('margin_right').value || 0;
            const marginBottom = document.getElementById('margin_bottom').value || 0;
            const marginLeft = document.getElementById('margin_left').value || 0;
            const mode = document.querySelector('input[name="content_type"]:checked').value;

            const demoData = {
                '[student_name]': 'John Doe',
                '[enrollment_number]': 'ENR-2024-001',
                '[course_name]': 'Bachelor of Technology',
                '[batch_name]': '2020-2024',
                '[father_name]': 'Robert Doe',
                '[dob]': '15-08-2002',
                '[issue_date]': '{{ now()->format("F j, Y") }}',
                '[college_name]': '{{ setting("college_name", "Victoria University") }}',
                '[college_logo_url]': '{{ setting("college_logo") ? asset("storage/" . setting("college_logo")) : "" }}'
            };

            Object.keys(demoData).forEach(key => {
                content = content.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), demoData[key]);
            });

            const mmToPx = 3.78;
            let width = '210mm';
            let height = '297mm';

            if (size === 'a4') { width = '210mm'; height = '297mm'; }
            else if (size === 'a5') { width = '148mm'; height = '210mm'; }
            else if (size === 'letter') { width = '216mm'; height = '279mm'; }
            else if (size === 'legal') { width = '216mm'; height = '356mm'; }

            if (orientation === 'landscape') { let temp = width; width = height; height = temp; }

            iframe.style.width = width;
            iframe.style.height = height;

            let bgStyle = '';
            const bgPreviewImg = document.getElementById('bg-preview-img');
            if (mode === 'full' && bgPreviewImg.src && !bgPreviewImg.classList.contains('d-none')) {
                bgStyle = `
                    background-image: url('${bgPreviewImg.src}');
                    background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                `;
            }

            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { 
                            margin: 0; 
                            padding: ${marginTop}mm ${marginRight}mm ${marginBottom}mm ${marginLeft}mm; 
                            box-sizing: border-box;
                            width: 100%;
                            height: 100vh;
                            overflow: hidden;
                            ${bgStyle}
                        }
                        * { box-sizing: border-box; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            doc.close();
        }

        function toggleBgUpload() {
            if (document.getElementById('modeLetterhead').checked) {
                $('#bg-upload-wrapper').slideUp();
            } else {
                $('#bg-upload-wrapper').slideDown();
            }
            updatePreview();
        }

        $('input[name="content_type"]').change(function () { toggleBgUpload(); });
        $('.margin-input, #paper_size, #orientation').on('change keyup', function () { updatePreview(); });

        document.getElementById('background_image').addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById('bg-preview-img');
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                    updatePreview();
                }
                reader.readAsDataURL(file);
            } else {
                // Revert? Nope, just keep as is or user can reload
            }
        });

        $('.custom-file-input').on('change', function () {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        $('.copy-code').click(function () {
            let code = $(this).text();
            navigator.clipboard.writeText(code);
            toastr.success('Copied: ' + code);
        });

        toggleBgUpload();
    </script>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('my-editor');
        CKEDITOR.instances['my-editor'].on('change', function () { updatePreview(); });
        setTimeout(updatePreview, 1000);
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (e.target.id === 'preview-tab') { updatePreview(); }
        });
    </script>
@endpush