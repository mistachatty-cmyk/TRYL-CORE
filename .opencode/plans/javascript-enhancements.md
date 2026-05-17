# JavaScript Enhancements for TRYL Shop Page Size Selection

## Overview
This document specifies the JavaScript enhancements needed to implement enhanced size selection functionality on the TRYL shop page, including slide-out animations, AJAX add-to-cart, and visual feedback systems.

## Core Functionality

### 1. Size Selector Toggle System
- Event delegation for efficient handling of dynamically generated product cards
- Click handling on `.tryl-size-toggle` buttons
- Panel state management (open/close)
- Accessibility considerations (focus trapping, keyboard navigation)

### 2. Size Option Selection
- Event delegation for `.tryl-size-option` buttons
- AJAX add-to-cart functionality using existing endpoints
- Loading states and error handling
- Integration with mini-cart update system

### 3. Visual Feedback System
- Three optional feedback styles configurable via dashboard
- GSAP-based animations for smooth, performant effects
- Consistent with existing animation patterns in tryl-core.js

## Detailed Implementation

### 1. DOMContentLoaded Extension
Add this to the existing DOMContentLoaded listener in tryl-core.js:

```javascript
// ── SIZE SELECTION ENHANCEMENT ─────────────────────────────────────
(function(){
  // State tracking
  let isAnimating = false;
  
  // Event delegation for size toggle buttons
  document.body.addEventListener('click', function(e) {
    // Prevent interference with animations
    if (isAnimating) return;
    
    const toggleBtn = e.target.closest('.tryl-size-toggle');
    if (!toggleBtn) return;
    
    e.preventDefault();
    
    const wrapper = toggleBtn.closest('.tryl-size-selector-wrapper');
    if (!wrapper) return;
    
    const panel = wrapper.querySelector('.tryl-size-options-panel');
    if (!panel) return;
    
    const isOpen = panel.classList.contains('active');
    
    // Close any other open panels
    document.querySelectorAll('.tryl-size-options-panel.active').forEach(p => {
      if (p !== panel) closeSizePanel(p);
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
    if (isAnimating) return;
    
    const sizeOption = e.target.closest('.tryl-size-option');
    if (!sizeOption) return;
    
    // Skip if button is disabled or in loading state
    if (sizeOption.disabled || sizeOption.classList.contains('loading')) return;
    
    e.preventDefault();
    
    const productId = sizeOption.dataset.pid;
    const variationId = sizeOption.dataset.vid;
    
    if (!productId) return;
    
    // Add to cart via AJAX
    addToCartFromShop(productId, variationId, sizeOption);
  });
  
  function openSizePanel(panel, wrapper) {
    isAnimating = true;
    
    // Ensure panel is in the DOM for measurement
    panel.style.display = 'block';
    panel.classList.add('active');
    
    // GSAP animation for slide-out effect
    gsap.fromTo(panel, 
      { 
        opacity: 0, 
        scaleY: 0,
        transformOrigin: 'top'
      }, 
      { 
        opacity: 1, 
        scaleY: 1,
        duration: 0.3,
        ease: "power3.out",
        onComplete: () => {
          isAnimating = false;
          // Focus first option for accessibility
          const firstOption = panel.querySelector('.tryl-size-option:not(.disabled)');
          if (firstOption) firstOption.focus();
        }
      }
    );
  }
  
  function closeSizePanel(panel) {
    isAnimating = true;
    
    gsap.to(panel, {
      opacity: 0,
      scaleY: 0.8,
      transformOrigin: 'top',
      duration: 0.2,
      ease: "power2.in",
      onComplete: () => {
        panel.classList.remove('active');
        panel.style.display = 'none'; // Hide from layout when closed
        isAnimating = false;
      }
    });
  }
  
  function addToCartFromShop(productId, variationId, clickedButton) {
    // Prevent multiple clicks
    if (clickedButton.classList.contains('loading')) return;
    
    clickedButton.classList.add('loading');
    // Optional: add visual loading indicator
    const originalHtml = clickedButton.innerHTML;
    clickedButton.innerHTML = '<span>Adding...</span>';
    
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
    .then(response => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    })
    .then(result => {
      clickedButton.classList.remove('loading');
      clickedButton.innerHTML = originalHtml; // Restore original content
      
      if (result.success) {
        // Update mini-cart
        refreshCart();
        
        // Provide visual feedback
        provideFeedback(clickedButton, result.data);
        
        // Update button state temporarily
        clickedButton.innerHTML = '<span>Added!</span>';
        clickedButton.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
        clickedButton.style.color = getComputedStyle(document.documentElement).getPropertyValue('--dark').trim();
        
        setTimeout(() => {
          clickedButton.innerHTML = originalHtml;
          // Restore original styles
          clickedButton.style.backgroundColor = '';
          clickedButton.style.color = '';
        }, 1500);
      } else {
        // Show error
        alert(result.message || 'Could not add to cart');
      }
    })
    .catch(error => {
      clickedButton.classList.remove('loading');
      clickedButton.innerHTML = originalHtml;
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
    
    // Store original styles
    const originalBoxShadow = card.style.boxShadow;
    const originalTransform = card.style.transform;
    
    gsap.to(card, {
      boxShadow: `0 0 0 4px var(--accent)`,
      scale: 1.02,
      duration: 0.3,
      repeat: 1,
      yoyo: true,
      ease: "power2.out",
      onComplete: () => {
        // Restore original styles
        card.style.boxShadow = originalBoxShadow;
        card.style.transform = originalTransform;
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
            // Simple approach: animate the number directly
            const progress = this.progress();
            const interpolatedCount = Math.round(currentCount + (newCount - currentCount) * progress);
            countEl.textContent = interpolatedCount;
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
    // In a real implementation with dashboard settings, this would check:
    // const optionValue = localStorage.getItem(optionName) || 
    //                    document.documentElement.getAttribute(`data-${optionName}`) ||
    //                    defaultValue;
    // For now, we'll use the default but leave room for expansion
    return defaultValue;
  }
  
  // Close panels when clicking outside
  document.addEventListener('click', function(e) {
    if (isAnimating) return;
    
    if (!e.target.closest('.tryl-size-selector-wrapper')) {
      document.querySelectorAll('.tryl-size-options-panel.active').forEach(panel => {
        closeSizePanel(panel);
      });
    }
  });
  
  // Escape key closes panels
  document.addEventListener('keydown', function(e) {
    if (isAnimating) return;
    
    if (e.key === 'Escape') {
      document.querySelectorAll('.tryl-size-options-panel.active').forEach(panel => {
        closeSizePanel(panel);
      });
    }
  });
  
  // Keyboard navigation within size panels
  document.addEventListener('keydown', function(e) {
    if (isAnimating) return;
    
    const activePanel = document.querySelector('.tryl-size-options-panel.active');
    if (!activePanel) return;
    
    const options = Array.from(activePanel.querySelectorAll('.tryl-size-option:not(.disabled)'));
    if (options.length === 0) return;
    
    let currentIndex = options.findIndex(opt => opt === document.activeElement);
    
    switch(e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (currentIndex === -1) {
          options[0].focus();
        } else {
          const nextIndex = (currentIndex + 1) % options.length;
          options[nextIndex].focus();
        }
        break;
        
      case 'ArrowUp':
        e.preventDefault();
        if (currentIndex === -1) {
          options[options.length - 1].focus();
        } else {
          const prevIndex = (currentIndex - 1 + options.length) % options.length;
          options[prevIndex].focus();
        }
        break;
        
      case 'Enter':
      case ' ':
        e.preventDefault();
        if (currentIndex !== -1) {
          options[currentIndex].click();
        }
        break;
        
      case 'Escape':
        closeSizePanel(activePanel);
        break;
    }
  });
})();
```

