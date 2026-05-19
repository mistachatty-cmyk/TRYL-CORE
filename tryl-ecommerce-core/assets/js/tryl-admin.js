jQuery(document).ready(function ($) {
    const navLinks = document.querySelectorAll('.tryl-admin-nav-link');

    // Safe Unseen Dot Tracking Logic
    const visitedTabsKey = 'tryl_visited_tabs_v350';
    let visitedTabs = [];
    try {
        const parsed = JSON.parse(localStorage.getItem(visitedTabsKey));
        visitedTabs = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        visitedTabs = [];
    }

    // Get the current tab from the URL
    const urlParams = new URLSearchParams(window.location.search);
    let currentTab = urlParams.get('tab');
    if (!currentTab) currentTab = 'general';

    // Mark current tab as visited
    if (!visitedTabs.includes(currentTab)) {
        visitedTabs.push(currentTab);
        try {
            localStorage.setItem(visitedTabsKey, JSON.stringify(visitedTabs));
        } catch (e) { }
    }

    // Initialize dots on load
    navLinks.forEach(link => {
        const tabId = link.getAttribute('data-tab');
        if (tabId && !visitedTabs.includes(tabId)) {
            const dot = link.querySelector('.tryl-nav-dot');
            if (dot) dot.classList.add('unseen');
        } else if (tabId) {
            const dot = link.querySelector('.tryl-nav-dot');
            if (dot) dot.classList.remove('unseen');
        }
    });

    // Handle PDF download button
    const pdfButton = document.getElementById('tryl-download-pdf');
    if (pdfButton) {
        pdfButton.addEventListener('click', function () {
            window.print();
        });
    }

    // Handle Purge Test Orders
    const purgeBtn = document.getElementById('tryl_purge_test_orders');
    if (purgeBtn && typeof trylAdminSettings !== 'undefined') {
        purgeBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you want to permanently delete all WooCommerce test orders? This action cannot be undone.')) return;
            const ogText = this.textContent;
            this.textContent = 'Purging...';
            this.style.opacity = '0.5';

            const fd = new FormData();
            fd.append('action', 'tryl_purge_test_orders');
            fd.append('security', trylAdminSettings.nonce);

            fetch(trylAdminSettings.ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.data && res.data.message ? res.data.message : 'Purge complete.');
                    window.location.reload();
                }).catch(e => {
                    alert('Error purging orders.');
                    this.textContent = ogText;
                    this.style.opacity = '1';
                });
        });
    }

    // Handle Force Sync
    const forceSyncBtn = document.getElementById('tryl_force_full_sync');
    if (forceSyncBtn && typeof trylAdminSettings !== 'undefined') {
        forceSyncBtn.addEventListener('click', function () {
            const ogText = this.innerHTML;
            this.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top:2px;"></span> Syncing...';
            this.style.opacity = '0.5';
            this.style.pointerEvents = 'none';

            const fd = new FormData();
            fd.append('action', 'tryl_force_full_sync');
            fd.append('security', trylAdminSettings.nonce);

            fetch(trylAdminSettings.ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.data && res.data.message ? res.data.message : 'Sync complete.');
                    this.innerHTML = ogText;
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }).catch(e => {
                    alert('Error running sync.');
                    this.innerHTML = ogText;
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                });
        });
    }

    // Handle DB Cleanup
    const cleanupBtn = document.getElementById('tryl_cleanup_db');
    if (cleanupBtn && typeof trylAdminSettings !== 'undefined') {
        cleanupBtn.addEventListener('click', function () {
            if (!confirm('This will permanently delete metadata for products that no longer exist. Proceed?')) return;
            const fd = new FormData();
            fd.append('action', 'tryl_cleanup_db');
            fd.append('security', trylAdminSettings.nonce);

            fetch(trylAdminSettings.ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.data && res.data.message ? res.data.message : 'Cleanup complete.');
                })
                .catch(e => {
                    alert('Error running cleanup.');
                });
        });
    }
});