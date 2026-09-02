import os

locales = {
    'ar': {
        'supplier-stock': 'مخزون علي إكسبرس',
        'out-of-stock': 'غير متوفر (0)',
        'units': 'قطعة',
    },
    'bn': {
        'supplier-stock': 'AliExpress স্টক',
        'out-of-stock': 'স্টক শেষ (0)',
        'units': 'পিস',
    },
    'ca': {
        'supplier-stock': 'Estoc AliExpress',
        'out-of-stock': 'Esgotat (0)',
        'units': 'unitats',
    },
    'de': {
        'supplier-stock': 'AliExpress-Bestand',
        'out-of-stock': 'Nicht vorrätig (0)',
        'units': 'Stück',
    },
    'en': {
        'supplier-stock': 'AliExpress Stock',
        'out-of-stock': 'Out of Stock (0)',
        'units': 'units',
    },
    'es': {
        'supplier-stock': 'Stock AliExpress',
        'out-of-stock': 'Agotado (0)',
        'units': 'unidades',
    },
    'fa': {
        'supplier-stock': 'موجودی علی‌اکسپرس',
        'out-of-stock': 'ناموجود (0)',
        'units': 'عدد',
    },
    'fr': {
        'supplier-stock': 'Stock AliExpress',
        'out-of-stock': 'Rupture de stock (0)',
        'units': 'unités',
    },
    'he': {
        'supplier-stock': 'מלאי אליאקספרס',
        'out-of-stock': 'אזל מהמלאי (0)',
        'units': 'יחידות',
    },
    'hi_IN': {
        'supplier-stock': 'AliExpress स्टॉक',
        'out-of-stock': 'स्टॉक में नहीं (0)',
        'units': 'इकाइयां',
    },
    'id': {
        'supplier-stock': 'Stok AliExpress',
        'out-of-stock': 'Stok Habis (0)',
        'units': 'unit',
    },
    'it': {
        'supplier-stock': 'Stock AliExpress',
        'out-of-stock': 'Esaurito (0)',
        'units': 'unità',
    },
    'ja': {
        'supplier-stock': 'AliExpress在庫',
        'out-of-stock': '在庫切れ (0)',
        'units': '個',
    },
    'nl': {
        'supplier-stock': 'AliExpress-voorraad',
        'out-of-stock': 'Niet op voorraad (0)',
        'units': 'stuks',
    },
    'pl': {
        'supplier-stock': 'Stan magazynowy AliExpress',
        'out-of-stock': 'Brak w magazynie (0)',
        'units': 'szt.',
    },
    'pt_BR': {
        'supplier-stock': 'Estoque AliExpress',
        'out-of-stock': 'Sem Estoque (0)',
        'units': 'unidades',
    },
    'ru': {
        'supplier-stock': 'Остаток AliExpress',
        'out-of-stock': 'Нет в наличии (0)',
        'units': 'шт.',
    },
    'sin': {
        'supplier-stock': 'AliExpress තොගය',
        'out-of-stock': 'තොග අවසන් (0)',
        'units': 'ඒකක',
    },
    'tr': {
        'supplier-stock': 'AliExpress Stoğu',
        'out-of-stock': 'Tükendi (0)',
        'units': 'adet',
    },
    'uk': {
        'supplier-stock': 'Залишок AliExpress',
        'out-of-stock': 'Немає в наявності (0)',
        'units': 'шт.',
    },
    'zh_CN': {
        'supplier-stock': '速卖通库存',
        'out-of-stock': '缺货 (0)',
        'units': '件',
    },
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, items in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'items-cost' =>"
        if "'supplier-stock' =>" not in content and target in content:
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
            print(f"Updated {loc}/app.php with stock translations")
        else:
            print(f"Skipped {loc}")

print("Done updating translations for supplier-stock!")
