/* ── CART STATE MANAGEMENT ── */

const CART_KEY = 'chicken_co_cart';

// Fetch cart from local storage
function getCart() {
  const cart = localStorage.getItem(CART_KEY);
  return cart ? JSON.parse(cart) : [];
}

// Save cart to local storage
function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartBadge();
}

// Add item to cart
function addToCart(item) {
  const cart = getCart();
  const existingItem = cart.find(cartItem => cartItem.id === item.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({
      ...item,
      quantity: 1
    });
  }
  
  saveCart(cart);
  
  // Show a quick visual feedback (optional)
  showToast(`Added ${item.name} to cart!`);
}

// Update quantity of an item
function updateQuantity(id, newQuantity) {
  let cart = getCart();
  
  if (newQuantity <= 0) {
    cart = cart.filter(item => item.id !== id);
  } else {
    const item = cart.find(item => item.id === id);
    if (item) {
      item.quantity = newQuantity;
    }
  }
  
  saveCart(cart);
  // Re-render cart if on cart page
  if (typeof renderCartPage === 'function') {
    renderCartPage();
  }
}

// Remove item entirely
function removeFromCart(id) {
  let cart = getCart();
  cart = cart.filter(item => item.id !== id);
  saveCart(cart);
  if (typeof renderCartPage === 'function') {
    renderCartPage();
  }
}

// Clear entire cart
function clearCart() {
  localStorage.removeItem(CART_KEY);
  updateCartBadge();
}

// Get cart totals (subtotal, tax, total)
function getCartTotals() {
  const cart = getCart();
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const tax = subtotal * 0.05; // 5% GST mock
  const delivery = subtotal > 0 ? 40 : 0; // ₹40 delivery fee
  
  return {
    subtotal: subtotal,
    tax: tax,
    delivery: delivery,
    total: subtotal + tax + delivery
  };
}

// Update badge count in navigation
function updateCartBadge() {
  const cart = getCart();
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  
  const badge = document.getElementById('nav-cart-count');
  if (badge) {
    badge.textContent = totalItems;
    if (totalItems > 0) {
      badge.classList.add('has-items');
    } else {
      badge.classList.remove('has-items');
    }
  }
}

// Simple Toast Notification
function showToast(message) {
  let toast = document.getElementById('cart-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'cart-toast';
    document.body.appendChild(toast);
  }
  
  toast.textContent = message;
  toast.classList.add('show');
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  updateCartBadge();
  
  // Attach event listeners to Add to Cart buttons on the menu page
  document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      // Prevent default in case it's an anchor
      e.preventDefault();
      
      const card = btn.closest('.mi-content');
      let finalId = btn.getAttribute('data-id');
      let finalName = btn.getAttribute('data-name');
      let finalPrice = parseFloat(btn.getAttribute('data-price'));
      
      if (card) {
        const checkedPortion = card.querySelector('.portion-lbl input:checked');
        if (checkedPortion) {
          const portionType = checkedPortion.value;
          const portionPrice = parseFloat(checkedPortion.getAttribute('data-price'));
          
          finalId = finalId + '-' + portionType;
          
          // Use specific names if provided, else generic Half/Full
          let portionLabel = checkedPortion.nextElementSibling.textContent.trim();
          finalName = finalName + ' (' + portionLabel + ')';
          finalPrice = portionPrice;
        }
      }
      
      const itemData = {
        id: finalId,
        name: finalName,
        price: finalPrice,
        image: btn.getAttribute('data-image')
      };
      
      addToCart(itemData);
      
      // Animate button briefly
      const originalText = btn.innerHTML;
      btn.innerHTML = 'Added!';
      btn.style.background = 'var(--gold)';
      btn.style.color = 'var(--dark)';
      
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
        btn.style.color = '';
      }, 1500);
    });
  });

  // Dynamic price update when portion is changed
  document.querySelectorAll('.portion-lbl input').forEach(radio => {
    radio.addEventListener('change', (e) => {
      const card = e.target.closest('.mi-content');
      if (card) {
        const priceDisplay = card.querySelector('.mi-price');
        const newPrice = e.target.getAttribute('data-price');
        if (priceDisplay && newPrice) {
          priceDisplay.textContent = '₹' + newPrice;
        }
      }
    });
  });
});
