<!-- Tombol Logout -->
<button onclick="openLogoutModal()" 
        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
    <i class="fas fa-sign-out-alt mr-2"></i> Logout
</button>

<!-- Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm w-full">
        <h2 class="text-xl font-bold mb-4">Konfirmasi Logout</h2>
        <p class="text-gray-600 mb-6">Apakah kamu yakin ingin keluar?</p>
        <div class="flex justify-end space-x-2">
            <button onclick="closeLogoutModal()" 
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Batal
            </button>
            <a href="logout.php" 
               class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Ya, Keluar
            </a>
        </div>
    </div>
</div>

<script>
function openLogoutModal() {
    document.getElementById('logoutModal').classList.remove('hidden');
}
function closeLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
}
</script>
