<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MembersTemplateExport implements
    WithHeadings,
    WithStyles,
    WithColumnWidths
{
    public function headings(): array
    {
        return [
            'cid',
            'full_name',
            'first_name',
            'last_name',
            'middle_name',
            'birth_date',
            'gender',
            'share_account',
            'is_migs',
            'share_amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Add sample data in rows 2-4
        $sheet->fromArray([
            [
                'CID001',
                'Dela Cruz, Juan Santos',
                'Juan',
                'Dela Cruz',
                'Santos',
                '1990-01-15',
                'Male',
                'SA001',
                'TRUE',
                '5000.00',
            ],
            [
                'CID002',
                'Reyes, Maria Garcia',
                'Maria',
                'Reyes',
                'Garcia',
                '1985-05-20',
                'Female',
                'SA002',
                'TRUE',
                '7500.00',
            ],
            [
                'CID003',
                'Santos, Pedro Lopez',
                'Pedro',
                'Santos',
                'Lopez',
                '1992-11-30',
                'Male',
                'SA003',
                'FALSE',
                '3000.00',
            ],
        ], null, 'A2');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            '2:4' => [
                'font' => [
                    'italic' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0FDF4'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // cid
            'B' => 35,  // full_name
            'C' => 20,  // first_name
            'D' => 20,  // last_name
            'E' => 20,  // middle_name
            'F' => 15,  // birth_date
            'G' => 12,  // gender
            'H' => 15,  // share_account
            'I' => 10,  // is_migs
            'J' => 15,  // share_amount
        ];
    }
}
