from PIL import Image
from collections import Counter

img = Image.open('~/workspace/.gemini/antigravity/brain/c5a5eed3-43ca-44c1-a9ed-18e164df64cf/media__1779037029149.png').convert("RGBA")
pixels = img.load()

# Check corners
corner_colors = [
    pixels[0, 0],
    pixels[img.width-1, 0],
    pixels[0, img.height-1],
    pixels[img.width-1, img.height-1]
]

print("Corner colors:", corner_colors)

# Check top left 20x20 block for common colors
top_left_colors = []
for x in range(20):
    for y in range(20):
        top_left_colors.append(pixels[x, y])
        
print("Most common in top-left:", Counter(top_left_colors).most_common(5))
