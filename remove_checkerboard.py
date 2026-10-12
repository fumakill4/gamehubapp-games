from PIL import Image

def remove_background(img_path, out_path, tolerance=25):
    img = Image.open(img_path).convert("RGBA")
    pixels = img.load()
    width, height = img.size
    
    # BFS to flood fill from all 4 corners
    queue = [(0, 0), (width-1, 0), (0, height-1), (width-1, height-1)]
    visited = set(queue)
    
    def is_bg(c):
        # Background is white/light gray checkerboard
        # R, G, B should be close to each other (grayscale) and very light
        r, g, b, a = c
        if r > 230 and g > 230 and b > 230:
            return True
        # Also include lighter grays
        if r > 210 and g > 210 and b > 210 and abs(r-g) < 15 and abs(r-b) < 15:
            return True
        return False

    while queue:
        x, y = queue.pop(0)
        
        c = pixels[x, y]
        if is_bg(c):
            pixels[x, y] = (255, 255, 255, 0)
            
            # Add neighbors
            for dx, dy in [(0, 1), (1, 0), (0, -1), (-1, 0)]:
                nx, ny = x + dx, y + dy
                if 0 <= nx < width and 0 <= ny < height and (nx, ny) not in visited:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
                    
    # Also do a second pass to remove any remaining bg pixels that might be isolated
    for y in range(height):
        for x in range(width):
            if is_bg(pixels[x, y]):
                pixels[x, y] = (255, 255, 255, 0)

    # Crop to content
    bbox = img.getbbox()
    if bbox:
        img = img.crop(bbox)
        
    img.save(out_path, "PNG")

remove_background('~/workspace/.gemini/antigravity/brain/c5a5eed3-43ca-44c1-a9ed-18e164df64cf/media__1779037029149.png', 'assets/img/favicon.png')
