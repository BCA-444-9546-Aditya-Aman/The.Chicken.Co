import os
import re

directory = r"c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website"

# The HTML to replace
old_footer_bottom = """  <div class="footer-bottom">
    <div class="footer-copy">© 2026 The Chicken Co. All rights reserved. <br> Made By Aditya ❤️</div>
    <div class="footer-legal">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>
  </div>"""

new_footer_bottom = """  <div class="footer-bottom">
    <div class="footer-copy">© 2026 The Chicken Co. All rights reserved. Made By Aditya ❤️</div>
  </div>"""

for file in os.listdir(directory):
    if file.endswith(".html"):
        filepath = os.path.join(directory, file)
        if file == "login.html":
            continue
            
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()

        content = content.replace(old_footer_bottom, new_footer_bottom)

        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Updated {file}")
