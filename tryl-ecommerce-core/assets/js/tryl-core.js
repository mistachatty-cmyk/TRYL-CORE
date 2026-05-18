/**
 * TRYL Premium E-Commerce Core: Master Scripts
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // Ensure trylCoreSettings global object exists safely
    window.trylCoreSettings = window.trylCoreSettings || {
        ajaxurl: '/wp-admin/admin-ajax.php',
        btnText: 'Added!',
        checkoutAnimations: '1',
        isCartOrCheckout: '0'
    };
    var trylCoreSettings = window.trylCoreSettings;
    
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
        window.trylOpenCart    = openCart;
        window.trylCloseCart   = closeCart;
        window.trylRefreshCart = refreshCart;
  
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

    // ── 5. SHOP GRID ATC & SIZE SELECTOR LOGIC ──
    document.addEventListener('click', function(e) {
        // Overlay Button triggers Footer interaction
        var quickAddBtn = e.target.closest('.tryl-quick-add-trigger');
        if (quickAddBtn) {
            e.preventDefault();
            var card = quickAddBtn.closest('.tryl-card, .te-card');
            if (card) {
                var toggle = card.querySelector('.tryl-atc-inline-toggle');
                if (toggle) toggle.click();
            }
            return;
        }

        // Toggle Size Inline Row
        var toggleBtn = e.target.closest('.tryl-atc-inline-toggle:not(.tryl-go-checkout)');
        if (toggleBtn) {
            e.preventDefault();
            var wrapper = toggleBtn.closest('.tryl-inline-var-wrapper');
            var dropdown = wrapper.querySelector('.tryl-inline-var-dropdown');
            
            // Close all other dropdowns first
            document.querySelectorAll('.tryl-inline-var-dropdown').forEach(function(el) {
                if (el !== dropdown) {
                    el.style.display = 'none';
                    var currentWrapper = el.closest('.tryl-inline-var-wrapper');
                    var currentToggle = currentWrapper ? currentWrapper.querySelector('.tryl-atc-inline-toggle') : null;
                    if (currentToggle) currentToggle.style.display = '';
                }
            });

            if (typeof gsap !== 'undefined') {
                gsap.to(toggleBtn, {
                    opacity: 0, scale: 0.8, duration: 0.2, onComplete: () => {
                        toggleBtn.style.display = 'none';
                    dropdown.style.display = 'block';
                    gsap.fromTo(dropdown, { opacity: 0, scale: 0.9, y: -10 }, { opacity: 1, scale: 1, y: 0, duration: 0.35, ease: 'power2.out' });
                }
                });
            } else {
                toggleBtn.style.display = 'none';
                dropdown.style.display = 'block';
            }
            return;
        }

        // Close dropdown when clicking outside
        if (!e.target.closest('.tryl-inline-var-wrapper')) {
            document.querySelectorAll('.tryl-inline-var-dropdown').forEach(function(el) {
                if (el.style.display !== 'none') {
                    el.style.display = 'none';
                    var wrapper = el.closest('.tryl-inline-var-wrapper');
                    var toggleBtn = wrapper ? wrapper.querySelector('.tryl-atc-inline-toggle') : null;
                    if (toggleBtn) {
                        toggleBtn.style.display = '';
                        if (typeof gsap !== 'undefined') gsap.fromTo(toggleBtn, { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.2 });
                    }
                }
            });
        }

        // Handle Add to Cart (Simple Product)
        var atcBtn = e.target.closest('.tryl-atc:not(.tryl-atc-choose)');
        if (atcBtn && atcBtn.dataset.pid && !atcBtn.classList.contains('disabled') && !atcBtn.classList.contains('loading')) {
            e.preventDefault();
            handleAddToCart(atcBtn, atcBtn.dataset.pid, 0);
            return;
        }

        // Handle Add to Cart (Variation Size Button)
        var varBtn = e.target.closest('.tryl-atc-variation');
        if (varBtn && varBtn.dataset.vid && !varBtn.classList.contains('loading')) {
            e.preventDefault();
            handleAddToCart(varBtn, varBtn.dataset.pid, varBtn.dataset.vid);
            return;
        }
    });

    function handleAddToCart(btn, productId, variationId) {
        var isVar = !!variationId;
        var card = btn.closest('.tryl-card');
        var wrapper = btn.closest('.tryl-inline-var-wrapper');
        var mainToggle = wrapper ? wrapper.querySelector('.tryl-atc-inline-toggle') : null;
        var dropdown = wrapper ? wrapper.querySelector('.tryl-inline-var-dropdown') : null;

        var targetBtn = btn;
        
        if (isVar && mainToggle && dropdown) {
            dropdown.style.display = 'none';
            mainToggle.style.display = '';
            targetBtn = mainToggle;
        }

        var ogText = targetBtn.innerHTML;
        targetBtn.classList.add('loading');
        targetBtn.innerHTML = '<span style="display:flex;align-items:center;gap:6px;justify-content:center;"><svg style="animation: spin 1s linear infinite;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg> Adding...</span>';

        var fd = new FormData();
        fd.append('action', 'tryl_ajax_add_to_cart');
        fd.append('product_id', productId);
        if (variationId) {
            fd.append('variation_id', variationId);
        }

        fetch(trylCoreSettings.ajaxurl, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                targetBtn.classList.remove('loading');
                targetBtn.classList.add('added');
                targetBtn.innerHTML = '<span style="display:flex;align-items:center;gap:6px;justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Added!</span>';
                
                setTimeout(() => {
                    targetBtn.classList.remove('added');
                    targetBtn.innerHTML = '<span style="display:flex;align-items:center;gap:6px;justify-content:center;">Checkout <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>';
                    targetBtn.style.backgroundColor = 'var(--dark, #0d1b0f)';
                    targetBtn.style.color = '#fff';
                    targetBtn.style.borderColor = 'var(--dark, #0d1b0f)';
                    targetBtn.classList.remove('tryl-atc-inline-toggle');
                    targetBtn.classList.add('tryl-go-checkout');
                    var checkoutUrl = (typeof trylMiniCart !== 'undefined' && trylMiniCart.checkoutUrl) ? trylMiniCart.checkoutUrl : '/checkout/';
                    targetBtn.onclick = function (e) { e.preventDefault(); window.location.href = checkoutUrl; };
                }, 1500);

                if (typeof gsap !== 'undefined') {
                    gsap.fromTo(targetBtn, { scale: 0.9, backgroundColor: '#31d190', color: '#fff' }, { scale: 1, duration: 0.35, ease: 'back.out(2)' });
                    if (card) {
                        gsap.fromTo(card,
                            { boxShadow: '0 0 0px rgba(49, 209, 144, 0)', borderColor: 'var(--border)' }, 
                            {
                                boxShadow: '0 0 20px rgba(49, 209, 144, 0.65)', borderColor: '#31d190', duration: 0.3, repeat: 1, yoyo: true, ease: 'power1.inOut', onComplete: () => {
                                    gsap.to(card, { boxShadow: '', borderColor: '', duration: 0.3 });
                                }
                            });
                    }
                }
                
                // Refresh Mini Cart using the globally exposed function
                if (typeof window.trylRefreshCart === 'function') {
                    window.trylRefreshCart(function () {
                        // Open cart after it has been refreshed
                        if (trylCoreSettings.autoOpen === '1' && typeof window.trylOpenCart === 'function') window.trylOpenCart();
                    });
                }
            } else {
                const errorMessage = res.data && res.data.message ? res.data.message : 'Error adding item.';
                targetBtn.innerHTML = `<span style="color: #d63638; font-size: 0.9em;">${errorMessage}</span>`;
                targetBtn.classList.remove('loading');
                setTimeout(() => { targetBtn.innerHTML = ogText; }, 3500);
            }
        })
        .catch(err => {
            console.error('Add to Cart AJAX Error:', err);
            targetBtn.innerHTML = '<span style="color: #d63638;">Network Error</span>';
            targetBtn.classList.remove('loading');
            setTimeout(() => { targetBtn.innerHTML = ogText; }, 3500);
        });
    }
});