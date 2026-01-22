<?php

// app/Http/Controllers/Admin/ComplaintController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ComplaintController extends Controller
{
    /**
     * Menampilkan halaman daftar keluhan.
     */
    public function index(): View
    {
        $complaints = Complaint::latest()->get();
        return view('admin.complaints.index', compact('complaints'));
    }

    /**
     * Menghapus data keluhan.
     */
    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        // Redirect kembali ke halaman daftar keluhan, bukan dashboard utama
        return redirect()->route('admin.complaints.index')
                         ->with('success', 'Keluhan pengguna berhasil dihapus.');
    }
}