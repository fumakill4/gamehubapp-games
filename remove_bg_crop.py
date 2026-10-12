from PIL import Image

def remove_bg(input_path, output_path, tolerance=40):
    img = Image.open(input_path).convert("RGBA")
    data = img.getdata()
    
    new_data = []
    for item in data:
        # Remove dark colors (black background and studio floor)
        if item[0] <= tolerance and item[1] <= tolerance and item[2] <= tolerance:
            new_data.append((255, 255, 255, 0))
        else:
            new_data.append(item)
            
    img.putdata(new_data)
    
    # Crop the image to the bounding box of the non-transparent pixels
    bbox = img.getbbox()
    if bbox:
        img = img.crop(bbox)
        
    img.save(output_path, "PNG")

remove_bg("~/workspace/.gemini/antigravity/brain/c5a5eed3-43ca-44c1-a9ed-18e164df64cf/hero_bat_full_1779033137027.png", "assets/img/bat_ball_transparent.png")
