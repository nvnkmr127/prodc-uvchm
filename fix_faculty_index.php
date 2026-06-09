<?php
$content = file_get_contents('resources/views/admin/faculty/index.blade.php');

// Define replacements safely using str_replace.

// 1. Replace @forelse with @if ($faculties->isEmpty())
$find1 = "@forelse (\$faculties as \$faculty)";
$replace1 = <<<HTML
        @if (\$faculties->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">No Faculty Members Found</h5>
                <p class="text-muted mb-4">No faculty members with the 'staff' role exist yet.</p>
                <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Create Your First Faculty Member
                </a>
            </div>
        @else
            {{-- Card View Container --}}
            <div id="facultyCardsContainer">
                @foreach (\$faculties as \$faculty)
HTML;
$content = str_replace($find1, $replace1, $content);

// 2. Remove the table row generation from the Card loop
// We need to cut out the `{{-- Table View (Hidden by default) --}}` block.
$trStart = "{{-- Table View (Hidden by default) --}}";
$trEnd = "@empty";
$trStartPos = strpos($content, $trStart);
$trEndPos = strpos($content, $trEnd);

$trBlock = substr($content, $trStartPos, $trEndPos - $trStartPos);
// We will insert this $trBlock into the table body later.
// Remove d-none from the tr class inside the block.
$trBlock = str_replace('tr class="faculty-table-row faculty-item d-none"', 'tr class="faculty-table-row faculty-item"', $trBlock);

// Remove the extracted block and the @empty part up to @endforelse
$emptyStartPos = strpos($content, "@empty");
$endForelsePos = strpos($content, "@endforelse") + strlen("@endforelse");

$content = substr($content, 0, $trStartPos) . "                @endforeach\n            </div>\n\n" . substr($content, $endForelsePos);

// 3. Insert the $trBlock into the table body
$tbodyStart = "<tbody id=\"facultyTableBody\">";
$tbodyEnd = "</tbody>";
$tbodyStartPos = strpos($content, $tbodyStart);
$tbodyEndPos = strpos($content, $tbodyEnd);

$newTbody = "<tbody id=\"facultyTableBody\">\n                    @foreach (\$faculties as \$faculty)\n            " . $trBlock . "                    @endforeach\n            </tbody>\n        @endif";

$content = substr($content, 0, $tbodyStartPos) . $newTbody . substr($content, $tbodyEndPos + strlen($tbodyEnd));

// 4. Update JavaScript
$jsFind1 = <<<JS
    const facultyCards = document.querySelectorAll('.faculty-card');
    const facultyTableRows = document.querySelectorAll('.faculty-table-row');
JS;
$jsReplace1 = <<<JS
    const facultyCardsContainer = document.getElementById('facultyCardsContainer');
JS;
$content = str_replace($jsFind1, $jsReplace1, $content);

$jsFind2 = <<<JS
    cardViewBtn.addEventListener('click', function() {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        facultyTable.classList.add('d-none');
        facultyCards.forEach(card => card.classList.remove('d-none'));
    });

    tableViewBtn.addEventListener('click', function() {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        facultyTable.classList.remove('d-none');
        facultyCards.forEach(card => card.classList.add('d-none'));
        
        // Move table rows to table body
        const tableBody = document.getElementById('facultyTableBody');
        facultyTableRows.forEach(row => {
            row.classList.remove('d-none');
            tableBody.appendChild(row);
        });
    });
JS;

$jsReplace2 = <<<JS
    cardViewBtn.addEventListener('click', function() {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        facultyTable.classList.add('d-none');
        if (facultyCardsContainer) facultyCardsContainer.classList.remove('d-none');
    });

    tableViewBtn.addEventListener('click', function() {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        facultyTable.classList.remove('d-none');
        if (facultyCardsContainer) facultyCardsContainer.classList.add('d-none');
    });
JS;
$content = str_replace($jsFind2, $jsReplace2, $content);

file_put_contents('resources/views/admin/faculty/index.blade.php', $content);
