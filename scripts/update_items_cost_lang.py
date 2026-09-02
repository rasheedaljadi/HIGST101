import os

locales = {
    'ar': {'items-cost': 'سعر المنتجات'},
    'bn': {'items-cost': 'পণ্যের মূল্য'},
    'ca': {'items-cost': 'Preu dels productes'},
    'de': {'items-cost': 'Produktpreis'},
    'en': {'items-cost': 'Items Price'},
    'es': {'items-cost': 'Precio de productos'},
    'fa': {'items-cost': 'قیمت کالاها'},
    'fr': {'items-cost': 'Prix des articles'},
    'he': {'items-cost': 'מחיר פריטים'},
    'hi_IN': {'items-cost': 'उत्पाद मूल्य'},
    'id': {'items-cost': 'Harga Produk'},
    'it': {'items-cost': 'Prezzo articoli'},
    'ja': {'items-cost': '商品価格'},
    'nl': {'items-cost': 'Artikelprijs'},
    'pl': {'items-cost': 'Cena produktów'},
    'pt_BR': {'items-cost': 'Preço dos itens'},
    'ru': {'items-cost': 'Стоимость товаров'},
    'sin': {'items-cost': 'භාණ්ඩ මිල'},
    'tr': {'items-cost': 'Ürün Fiyatı'},
    'uk': {'items-cost': 'Вартість товарів'},
    'zh_CN': {'items-cost': '商品总价'},
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, items in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'shipping-fee' =>"
        if "'items-cost' =>" not in content and target in content:
            lines = content.splitlines(True)
            new_lines = []
            for line in lines:
                new_lines.append(line)
                if target in line:
                    indent = line[:len(line) - len(line.lstrip())]
                    for k, v in items.items():
                        escaped_v = v.replace("'", "\\'")
                        new_lines.append(f"{indent}'{k}' => '{escaped_v}',\n")
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)
            print(f"Updated {loc}/app.php with items-cost")
        else:
            print(f"Skipped {loc}")

print("Done updating translations for items-cost!")
