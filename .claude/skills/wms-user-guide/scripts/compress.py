"""Nén ảnh chụp hướng dẫn về PNG 256 màu rồi chép vào public/img/userGuide/<khoá>/.

    python compress.py <thư mục ảnh vừa chụp> <khoá tài liệu>
"""
import os
import sys

from PIL import Image

src, guide = sys.argv[1], sys.argv[2]
root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..', '..'))
dst = os.path.join(root, 'public', 'img', 'userGuide', guide)
os.makedirs(dst, exist_ok=True)

for name in sorted(os.listdir(src)):
    if not name.endswith('.png'):
        continue
    image = Image.open(os.path.join(src, name)).convert('RGB')
    image.quantize(colors=256, method=Image.Quantize.MEDIANCUT).save(os.path.join(dst, name), optimize=True)
    print(name, image.size, os.path.getsize(os.path.join(dst, name)) // 1024, 'KB')