## Integration Points

### 1. AJAX Endpoint Usage
- Uses existing `tryl_ajax_add_to_cart` endpoint
- Leverages existing `refreshCart()` function for mini-cart updates
- Maintains compatibility with current WooCommerce integration

### 2. Animation Consistency
- Follows existing GSAP patterns from tryl-core.js
- Uses same easing functions (`power3.out`, `power2.in`)
- Maintains consistent duration ranges (0.2-0.3s)
- Uses CSS variables for theme consistency (`--accent`, `--dark`)

### 3. Performance Considerations
- Event delegation minimizes event listeners
- GSAP hardware acceleration for transform/opacity
- isAnimating flag prevents conflicting animations
- Efficient DOM reads/writes

## Configuration Extension Points

### 1. Dashboard Integration Ready
The `getOption()` function is designed to be extended:
```javascript
function getOption(optionName, defaultValue) {
  // Check for dashboard-stored preferences
  const storedValue = localStorage.getItem(`tryl_${optionName}`);
  if (storedValue !== null) {
    return storedValue;
  }
  
  // Check for data attributes on root element
  const attrValue = document.documentElement.getAttribute(`data-${optionName}`);
  if (attrValue !== null) {
    return attrValue;
  }
  
  return defaultValue;
}
```

### 2. Available Configuration Options
- `tryl_size_feedback_type`: 'glow' | 'scaleWithNumber' | 'miniPulse' | 'none'
- Future extensions could include:
  - `tryl_size_animation_duration`
  - `tryl_size_animation_easing`
  - `tryl_size_selector_style`: 'slide-out' | 'toggle' | 'dropdown'

## Browser Compatibility
- GSAP supports all modern browsers
- Event delegation works in IE9+
- CSS transforms supported in all target browsers
- Fetch API with polyfill fallback if needed (though modern browsers support it)

## Testing Considerations
1. Test with various product types (simple, variable)
2. Test with out-of-stock variations
3. Test rapid clicking scenarios
4. Test network error handling
5. Test keyboard navigation
6. Test screen reader compatibility
7. Test mobile touch interactions
8. Test browser zoom levels