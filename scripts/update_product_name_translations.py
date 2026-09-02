import os

locales = {
    'ar': 'اسم المنتج والمتغيرات',
    'bn': 'পণ্যের নাম ও ভ্যারিয়েন্ট',
    'ca': 'Nom del producte i variants',
    'de': 'Produktname & Varianten',
    'en': 'Product Name & Variants',
    'es': 'Nombre del producto y variantes',
    'fa': 'نام محصول و تنوع‌ها',
    'fr': 'Nom du produit & Variantes',
    'he': 'שם מוצר ווריאציות',
    'hi_IN': 'उत्पाद का नाम और वेरिएंट',
    'id': 'Nama Produk & Varian',
    'it': 'Nome prodotto e varianti',
    'ja': '商品名とバリエーション',
    'nl': 'Productnaam & Varianten',
    'pl': 'Nazwa produktu i warianty',
    'pt_BR': 'Nome do Produto e Variantes',
    'ru': 'Название товара и варианты',
    'sin': 'නිෂ්පාදන නම සහ ප්‍රභේද',
    'tr': 'Ürün Adı ve Varyantlar',
    'uk': 'Назва товару та варіанти',
    'zh_CN': '商品名称与规格',
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, label in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'supplier-stock' =>"
        if "'product-name' =>" not in content and target in content:
            lines = content.splitlines(True)
            new_lines = []
            for line in lines:
                new_lines.append(line)
                if target in line:
                    indent = line[:len(line) - len(line.lstrip())]
                    escaped_v = label.replace("'", "\\'")
                    new_lines.append(f"{indent}'product-name' => '{escaped_v}',\n")
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)
            print(f"Updated {loc}/app.php with product-name")

print("Done updating translations for product-name!")
