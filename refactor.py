import re, os

filepath = r'c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website\index.html'
assets_dir = r'c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website\assets'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Extract CSS
style_match = re.search(r'<style>(.*?)</style>', content, re.DOTALL)
if style_match:
    css = style_match.group(1).strip()
    # Remove #why CSS
    css = re.sub(r'/\*\s*════════════════════════════════════════════\s*WHY SECTION\s*════════════════════════════════════════════\s*\*/.*?/\*\s*════════════════════════════════════════════\s*MENU SECTION', r'/* ════════════════════════════════════════════\n   MENU SECTION', css, flags=re.DOTALL)
    css = re.sub(r'#why,#menu\{padding:5rem 1\.4rem;\}', r'#menu{padding:5rem 1.4rem;}', css)
    
    with open(os.path.join(assets_dir, 'style.css'), 'w', encoding='utf-8') as f:
        f.write(css)

# Extract JS
script_match = re.search(r'<script>\s*(/\* ── CURSOR ── \*/.*?)</script>', content, re.DOTALL)
if script_match:
    js = script_match.group(1).strip()
    with open(os.path.join(assets_dir, 'script.js'), 'w', encoding='utf-8') as f:
        f.write(js)

# Update HTML
html = re.sub(r'<style>.*?</style>', r'<link rel="stylesheet" href="assets/style.css">', content, flags=re.DOTALL)
html = re.sub(r'<script>\s*/\* ── CURSOR ── \*/.*?</script>', r'<script src="assets/script.js" defer></script>', html, flags=re.DOTALL)

# Remove #why HTML section
html = re.sub(r'<!-- ── WHY SECTION ── -->.*?<!-- ── MENU ── -->', r'<!-- ── MENU ── -->', html, flags=re.DOTALL)

# Remove 'Our Story' link from Nav
html = re.sub(r'<li><a href="#why">Our Story</a></li>\n\s*', r'', html)

# Simple footer design
footer_html = '''<footer>
  <div class="footer-content" style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; width: 100%;">
    <div class="logo" style="font-size: 1.5rem; color: var(--gold);">The<span style="color:var(--fire)">.</span>Chicken<span style="color:var(--fire)">.</span>Co</div>
    <div style="display: flex; gap: 2rem;">
      <a href="#story" style="color: var(--cream); text-decoration: none; font-size: 0.9rem; font-family: 'DM Sans', sans-serif;">Process</a>
      <a href="#menu" style="color: var(--cream); text-decoration: none; font-size: 0.9rem; font-family: 'DM Sans', sans-serif;">Menu</a>
      <a href="#order" style="color: var(--cream); text-decoration: none; font-size: 0.9rem; font-family: 'DM Sans', sans-serif;">Locations</a>
    </div>
  </div>
  <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,184,0,0.1); color: rgba(255,248,238,0.7); font-size: 0.85rem;">
    <span>© 2025 The Chicken Co. All rights reserved. | Made with 🔥 and 14 spices</span>
  </div>
</footer>'''
html = re.sub(r'<footer>.*?</footer>', footer_html, html, flags=re.DOTALL)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(html)

print('Success')
