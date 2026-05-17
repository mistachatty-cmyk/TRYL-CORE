# TRYL Shop Page Size Selection Enhancement - Technical Specifications

## 1. Template Modifications (tryl-ecommerce-core.php)

### 1.1 Product Card Template Changes
Modify the `tryl_get_core_product_card_html()` function to replace the existing variable product size selector:

**Current Structure:**
```php
<div class="tryl-inline-var-wrapper" style="position:relative;">
  <button class="tryl-atc tryl-atc-choose tryl-atc-inline-toggle" type="button">
    <!-- SVG + "Select Size" text -->
  </button>
  <div class="tryl-inline-var-dropdown" style="display:none; ..."> <!-- Options --> </div>
</div>
```

**Proposed Structure:**
```php
<div class="tryl-size-selector-wrapper" style="position:relative;">
  <button class="tryl-atc tryl-atc-choose tryl-size-toggle" type="button" aria-label="Select size">
    <!-- SVG + "Select Size" text -->
  </button>
  <div class="tryl-size-options-panel" style="display:none; position:absolute; bottom:calc(100% + 8px); right:0; background:var(--card-bg); border:1px solid var(--border); box-shadow:0 10px 30px rgba(0,0,0,0.1); z-index:100; border-radius:4px; overflow:hidden; opacity:0; transform:scaleY(0); transform-origin:top;">
    <div style="font-size:0.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px; padding:8px 12px; border-bottom:1px solid var(--border); text-align:center;">Select Size</div>
    <div style="display:flex; flex-direction:column; gap:4px; max-height:200px; overflow-y:auto; padding:0 12px 12px;">
      <!-- Size option buttons -->
    </div>
  </div>
</div>
```

### 1.2 CSS Enhancements
Add to the existing stylesheet in the shortcode:

```css
/* Size Selector Animations */
.tryl-size-options-panel {
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  will-change: opacity, transform;
}

.tryl-size-selector-wrapper:hover .tryl-size-options-panel,
.tryl-size-selector-wrapper .tryl-size-options-panel.active {
  display: block !important;
  opacity: 1;
  transform: scaleY(1);
}

.tryl-size-option {
  width: 100%;
  text-align: left;
  padding: 10px 12px;
  background: var(--off);
  border: 1px solid var(--border);
  cursor: pointer;
  font-family: var(--tryl-body-font);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--txt);
  transition: all 0.2s;
  border-radius: 4px;
}

.tryl-size-option:hover {
  background: var(--dark);
  color: var(--btn-txt);
}

/* Active state for selected size */
.tryl-size-option.selected {
  background: var(--accent);
  color: var(--dark);
}

/* Mobile touch target enhancement */
@media (max-width: 768px) {
  .tryl-size-option {
    padding: 14px 12px;
    font-size: 0.85rem;
  }
}
```

### 1.3 Simple Product Button Update
For consistency, update simple product buttons to use the same styling:

```php
<?php else: ?>
<button class="tryl-atc tryl-size-toggle" data-pid="<?php echo $pid;?>" aria-label="Add to Cart">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
  <span>Add to Cart</span>
</button>
<?php endif; ?>
```

## 2. JavaScript Enhancements (tryl-core.js)

### 2.1 Size Selection Handler
Add this to the DOMContentLoaded event listener:

