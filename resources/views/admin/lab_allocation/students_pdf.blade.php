<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} - {{ $academicYear->name }}</title>
    <style>
        /* --- Page & Print Settings --- */
        @page {
            size: A4;
            margin: 2cm;
            @bottom-right {
                content: "Page " counter(page);
                font-size: 9pt;
                color: #888;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            color: #222;
            margin: 0;
            counter-reset: page;
        }

        h1, h2, h3, h4 {
            color: #1a1a1a;
        }

        /* --- Layout --- */
        .page-container {
            width: 100%;
            padding: 0 10px;
        }

        .page-break {
            page-break-before: always;
        }

        /* --- Header --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 3px solid #004080;
            margin-bottom: 20px;
        }

        .header-logo img {
            height: 60px;
            max-width: 120px;
            object-fit: contain;
        }

        .header-details {
            text-align: right;
            font-size: 9pt;
            color: #444;
        }

        .header-details .college-name {
            font-size: 15pt;
            font-weight: 700;
            color: #004080;
        }

        /* --- Title Section --- */
        .report-main-title {
            text-align: center;
            margin: 30px 0;
        }

        .report-main-title h1 {
            font-size: 20pt;
            margin: 0;
            color: #2a2a2a;
        }

        .report-main-title h2 {
            font-size: 13pt;
            color: #666;
            margin-top: 8px;
        }

        /* --- Meta Info --- */
        .meta-info {
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            background-color: #eef3f9;
            padding: 12px 15px;
            border: 1px solid #d0dcea;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        /* --- Batch Sections --- */
        .batch-section {
            margin-bottom: 40px;
            break-inside: avoid;
        }

        .batch-header {
            font-size: 15pt;
            border-bottom: 2px solid #ccc;
            margin-bottom: 20px;
            padding-bottom: 5px;
        }

        .group-container {
            margin-bottom: 25px;
            break-inside: avoid;
        }

        .group-header {
            font-size: 12pt;
            font-weight: 600;
            color: #00557f;
            margin-bottom: 3px;
        }

        .group-details {
            font-size: 9pt;
            color: #555;
            font-style: italic;
            margin-bottom: 10px;
        }

        /* --- Table Styling --- */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 20px;
        }

        .students-table th, .students-table td {
            padding: 8px 6px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .students-table thead th {
            background-color: #f0f4f8;
            font-weight: bold;
            color: #000;
            border-bottom: 2px solid #aaa;
        }

        .students-table .student-name {
            font-weight: bold;
        }

        /* --- Unassigned Section --- */
        .unassigned-section {
            margin-top: 40px;
            padding: 20px;
            background: #fff3f3;
            border: 2px dashed #cc0000;
            border-radius: 5px;
        }

        .unassigned-title {
            color: #cc0000;
            font-size: 16pt;
            margin-bottom: 20px;
        }

        .unassigned-batch-title {
            font-size: 12pt;
            margin-bottom: 10px;
            color: #a00;
        }

        /* --- Helpers --- */
        .text-center { text-align: center; }
        .text-right { text-align: right; }

    </style>
</head>
<body>

<div class="page-container">

    {{-- Header --}}
    <header class="header">
        <div class="header-logo">
            @if($collegeInfo['logo'])
                <img src="{{ public_path('storage/' . $collegeInfo['logo']) }}" alt="Logo">
            @endif
        </div>
        <div class="header-details">
            <div class="college-name">{{ $collegeInfo['name'] }}</div>
            <div>{{ $collegeInfo['address'] }}</div>
            <div>Phone: {{ $collegeInfo['phone'] }} | Email: {{ $collegeInfo['email'] }}</div>
            <div><strong>Affiliated to:</strong> {{ $collegeInfo['affiliation'] }}</div>
        </div>
    </header>

    {{-- Title --}}
    <div class="report-main-title">
        <h1>{{ $reportTitle }}</h1>
        <h2>Academic Year: {{ $academicYear->name }}</h2>
    </div>



    {{-- Batches --}}
    @foreach($batches as $batch)
        <section class="batch-section">
            <h3 class="batch-header">{{ $batch->course->name }} - {{ $batch->name }}</h3>

            @php
                $studentGroupMap = [];
                foreach($batch->practicalGroups as $group) {
                    foreach($group->students as $student) {
                        $studentGroupMap[$student->id] = $group;
                    }
                }
            @endphp

            @foreach($batch->practicalGroups as $group)
                @if($group->students->count() > 0)
                    <div class="group-container">
                        <h4 class="group-header">
                            @php
                                $groupDisplayName = $group->name;
                                if (preg_match('/(Group\s+[A-Z0-9]+)$/i', $group->name, $matches)) {
                                    $groupDisplayName = $matches[1];
                                }
                            @endphp
                            {{ $groupDisplayName }}
                        </h4>
                        <div class="group-details">Total Students: {{ $group->students->count() }}</div>

                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 10%;">S.No.</th>
                                    <th style="width: 60%;">Student Name</th>
                                    <th style="width: 30%;">Roll Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group->students->sortBy('name') as $index => $student)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="student-name">{{ $student->name }}</td>
                                        <td>{{ $student->enrollment_number ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        </section>
    @endforeach

    {{-- Unassigned Students --}}
    @if(count($unassignedStudents) > 0)
        <section class="unassigned-section page-break">
            <h3 class="unassigned-title">⚠️ Unassigned Students Summary</h3>
            @foreach($unassignedStudents as $batchName => $students)
                <div class="group-container">
                    <h4 class="unassigned-batch-title">{{ $batchName }} ({{ $students->count() }} Students)</h4>
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 10%;">S.No.</th>
                                <th style="width: 60%;">Student Name</th>
                                <th style="width: 30%;">Roll Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="student-name">{{ $student->name }}</td>
                                    <td>{{ $student->enrollment_number ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </section>
    @endif

</div>
</body>
</html>
