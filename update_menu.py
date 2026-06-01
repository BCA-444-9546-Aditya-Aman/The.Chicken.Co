import re

filepath = r"c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website\menu.html"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Add styles for the filters
filter_css = """
  .menu-filters { display: flex; gap: 0.6rem; justify-content: center; margin-bottom: 3rem; flex-wrap: wrap; }
  .filter-btn { background: transparent; border: 1px solid rgba(255,248,238,0.15); color: rgba(255,248,238,0.5); padding: 0.5rem 1.4rem; border-radius: 50px; font-family: 'DM Sans', sans-serif; font-size: 0.8rem; letter-spacing: 0.05em; cursor: none; transition: all 0.25s; }
  .filter-btn:hover { border-color: rgba(255,184,0,0.4); color: var(--cream); }
  .filter-btn.active { background: var(--gold); color: var(--dark); border-color: var(--gold); }
  .mi.hidden { display: none; }
  .mi.fade-in { animation: fadeIn 0.4s ease forwards; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
"""
content = content.replace("</style>", filter_css + "\n</style>")

# Add the filter buttons HTML
filter_html = """  <div class="menu-filters">
    <button class="filter-btn active" data-filter="all">All</button>
    <button class="filter-btn" data-filter="wings">Wings</button>
    <button class="filter-btn" data-filter="burgers">Burgers</button>
    <button class="filter-btn" data-filter="grilled">Grilled</button>
    <button class="filter-btn" data-filter="combos">Combos</button>
  </div>"""
content = content.replace('<div class="menu-grid">', filter_html + '\n  <div class="menu-grid">')

# Add data-category attributes
categories = {
    "Classic Crispy Wings": "wings",
    "Tower Burger": "burgers",
    "Peri Peri Flame": "grilled",
    "Smoky Kababs": "grilled",
    "Spicy Tenders": "wings",
    "The Family Bucket": "combos"
}

# Find all mi items and replace
for item_name, category in categories.items():
    # we can find the .mi that contains the name and inject data-category into it
    pattern = re.compile(rf'<div class="mi (reveal reveal-d\d+)">\s*<div class="mi-img-wrapper">.*?<h3 class="mi-name">{item_name}</h3>', re.DOTALL)
    
    def replacer(match):
        return match.group(0).replace('<div class="mi ', f'<div class="mi " data-category="{category}" ')
        
    content = pattern.sub(replacer, content)

# Also there's an issue with the regex approach, simpler is to do it with beautifulsoup or just split by <div class="mi
parts = content.split('<div class="mi ')
new_parts = [parts[0]]
for part in parts[1:]:
    for name, cat in categories.items():
        if f'<h3 class="mi-name">{name}</h3>' in part:
            part = f'data-category="{cat}" ' + part
            break
    new_parts.append(part)

content = '<div class="mi '.join(new_parts)

# Add filter JS
filter_js = """
  // Filter logic
  const filterBtns = document.querySelectorAll('.filter-btn');
  const menuItems = document.querySelectorAll('.mi');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.getAttribute('data-filter');
      menuItems.forEach(item => {
        item.classList.remove('fade-in', 'hidden');
        if(filter === 'all' || item.getAttribute('data-category') === filter) {
          // Trigger reflow for animation
          void item.offsetWidth;
          item.classList.add('fade-in');
        } else {
          item.classList.add('hidden');
        }
      });
    });
  });
"""
content = content.replace("</script>\n<script src=\"assets/auth.js\">", filter_js + "\n</script>\n<script src=\"assets/auth.js\">")

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("Updated menu.html with filters")