```javascript
// ── SIZE SELECTION ENHANCEMENT ─────────────────────────────────────
(function(){
  // Event delegation for size toggle buttons
  document.body.addEventListener('click', function(e) {
    const toggleBtn = e.target.closest('.tryl-size-toggle');
    if (!toggleBtn) return;
    
    e.preventDefault();
    
    const wrapper = toggleBtn.closest('.tryl-size-selector-wrapper');
    if (!wrapper) return;
    
    const panel = wrapper.querySelector('.tryl-size-options-panel');
    const isOpen = panel.classList.contains('active');
    
    // Close any other open panels
    document.querySelectorAll('.tryl-size-options-panel.active').forEach(p => {
      if (p !== panel) p.classList.remove('active');
    });
    
    // Toggle current panel
    if (!isOpen) {
      openSizePanel(panel, wrapper);
    } else {
      closeSizePanel(panel);
    }
  });
  
  // Event delegation for size option selection
  document.body.addEventListener('click', function(e) {
    const sizeOption = e.target.closest('.tryl-size-option');
    if (!sizeOption) return;
    
    e.preventDefault();
    
    const productId = sizeOption.dataset.pid;
    const variationId = sizeOption.dataset.vid;
    
    if (!productId) return;
    
    // Add to cart via AJAX
    addToCartFromShop(productId, variationId, sizeOption);
  });
  
  function openSizePanel(panel, wrapper) {
    panel.classList.add('active');
    gsap.to(panel, {
      opacity: 1,
      scaleY: 1,
      duration: 0.3,
      ease: "power3.out",
      onComplete: () => {
        // Focus first option for accessibility
        const firstOption = panel.querySelector('.tryl-size-option');
        if (firstOption) firstOption.focus();
      }
    });
  }
  
  function closeSizePanel(panel) {
    gsap.to(panel, {
      opacity: 0,
      scaleY: 0.8,
      duration: 0.2,
      ease: "power2.in",
      onComplete: () => {
        panel.classList.remove('active');
      }
    });
  }
  
  function addToCartFromShop(productId, variationId, clickedButton) {
    // Prevent multiple clicks
    if (clickedButton.classList.contains('loading')) return;
    
    clickedButton.classList.add('loading');
    
    const formData = new FormData();
    formData.append('action', 'tryl_ajax_add_to_cart');
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    if (variationId) formData.append('variation_id', variationId);
    
    fetch(trylCoreSettings.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
    .then(response => response.json())
    .then(result => {
      clickedButton.classList.remove('loading');
      
      if (result.success) {
        // Update mini-cart
        refreshCart();
        
        // Provide visual feedback
        provideFeedback(clickedButton, result.data);
        
        // Update button state temporarily
        const originalHtml = clickedButton.innerHTML;
        clickedButton.innerHTML = '<span>Added!</span>';
        setTimeout(() => {
          clickedButton.innerHTML = originalHtml;
        }, 1500);
      } else {
        // Show error
        alert(result.message || 'Could not add to cart');
      }
    })
    .catch(error => {
      clickedButton.classList.remove('loading');
      console.error('AJAX error:', error);
      alert('Network error. Please try again.');
    });
  }
  
  function provideFeedback(button, cartData) {
    const feedbackType = getOption('tryl_size_feedback_type', 'glow'); // Default to glow
    
    switch(feedbackType) {
      case 'glow':
        glowFeedback(button);
        break;
      case 'scaleWithNumber':
        scaleWithNumberFeedback(button, cartData);
        break;
      case 'miniPulse':
        miniPulseFeedback(button);
        break;
      default:
        glowFeedback(button);
    }
  }
  
  function glowFeedback(button) {
    const card = button.closest('.tryl-card');
    if (!card) return;
    
    const originalBg = card.style.boxShadow;
    gsap.to(card, {
      boxShadow: `0 0 0 4px var(--accent)`,
      duration: 0.3,
      repeat: 1,
      yoyo: true,
      ease: "power2.out",
      onComplete: () => {
        card.style.boxShadow = originalBg;
      }
    });
  }
  
  function scaleWithNumberFeedback(button, cartData) {
    const cartIcon = document.querySelector('.tryl-cart-count-badge');
    if (!cartIcon) return;
    
    // Scale up cart icon
    gsap.fromTo(cartIcon, 
      { scale: 1 }, 
      { 
        scale: 1.3, 
        duration: 0.3, 
        ease: "back.out(1.7)",
        onComplete: () => {
          gsap.to(cartIcon, { scale: 1, duration: 0.2, ease: "power2.in" });
        }
      }
    );
    
    // Animate number increase
    const countEl = document.querySelector('.tryl-cart-count');
    if (countEl) {
      const currentCount = parseInt(countEl.textContent) || 0;
      const newCount = currentCount + 1;
      
      gsap.fromTo(countEl,
        { opacity: 0, y: -5 },
        {
          opacity: 1,
          y: 0,
          duration: 0.3,
          ease: "power2.out",
          onUpdate: function() {
            countEl.textContent = Math.round(this.targets()[0]._gsap.getProperty(this.targets()[0], "y") < 0 ? currentCount : newCount);
          }
        }
      );
    }
  }
  
  function miniPulseFeedback(button) {
    gsap.fromTo(button, 
      { scale: 1 }, 
      { 
        scale: 1.05, 
        duration: 0.15, 
        ease: "power2.out",
        yoyo: true,
        repeat: 1
      }
    );
  }
  
  function getOption(optionName, defaultValue) {
    // Try to get from data attributes or fallback to default
    // In a real implementation, this might check dashboard settings
    return defaultValue;
  }
  
  // Close panels when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.tryl-size-selector-wrapper')) {
      document.querySelectorAll('.tryl-size-options-panel.active').forEach(panel => {
        closeSizePanel(panel);
      });
    }
  });
  
  // Escape key closes panels
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.tryl-size-options-panel.active').forEach(panel => {
        closeSizePanel(panel);
      });
    }
  });
})();
```

