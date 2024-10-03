<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\attendances;
use App\Models\Linmas;
use App\Models\payrolls;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        return view('payroll.index');
    }

    public function calculatePayroll(Request $request)
    {
        // Validate the request
        $request->validate([
            'start_month' => 'required|date_format:Y-m',
            'end_month' => 'required|date_format:Y-m',
        ]);

        // Get the start and end date
        $startDate = Carbon::parse($request->start_month)->startOfMonth();
        $endDate = Carbon::parse($request->end_month)->endOfMonth();

        // Fetch attendance data between the selected dates
        $attendances = attendances::whereBetween('waktu', [$startDate, $endDate])
            ->with('linmas') // Fetch related linmas data
            ->get();

        // Group attendance data by NIK and calculate the payroll
        $payrollData = $attendances->groupBy('linmas.nik')->map(function ($attendanceGroup, $nik) {
            $totalDaysWorked = $attendanceGroup->where('status', 'C/Masuk')->count();
            $totalOvertime = $attendanceGroup->where('status_baru', 'Lembur Masuk')->count();
            $dailyWage = 75000; // Example daily wage
            $overtimeWage = 10000; // Example overtime wage

            return [
                'nik' => $nik,
                'nama' => $attendanceGroup->first()->linmas->nama,
                'total_days_worked' => $totalDaysWorked,
                'total_overtime' => $totalOvertime,
                'total_wage' => ($totalDaysWorked * $dailyWage) + ($totalOvertime * $overtimeWage),
            ];
        });

        return view('payroll.index', compact('payrollData', 'startDate', 'endDate'));
    }
    public function exportPdf(Request $request)
    {
        // Mengambil data payroll dari request
        $payrollData = json_decode($request->payroll_data, true);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Generate PDF menggunakan DomPDF
        $pdf = Pdf::loadView('payroll.report', compact('payrollData', 'startDate', 'endDate'));

        // Mengunduh PDF
        return $pdf->download('laporan_penggajian.pdf');
    }
    public function exportSlip(Request $request, $nik)
    {
        // Ambil data payroll untuk NIK yang dipilih
        $payrollData = collect(json_decode($request->payroll_data, true))->firstWhere('nik', $nik);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Jika tidak ada data untuk NIK yang dimaksud
        if (!$payrollData) {
            return redirect()->back()->withErrors(['msg' => 'Data tidak ditemukan untuk NIK ini.']);
        }

        // Generate PDF slip gaji menggunakan DomPDF
        $pdf = PDF::loadView('payroll.slip', compact('payrollData', 'startDate', 'endDate'));

        // Mengunduh slip gaji PDF
        return $pdf->download('slip_gaji_' . $payrollData['nik'] . '.pdf');
    }
    public function storePayroll(Request $request)
    {
        // Ambil data dari request
        $payrollData = json_decode($request->payroll_data, true);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Loop melalui setiap data payroll dan simpan ke database
        foreach ($payrollData as $nik => $payroll) {
            payrolls::create([
                'nik' => $payroll['nik'], // Ambil NIK dari data payroll
                'nama' => $payroll['nama'],
                'total_days_worked' => $payroll['total_days_worked'],
                'total_overtime' => $payroll['total_overtime'],
                'total_wage' => $payroll['total_wage'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }

        return redirect()->route('payroll.index')->with('success', 'Data penggajian berhasil disimpan.');
    }
}
