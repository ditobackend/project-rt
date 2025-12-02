document.addEventListener("DOMContentLoaded", () => {
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");
    const closeBtn = document.getElementById("closeBtn");

    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", () => {
            sidebar.classList.remove("hidden");
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener("click", () => {
            sidebar.classList.add("hidden");
        });
    }

    // Klik di luar sidebar → tutup
    sidebar?.addEventListener("click", (e) => {
        if (e.target === sidebar) {
            sidebar.classList.add("hidden");
        }
    });
});
