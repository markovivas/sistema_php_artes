<?php if (Auth::check()): ?>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}

// Notification polling + mark as read
(function() {
    const notifBell = document.getElementById('notifBell');
    if (!notifBell) return;
    const notifList = document.getElementById('notifList');
    const notifCount = document.getElementById('notifCount');

    function pollNotifications() {
        fetch(BASE_URL + '/auth/notifications.php')
            .then(r => r.json())
            .then(data => {
                const count = data.count;
                notifCount.textContent = count;
                notifCount.style.display = count > 0 ? '' : 'none';

                if (data.html) {
                    notifList.innerHTML = data.html;
                    attachNotifEvents();
                }
            });
    }

    function attachNotifEvents() {
        notifList.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', function(e) {
                const id = this.dataset.id;
                fetch(BASE_URL + '/auth/notifications.php?read=' + id);
            });
        });
        const markBtn = document.getElementById('markAllRead');
        if (markBtn) {
            markBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetch(BASE_URL + '/auth/notifications.php?read_all=1')
                    .then(() => pollNotifications());
            });
        }
    }

    attachNotifEvents();
    setInterval(pollNotifications, 15000);
})();
</script>
</body>
</html>
