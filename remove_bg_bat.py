from PIL import Image

def remove_bg(input_path, output_path, tolerance=45):
    img = Image.open(input_path).convert("RGBA")
    pixels = img.load()
    width, height = img.size
    
    # BFS flood fill from all 4 corners
    queue = [(0,0),(width-1,0),(0,height-1),(width-1,height-1)]
    visited = set(queue)
    
    def is_bg(r, g, b, a):
        # Pure black or very dark
        return r < 50 and g < 50 and b < 50
    
    while queue:
        x, y = queue.pop(0)
        r, g, b, a = pixels[x, y]
        if is_bg(r, g, b, a):
            pixels[x, y] = (0, 0, 0, 0)
            for dx, dy in [(0,1),(1,0),(0,-1),(-1,0)]:
                nx, ny = x+dx, y+dy
                if 0 <= nx < width and 0 <= ny < height and (nx,ny) not in visited:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
    
    # Crop to content bounding box
    bbox = img.getbbox()
    if bbox:
        img = img.crop(bbox)
    
    img.save(output_path, "PNG")
    print(f"Done: {img.size}")

remove_bg(
    '~/workspace/.gemini/antigravity/brain/c5a5eed3-43ca-44c1-a9ed-18e164df64cf/bat_ball_upright_1779038416312.png',
    'assets/img/bat_ball_transparent.png'
)
