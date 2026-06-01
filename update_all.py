import os
import re

directory = r"c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website"

nav_dropdown_content = """      <div class="nav-dropdown">
        <a href="profile.html" class="auth-required">My Profile</a>
        <a href="orders.html" class="auth-required">My Orders</a>
        <a href="offers.html" class="auth-required">Offers</a>
        <a href="login.html" class="auth-hidden">Login</a>
        <a href="#" class="auth-required logout-btn">Logout</a>
      </div>"""

footer_content = """<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">The<span>.</span>Chicken<span>.</span>Co</div>
      <p>Farm-raised, never frozen. Marinated in 14 secret spices for 24 hours. Double fried to legendary crunch.</p>
      <div class="footer-hours">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        Open Daily 11:00 AM – 11:00 PM
      </div>
    </div>
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="menu.html">Menu</a></li>
        <li><a href="cart.html">Cart</a></li>
        <li><a href="profile.html">My Account</a></li>
        <li><a href="index.html#story">Our Story</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact Us</h4>
      <ul>
        <li><a href="mailto:hello@thechickenco.com">hello@thechickenco.com</a></li>
        <li><a href="tel:+919876543210">+91 98765 43210</a></li>
      </ul>
      <div class="footer-socials">
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
        <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg></a>
        <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© 2026 The Chicken Co. All rights reserved. <br> Made By Aditya ❤️</div>
    <div class="footer-legal">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>
  </div>
</footer>"""

for file in os.listdir(directory):
    if file.endswith(".html"):
        filepath = os.path.join(directory, file)
        if file == "login.html": # Login doesn't have footer or dropdown but just in case
            continue
            
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()

        # Update dropdown
        dropdown_pattern = re.compile(r'<div class="nav-dropdown">.*?</div>', re.DOTALL)
        content = dropdown_pattern.sub(nav_dropdown_content, content)

        # Update footer
        footer_pattern = re.compile(r'<footer>.*?</footer>', re.DOTALL)
        content = footer_pattern.sub(footer_content, content)

        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Updated {file}")
