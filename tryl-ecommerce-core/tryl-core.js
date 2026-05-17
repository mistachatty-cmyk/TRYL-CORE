/**
 * TRYL Premium E-Commerce Core: Master Scripts
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // ── 1. GLOBAL NAVIGATION & THEME SWITCHER ──
    var hamburger = document.getElementById('trylHamburger');
    var mobileNav = document.getElementById('trylMobileNav');
    
    if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('open');
            mobileNav.classList.toggle('open');
        });
    }
    
    var currentTheme = document.documentElement.getAttribute('data-theme') || 'bright';
    updateActiveBtns(currentTheme);

    document.querySelectorAll('[data-set-theme]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var theme = btn.getAttribute('data-set-theme');
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('tryl_theme', theme);
            updateActiveBtns(theme);
        });
    });
    
    function updateActiveBtns(theme) {
        document.querySelectorAll('[data-set-theme]').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('[data-set-theme="'+theme+'"]').forEach(function(b) { b.classList.add('active'); });
    }

    // ── 2. CHECKOUT GSAP ANIMATIONS ──
    if (typeof gsap !== "undefined" && trylCoreSettings.checkoutAnimations === '1' && trylCoreSettings.isCartOrCheckout === '1') {
        var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

        if (document.body.classList.contains("woocommerce-cart")) {
            tl.fromTo(".woocommerce", { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.6 });
        }

        if (document.body.classList.contains("woocommerce-checkout")) {
            var grid = document.querySelector(".tryl-checkout-grid");
            var fields = document.querySelectorAll(".tryl-checkout-main .form-row");
            var sidebar = document.querySelector(".tryl-checkout-sidebar");

            if (grid) tl.fromTo(grid, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.5 }, 0);
            if (fields.length) tl.fromTo(fields, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.35, stagger: 0.025 }, "-=0.15");
            if (sidebar) tl.fromTo(sidebar, { opacity: 0, x: 16 }, { opacity: 1, x: 0, duration: 0.45 }, "-=0.25");

            var placeBtn = document.getElementById("place_order");
            if (placeBtn) {
                placeBtn.addEventListener("mousemove", function(e) {
                    var r = this.getBoundingClientRect();
                    var x = (e.clientX - r.left - r.width / 2) * 0.15;
                    var y = (e.clientY - r.top - r.height / 2) * 0.15;
                    gsap.to(this, { x: x, y: y, duration: 0.25, ease: "power2.out" });
                });
                placeBtn.addEventListener("mouseleave", function() {
                    gsap.to(this, { x: 0, y: 0, duration: 0.35, ease: "power2.out" });
                });
            }
        }
    }

    // ── 3. EXIT INTENT POPUP ──
    if (!localStorage.getItem('tryl_popup_closed')) {
        var popupTriggered = false;
        var popupOverlay = document.getElementById('trylExitPopup');
        var popupCloseBtn = document.getElementById('trylPopupClose');
        
        if (popupOverlay) {
            function showPopup() {
                if (popupTriggered) return;
                popupTriggered = true;
                popupOverlay.classList.add('show');
                if (window.gsap) gsap.fromTo(".tryl-popup-content", { scale: 0.9, opacity: 0, y: 30 }, { scale: 1, opacity: 1, y: 0, duration: 0.8, ease: "expo.out" });
            }
            function closePopup() {
                if (window.gsap) {
                    gsap.to(".tryl-popup-content", {
                        scale: 0.95, opacity: 0, y: 10, duration: 0.4, ease: "power2.in",
                        onComplete: function() { popupOverlay.classList.remove('show'); localStorage.setItem('tryl_popup_closed', 'true'); }
                    });
                } else {
                    popupOverlay.classList.remove('show'); localStorage.setItem('tryl_popup_closed', 'true');
                }
            }
            document.addEventListener('mouseout', function(e) { if (e.clientY < 10 && e.clientY > -200) { showPopup(); } });
            setTimeout(function() { if (!popupTriggered) showPopup(); }, 30000);
            if (popupCloseBtn) popupCloseBtn.addEventListener('click', closePopup);
            popupOverlay.addEventListener('click', function(e) { if (e.target === popupOverlay) closePopup(); });
            var form = popupOverlay.querySelector('form');
            if (form) form.addEventListener('submit', function() { setTimeout(closePopup, 500); });
        }
    }
    
    // ── 4. MINI CART DRAWER LOGIC ──
    (function(){
        var overlay = document.getElementById('trylMcOverlay');
        var drawer  = document.getElementById('trylMcDrawer');
        var close   = document.getElementById('trylMcClose');
        if (!overlay || !drawer) return;
  
        var hasGSAP = typeof gsap !== 'undefined';
        var openTimeline = null;
        
        function openCart() {
          overlay.classList.add('open');
          if (hasGSAP) {
            if (openTimeline) openTimeline.kill();
            openTimeline = gsap.timeline();
            openTimeline.to(overlay, { opacity: 1, duration: 0.3, ease: 'power2.out' }, 0).to(drawer, { x: '0%', duration: 0.45, ease: 'power3.out' }, 0);
          } else { drawer.style.transform = 'translateX(0%)'; }
        }
        
        function closeCart() {
          overlay.classList.remove('open');
          if (hasGSAP) {
            gsap.to(drawer,  { x: '100%', duration: 0.35, ease: 'power2.in' });
            gsap.to(overlay, { opacity: 0, duration: 0.3, ease: 'power2.in' });
          } else { drawer.style.transform = 'translateX(100%)'; }
        }
        
        if (close) close.addEventListener('click', closeCart);
        overlay.addEventListener('click', closeCart);
        window.trylOpenCart  = openCart;
        window.trylCloseCart = closeCart;
  
        function updateCounts(count) {
          document.querySelectorAll('.tryl-cart-count').forEach(function(el){ el.textContent = count; });
          document.querySelectorAll('.tryl-cart-count-badge').forEach(function(el){ el.style.display = count > 0 ? 'flex' : 'none'; });
        }
  
        function refreshCart(callback) {
          fetch(trylCoreSettings.ajaxurl + '?action=tryl_refresh_minicart', { method: 'GET' })
          .then(function(r){ return r.json(); }).then(function(res){
            if (!res.success) return;
            var itemsEl = document.getElementById('trylMcItems');
            var footerEl = document.getElementById('trylMcFooter');
            var subtotalEl = document.getElementById('trylMcSubtotal');
            var freeShip = document.getElementById('trylMcFreeShip');
            if (itemsEl) itemsEl.innerHTML = res.data.html;
            if (subtotalEl) subtotalEl.innerHTML = res.data.subtotal;
            if (footerEl) footerEl.style.display = res.data.count > 0 ? '' : 'none';
            if (freeShip) freeShip.textContent = res.data.free_ship || '';
            updateCounts(res.data.count);
            bindQtyButtons();
            if (typeof callback === 'function') callback(res);
          });
        }
        
        // Cart qty / remove handlers
        function bindQtyButtons() {
          document.querySelectorAll('.tryl-mc-qty-dec, .tryl-mc-qty-inc').forEach(function(el) {
            el.onclick = function(e) {
              var b = e.target.closest('button');
              if (!b) return;
              var key = b.dataset.key;
              var inc = b.classList.contains('tryl-mc-qty-inc');
              var num = b.parentElement.querySelector('.tryl-mc-qty-num');
              var cur = parseInt(num.textContent) || 1;
              var qty = inc ? cur + 1 : Math.max(0, cur - 1);
              updateQty(key, qty);
            };
          });
          document.querySelectorAll('.tryl-mc-item-remove').forEach(function(el) { el.onclick = function(e) { var b = e.target.closest('button'); if (b) updateQty(b.dataset.key, 0); }; });
        }
        
         function updateQty(key, qty) {
           var fd = new FormData();
           fd.append('action', 'tryl_update_cart'); fd.append('cart_key', key); fd.append('quantity', qty);
           fetch(trylCoreSettings.ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
           .then(function(r){ return r.json(); }).then(function(res){ if (res.success) refreshCart(); });
         }
        bindQtyButtons();
    })();
});