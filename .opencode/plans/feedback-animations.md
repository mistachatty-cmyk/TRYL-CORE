# GSAP-Based Notification Animations for TRYL Cart Feedback

## Overview
This document specifies the GSAP-based notification animations that provide visual feedback when users add items to their cart from the shop page. Three distinct animation styles are implemented to cater to different user preferences and design aesthetics.

## Animation Styles

### 1. Glow Effect
**Description:** The product card pulses with a glowing accent color to confirm successful addition to cart.

**Technical Implementation:**
```javascript
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
```

**Visual Characteristics:**
- Soft outer glow using the theme's accent color
- Slight scale increase (2%) for depth
- Smooth fade-in/fade-out using power2.out easing
- Duration: 300ms total (150ms out, 150ms back)
- Non-intrusive but noticeable confirmation

### 2. Scale with Number Effect
**Description:** The cart icon scales up while the item count animates upward, providing quantitative feedback.

**Technical Implementation:**
```javascript
function scaleWithNumberFeedback(button, cartData) {
  const cartIcon = document.querySelector('.tryl-cart-count-badge');
  if (!cartIcon) return;
  
  // Scale up cart icon
  gsap.fromTo(cartIcon, 
    { scale: 1 }, 
    { 
      scale: 1.3, 
      duration: 0.3, 
      ease: "back.out(1.7)",  // Overshoot for playful feel
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
          // Interpolate between current and new count
          const progress = this.progress();
          const interpolatedCount = Math.round(currentCount + (newCount - currentCount) * progress);
          countEl.textContent = interpolatedCount;
        }
      }
    );
  }
}
```

**Visual Characteristics:**
- Cart badge scales to 130% with playful overshoot (back.out)
- Smooth return to normal size
- Item count number animates from current to new value
- Vertical lift effect during count animation
- Duration: 300ms for icon, 300ms for number (overlapped)
- Provides both qualitative and quantitative feedback

### 3. Mini Pulse Effect
**Description:** A subtle scale pulse on the add-to-cart button itself for minimal but clear feedback.

**Technical Implementation:**
```javascript
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
```

**Visual Characteristics:**
- Button scales to 105% and back
- Quick, subtle motion (150ms out, 150ms back)
- Uses power2.out/ease-in symmetry for natural feel
- Least disruptive of the three options
- Ideal for power users who prefer minimal distraction

## Animation Principles

### 1. Performance Optimization
- **GPU Acceleration:** All animations use transform and opacity properties
- **Will-Change Hint:** CSS will-change properties could be added for known animated elements
- **Efficient Easing:** Standard GSAP easings (power2.out, power3.out) are optimized
- **Duration Limits:** All animations under 500ms to maintain responsiveness

### 2. Consistency with Existing Patterns
- **Easing Functions:** Uses same power2.out/power3.out patterns from tryl-core.js
- **Duration Ranges:** Matches existing animation durations (0.2s-0.4s)
- **CSS Variables:** Leverages existing --accent, --dark, --off color variables
- **GSAP Usage:** Follows same gsap.fromTo() patterns already in codebase

### 3. Accessibility Considerations
- **Reduced Motion:** Animations respect prefers-reduced-media CSS media query (could be enhanced)
- **Non-Essential:** Animations enhance but don't convey critical information
- **No Flashing:** All animations avoid rapid strobing effects
- **Color Contrast:** Glow effect uses theme colors that maintain proper contrast

## Configuration System

### Dashboard Integration Points
The feedback system is designed to work with a future dashboard configuration:

```javascript
function getFeedbackType() {
  // Priority order for determining feedback type:
  // 1. Local storage (user preference)
  // 2. Data attribute on HTML element (theme/site setting)
  // 3. Default value
  
  const stored = localStorage.getItem('tryl_feedback_type');
  if (stored) return stored;
  
  const dataAttr = document.documentElement.getAttribute('data-feedback-type');
  if (dataAttr) return dataAttr;
  
  return 'glow'; // Default
}
```

### Available Options
- `glow`: Pulsing glow around product card (default)
- `scaleWithNumber`: Cart icon scales with counting animation
- `miniPulse`: Subtle button scale pulse
- `none`: Disable feedback (for performance-sensitive users)

## Implementation Notes

### 1. Integration with Existing Systems
- Works with existing `tryl_ajax_add_to_cart` endpoint
- Leverages existing `refreshCart()` function for cart updates
- Uses existing mini-cart DOM structure (.tryl-cart-count, .tryl-cart-count-badge)
- Compatible with theme switching via CSS variables

### 2. Error Handling
- Graceful degradation if DOM elements not found
- No feedback if GSAP is not loaded (though it's globally enqueued)
- Animations automatically stop if element is removed during animation

### 3. Customization Hooks
Easy to extend with additional feedback types:
```javascript
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
  case 'confetti':
    confettiFeedback(button); // Future extension
    break;
  default:
    glowFeedback(button);
}
```

## Performance Benchmarks
- **Frame Rate:** Target 60fps (16.6ms per frame)
- **Main Thread Impact:** Minimal - GSAP uses requestAnimationFrame
- **Memory Usage:** Low - only animating specific properties
- **Layout Thrashing:** None - only transform/opacity changes

## Testing Guidelines
1. **Visual Verification:** Confirm each animation plays correctly
2. **Performance Testing:** Use Chrome DevTools Performance tab
3. **Accessibility Testing:** Verify with reduced motion preferences
4. **Cross-Browser:** Test in Chrome, Firefox, Safari, Edge
5. **Mobile Testing:** Verify touch devices and various viewports
6. **Regression Testing:** Ensure existing mini-cart functionality unchanged