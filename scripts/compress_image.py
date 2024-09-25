import sys
from PIL import Image

def resize_and_compress_image(source_path, target_path, max_width=800, max_height=600, quality=75):
    with Image.open(source_path) as img:
        img.thumbnail((max_width, max_height), Image.ANTIALIAS)
        img.save(target_path, format='JPEG', quality=quality)

if __name__ == "__main__":
    # Receba os caminhos da imagem de entrada e saída como argumentos
    source_image_path = sys.argv[1]
    target_image_path = sys.argv[2]
    
    resize_and_compress_image(source_image_path, target_image_path)
    print(f"Imagem comprimida e salva em: {target_image_path}")
