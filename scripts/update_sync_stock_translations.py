import os

locales = {
    'ar': 'مزامنة المخزون',
    'bn': 'স্টক সিঙ্ক করুন',
    'ca': 'Sincronitza l\'estoc',
    'de': 'Bestand synchronisieren',
    'en': 'Sync Stock',
    'es': 'Sincronizar stock',
    'fa': 'همگام‌سازی موجودی',
    'fr': 'Synchroniser le stock',
    'he': 'סנכרן מלאי',
    'hi_IN': 'स्टॉक सिंक करें',
    'id': 'Sinkronkan Stok',
    'it': 'Sincronizza stock',
    'ja': '在庫を同期',
    'nl': 'Voorraad synchroniseren',
    'pl': 'Synchronizuj stany magazynowe',
    'pt_BR': 'Sincronizar Estoque',
    'ru': 'Синхронизировать остатки',
    'sin': 'තොග සමමුහුර්ත කරන්න',
    'tr': 'Stoku Senkronize Et',
    'uk': 'Синхронізувати залишки',
    'zh_CN': '同步库存',
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, label in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'product-name' =>"
        if "'sync-stock' =>" not in content and target in content:
            lines = content.splitlines(True)
            new_lines = []
            for line in lines:
                new_lines.append(line)
                if target in line:
                    indent = line[:len(line) - len(line.lstrip())]
                    escaped_v = label.replace("'", "\\'")
                    new_lines.append(f"{indent}'sync-stock' => '{escaped_v}',\n")
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)
            print(f"Updated {loc}/app.php with sync-stock")

print("Done updating translations for sync-stock!")
