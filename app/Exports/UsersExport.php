<?php

namespace App\Exports;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class UsersExport
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function export()
    {
        $query = User::where('role', 'client')->with('wallet');

        if (isset($this->filters['search']) && $this->filters['search']) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('is_active', $this->filters['status'] === 'active');
        }

        $users = $query->orderBy('name')->get();

        // Criar nova planilha
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuários');

        // Cabeçalhos
        $headers = [
            'ID',
            'Nome',
            'E-mail',
            'Telefone',
            'Código da Carteira',
            'Saldo (NDL)',
            'Status',
            'Data de Cadastro'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Estilizar cabeçalho
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Dados
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user->id);
            $sheet->setCellValue('B' . $row, $user->name);
            $sheet->setCellValue('C' . $row, $user->email);
            $sheet->setCellValue('D' . $row, $user->phone);
            $sheet->setCellValue('E' . $row, $user->wallet->code ?? '-');
            $sheet->setCellValue('F' . $row, number_format($user->wallet->balance ?? 0, 2, ',', '.'));
            $sheet->setCellValue('G' . $row, $user->is_active ? 'Ativo' : 'Bloqueado');
            $sheet->setCellValue('H' . $row, $user->created_at->format('d/m/Y H:i:s'));
            $row++;
        }

        // Ajustar largura das colunas
        $sheet->getColumnDimension('A')->setWidth(10);  // ID
        $sheet->getColumnDimension('B')->setWidth(30);  // Nome
        $sheet->getColumnDimension('C')->setWidth(35);  // E-mail
        $sheet->getColumnDimension('D')->setWidth(20);  // Telefone
        $sheet->getColumnDimension('E')->setWidth(20);  // Código da Carteira
        $sheet->getColumnDimension('F')->setWidth(15);  // Saldo
        $sheet->getColumnDimension('G')->setWidth(15);  // Status
        $sheet->getColumnDimension('H')->setWidth(20);  // Data de Cadastro

        return $spreadsheet;
    }
}
