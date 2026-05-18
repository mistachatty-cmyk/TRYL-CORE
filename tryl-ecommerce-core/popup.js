document.addEventListener('DOMContentLoaded', () => {
  
  // --- Tab Navigation ---
  const tabs = document.querySelectorAll('.lok-tab');
  const panels = document.querySelectorAll('.lok-panel');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      
      tab.classList.add('active');
      document.getElementById(tab.dataset.target).classList.add('active');
    });
  });

  // --- State & Settings ---
  let storeUrl = '';
  let apiKey = '';

  const urlInput = document.getElementById('store-url');
  const keyInput = document.getElementById('api-key');
  const statusDot = document.querySelector('.lok-status .dot');
  const statusText = document.querySelector('.lok-status');

  // Load settings from Chrome Storage
  chrome.storage.local.get(['trylStoreUrl', 'trylApiKey'], (result) => {
    if (result.trylStoreUrl && result.trylApiKey) {
      storeUrl = result.trylStoreUrl;
      apiKey = result.trylApiKey;
      urlInput.value = storeUrl;
      keyInput.value = apiKey;
      fetchLiveStats(); // Auto-fetch if configured
    } else {
      // Force user to settings tab if not configured
      document.querySelector('[data-target="tab-settings"]').click();
    }
  });

  // Save Settings
  document.getElementById('save-settings').addEventListener('click', () => {
    const url = urlInput.value.trim().replace(/\/$/, ''); // Remove trailing slash
    const key = keyInput.value.trim();
    const msgEl = document.getElementById('settings-msg');
    
    if (!url || !key) {
      showMessage(msgEl, 'Both fields are required.', 'error');
      return;
    }
    
    chrome.storage.local.set({ trylStoreUrl: url, trylApiKey: key }, () => {
      storeUrl = url;
      apiKey = key;
      showMessage(msgEl, 'Settings saved!', 'success');
      setTimeout(() => {
        document.querySelector('[data-target="tab-stats"]').click();
        fetchLiveStats();
      }, 1000);
    });
  });

  // --- API Functions ---
  function showMessage(el, text, type) {
    el.textContent = text;
    el.className = `lok-msg ${type}`;
    setTimeout(() => { el.textContent = ''; el.className = 'lok-msg'; }, 4000);
  }

  function setConnectionStatus(isOnline) {
    if (isOnline) {
      statusDot.className = 'dot green';
      statusText.innerHTML = '<span class="dot green"></span> Connected';
    } else {
      statusDot.className = 'dot red';
      statusText.innerHTML = '<span class="dot red"></span> Offline';
    }
  }

  function fetchLiveStats() {
    if (!storeUrl || !apiKey) return;
    
    const btn = document.getElementById('refresh-stats');
    btn.textContent = 'Fetching...';
    btn.disabled = true;

    fetch(`${storeUrl}/wp-json/tryl/v1/ecosystem-stats`, {
      headers: { 'X-TRYL-Extension-Key': apiKey }
    })
    .then(res => {
      if (!res.ok) throw new Error('Auth failed or site unreachable');
      return res.json();
    })
    .then(data => {
      setConnectionStatus(true);
      document.getElementById('stat-unfulfilled').textContent = data.unfulfilled_orders;
      document.getElementById('stat-products').textContent = data.synced_products;
      document.getElementById('stat-last-sync').textContent = data.last_catalog_sync;
      
      if (data.sandbox_active) {
        document.getElementById('sandbox-warning').classList.remove('hidden');
      } else {
        document.getElementById('sandbox-warning').classList.add('hidden');
      }
    })
    .catch(err => {
      setConnectionStatus(false);
      console.error(err);
    })
    .finally(() => {
      btn.textContent = 'Refresh Data';
      btn.disabled = false;
    });
  }

  document.getElementById('refresh-stats').addEventListener('click', fetchLiveStats);

  // --- Font Sync Pusher ---
  document.getElementById('push-font').addEventListener('click', () => {
    const fontName = document.getElementById('font-name').value.trim();
    const fontCss = document.getElementById('font-css').value.trim();
    const msgEl = document.getElementById('font-msg');
    const btn = document.getElementById('push-font');

    if (!fontName || !fontCss) { showMessage(msgEl, 'Both fields are required.', 'error'); return; }
    
    btn.textContent = 'Injecting...'; btn.disabled = true;

    fetch(`${storeUrl}/wp-json/tryl/v1/sync-font`, {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-TRYL-Extension-Key': apiKey },
      body: JSON.stringify({ font_family: fontName, css: fontCss })
    }).then(res => res.json()).then(data => { if (data.success) { showMessage(msgEl, 'Font injected successfully!', 'success'); document.getElementById('font-name').value = ''; document.getElementById('font-css').value = ''; } else { showMessage(msgEl, data.message || 'Error injecting font.', 'error'); } }).catch(() => { showMessage(msgEl, 'Network error.', 'error'); }).finally(() => { btn.textContent = 'Inject Font to Store'; btn.disabled = false; });
  });
});
