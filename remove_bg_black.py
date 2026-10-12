from PIL import Image

def remove_bg(input_path, output_path, bg_color=(0, 0, 0), tolerance=15):
    img = Image.open(input_path).convert("RGBA")
    data = img.getdata()
    
    new_data = []
    for item in data:
        if abs(item[0] - bg_color[0]) <= tolerance and \
           abs(item[1] - bg_color[1]) <= tolerance and \
           abs(item[2] - bg_color[2]) <= tolerance:
            new_data.append((255, 255, 255, 0))
        else:
            new_data.append(item)
            
    img.putdata(new_data)
    img.save(output_path, "PNG")

remove_bg("~/workspace/.gemini/antigravity/brain/c5a5eed3-43ca-44c1-a9ed-18e164df64cf/hero_bat_ball_raw_1779030212390.png", "assets/img/bat_ball_transparent.png")