### 2.2 Integration with Existing Systems
The implementation leverages:
- Existing `tryl_ajax_add_to_cart` AJAX endpoint
- Existing `refreshCart()` function from mini-cart system
- Existing GSAP animation patterns
- Existing CSS variable system (`--accent`, `--dark`, etc.)

## 3. Visual Feedback Options

### 3.1 Glow Effect
- Product card gets a pulsating glow in the accent color
- Uses existing CSS variables for theme consistency
- Subtle but noticeable confirmation

### 3.2 Scale with Number
- Cart icon scales up and back down
- Cart count number animates upward
- Provides clear quantitative feedback

### 3.3 Mini Pulse
- Simple scale pulse on the button itself
- Most subtle option
- Good for users who prefer minimal feedback

## 4. Mobile Responsiveness

### 4.1 Touch Targets
- Minimum 44x44px touch targets for size options
- Increased padding on mobile viewports
- Font size adjustments for readability

### 4.2 Panel Behavior
- Slide-out panels work with touch events
- Panels can be swiped away (could be enhanced)
- Proper viewport containment to prevent overflow

### 4.3 Performance Considerations
- GSAP hardware acceleration
- Minimal DOM reads/writes in animation loops
- Event delegation to minimize listeners

## 5. Configuration Options (Future Dashboard Integration)

### 5.1 Size Selector Style
- Slide-out (current implementation)
- Toggle-in-place (alternative)
- Dropdown (existing behavior)

### 5.2 Feedback Animation Type
- Glow (default)
- Scale with Number
- Mini Pulse
- None (for performance-sensitive users)

### 5.3 Animation Customization
- Duration sliders
- Easing function selectors
- Intensity controls

## 6. Accessibility Considerations

### 6.1 Keyboard Navigation
- Enter/Space activates size toggle
- Arrow keys navigate between size options
- Enter selects highlighted option
- Escape closes panel
- Focus management when opening/closing panels

### 6.2 Screen Reader Support
- Proper ARIA labels on buttons
- Live regions for status updates (if needed)
- Semantic HTML structure

## 7. Performance Optimization

### 7.1 Animation Efficiency
- Use GSAP's autoCSS property detection
- Animate transform and opacity for best performance
- Will-change properties for known animations

### 7.2 DOM Efficiency
- Event delegation minimizes listener count
- Efficient selector usage
- Minimize layout thrashing

### 7.3 AJAX Optimization
- Debounce rapid clicks
- Show loading states
- Handle error states gracefully

## 8. Testing Guidelines

### 8.1 Cross-Browser Testing
- Chrome, Firefox, Safari, Edge
- Mobile browsers (iOS Safari, Android Chrome)

### 8.2 Responsive Testing
- Various screen widths (320px, 768px, 1024px, 1440px)
- Orientation changes
- Touch vs mouse interaction

### 8.3 Edge Cases
- Out of stock variations
- Network failures during AJAX
- Rapid clicking
- Disabled JavaScript fallback

## 9. Implementation Notes

### 9.1 Backward Compatibility
- No changes to existing AJAX endpoints
- Existing mini-cart functionality unaffected
- Theme switching still works via CSS variables

### 9.2 Code Organization
- Follows existing patterns in tryl-core.js
- Uses same GSAP easing and duration conventions
- Maintains consistent variable naming

### 9.3 Extensibility
- Easy to add new feedback types
- Simple to modify animation parameters
- Modular design allows feature toggles