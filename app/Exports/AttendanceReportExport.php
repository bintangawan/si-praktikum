<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    protected Course $course;

    protected $report;

    protected $meetings;

    public function __construct(Course $course, $report, $meetings)
    {
        $this->course = $course;
        $this->report = $report;
        $this->meetings = $meetings;
    }

    public function collection()
    {
        return collect($this->report);
    }

    public function title(): string
    {
        return 'Rekap Presensi - '.$this->course->course_name;
    }

    /**
     * Headings Dinamis
     */
    public function headings(): array
    {
        $headings = [
            'NIM',
            'Nama Mahasiswa',
        ];

        foreach ($this->meetings as $m) {
            $headings[] = 'P'.$m->meeting_number;
        }

        array_push(
            $headings,
            'Hadir (H)',
            'Sakit (S)',
            'Izin (I)',
            'Tanpa Keterangan (TK)', // Diubah ke TK
            'Persentase (%)',
            'Status',
            'Nilai'
        );

        return $headings;
    }

    public function map($row): array
    {
        $mappedData = [
            $row->id,
            $row->name,
        ];

        foreach ($this->meetings as $m) {
            $status = $row->per_meeting_status[$m->id] ?? '-';
            if ($status === 'A') {
                $status = 'TK';
            } // Konversi A ke TK
            $mappedData[] = ($status === '-' || ! $status) ? '-' : $status;
        }

        $hadir = isset($row->hadir) && $row->hadir !== false ? (int) $row->hadir : 0;
        $sakit = isset($row->sakit) && $row->sakit !== false ? (int) $row->sakit : 0;
        $izin = isset($row->izin) && $row->izin !== false ? (int) $row->izin : 0;
        $alpha = isset($row->alpha) && $row->alpha !== false ? (int) $row->alpha : 0;
        $percentage = isset($row->percentage) && $row->percentage !== false ? (int) $row->percentage : 0;

        array_push(
            $mappedData,
            $hadir,
            $sakit,
            $izin,
            $alpha,
            $percentage.'%',
            $percentage >= 75 ? 'Aman' : 'Peringatan',
            ''
        );

        return $mappedData;
    }
}
