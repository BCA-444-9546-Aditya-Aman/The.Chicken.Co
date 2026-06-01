import re

filepath = r"c:\Users\Aditya Aman\OneDrive\Documents\Coding\webdev\chicken-website\menu.html"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Fix the invalid HTML caused by the previous script
# Replace '<div class="mi data-category="burgers" reveal reveal-d2" data-category="burgers">' 
# with '<div class="mi reveal reveal-d2" data-category="burgers">'

# Using regex to clean up any messy div classes
content = re.sub(r'<div class="mi\s+data-category="([^"]+)"\s*(reveal[^"]*)".*?>', r'<div class="mi \2" data-category="\1">', content)

# Check if the first one is correct: 'Classic Crispy Wings' was the first part.
# The python script split by '<div class="mi ' and prepended data-category.
# Let's just do a clean pass.
# Find all `<div class="mi ` that got corrupted.
# Actually, the string became `<div class="mi data-category="wings" reveal reveal-d1"`
# Let's replace `data-category="wings" ` from inside the class.
content = content.replace('class="mi data-category="wings" ', 'class="mi " data-category="wings" ')
content = content.replace('class="mi data-category="burgers" ', 'class="mi " data-category="burgers" ')
content = content.replace('class="mi data-category="grilled" ', 'class="mi " data-category="grilled" ')
content = content.replace('class="mi data-category="combos" ', 'class="mi " data-category="combos" ')

# Remove any duplicated data-category outside
content = re.sub(r'(data-category="[^"]+")\s+data-category="[^"]+"', r'\1', content)

# Ensure no `class="mi " data-category="wings" reveal reveal-d1"` which is still invalid
# Wait, it was `<div class="mi data-category="burgers" reveal reveal-d2" data-category="burgers">`
# That means it was literal: `class="mi data-category="burgers" reveal reveal-d2"`
# Let's just replace the exact substrings since there are only 6 items.

fixes = [
    ('class="mi data-category="wings" reveal reveal-d1" data-category="wings"', 'class="mi reveal reveal-d1" data-category="wings"'),
    ('class="mi data-category="burgers" reveal reveal-d2" data-category="burgers"', 'class="mi reveal reveal-d2" data-category="burgers"'),
    ('class="mi data-category="grilled" reveal reveal-d3" data-category="grilled"', 'class="mi reveal reveal-d3" data-category="grilled"'),
    ('class="mi data-category="grilled" reveal reveal-d4" data-category="grilled"', 'class="mi reveal reveal-d4" data-category="grilled"'),
    ('class="mi data-category="wings" reveal reveal-d5" data-category="wings"', 'class="mi reveal reveal-d5" data-category="wings"'),
    ('class="mi data-category="combos" reveal reveal-d6" data-category="combos"', 'class="mi reveal reveal-d6" data-category="combos"')
]

for bad, good in fixes:
    content = content.replace(bad, good)

# Also fix the first item if it was broken differently. 
# Classic Crispy Wings was `class="mi reveal reveal-d1"`, maybe it didn't get corrupted if it was the first split part?
# Wait, the first split part before `<div class="mi ` doesn't have an item.
# So `parts[1]` got prepended with `data-category="wings" `.
# So `<div class="mi data-category="wings" reveal reveal-d1"` was formed!

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
