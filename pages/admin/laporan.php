<h2 class="text-2xl font-bold mb-4">Laporan</h2>
<p class="mb-6 text-gray-600">Generate laporan keuangan RT</p>

<!-- Filter Bar -->
<div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 mb-6 w-full">
    <select class="border px-3 py-2 rounded-lg w-full sm:w-auto">
        <option>Januari 2024</option>
        <option>Februari 2024</option>
    </select>
    <select class="border px-3 py-2 rounded-lg w-full sm:w-auto">
        <option>Semua Jenis</option>
        <option>Pemasukan</option>
        <option>Pengeluaran</option>
    </select>
    <button class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
        <i class="fas fa-filter"></i> Filter
    </button>
</div>

<!-- Table -->
<div class="bg-white shadow rounded-xl overflow-x-auto mb-6">
    <table class="w-full text-left min-w-[600px]">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Keterangan</th>
                <th class="px-6 py-3">Jenis</th>
                <th class="px-6 py-3">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t">
                <td class="px-6 py-3">10 Jan 2024</td>
                <td class="px-6 py-3">Iuran Bulanan Warga</td>
                <td class="px-6 py-3">
                    <span class="bg-green-100 text-green-600 px-2 py-1 rounded">Pemasukan</span>
                </td>
                <td class="px-6 py-3 text-green-600 font-bold">+ Rp 5.000.000</td>
            </tr>
            <tr class="border-t">
                <td class="px-6 py-3">08 Jan 2024</td>
                <td class="px-6 py-3">Pembelian Alat Kebersihan</td>
                <td class="px-6 py-3">
                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded">Pengeluaran</span>
                </td>
                <td class="px-6 py-3 text-red-600 font-bold">- Rp 500.000</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Export Buttons -->
<div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 w-full">
    <a href="exports/laporan_pdf.php" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto text-center">
        <i class="fas fa-file-pdf mr-2"></i> Export PDF
    </a>
    <a href="exports/laporan_excel.php" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto text-center">
        <i class="fas fa-file-excel mr-2"></i> Export Excel
    </a>
</div>
