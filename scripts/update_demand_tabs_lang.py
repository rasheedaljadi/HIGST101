import os

locales = {
    'ar': {
        'tab-all': 'الكل',
        'tab-open-for-batching': 'متاح للتجميع',
        'tab-batched': 'تم التجميع',
    },
    'bn': {
        'tab-all': 'সব',
        'tab-open-for-batching': 'ব্যাচিংয়ের জন্য উপলব্ধ',
        'tab-batched': 'ব্যাচ করা হয়েছে',
    },
    'ca': {
        'tab-all': 'Tot',
        'tab-open-for-batching': 'Disponible per agrupar',
        'tab-batched': 'Agrupat',
    },
    'de': {
        'tab-all': 'Alle',
        'tab-open-for-batching': 'Offen für Batching',
        'tab-batched': 'Gebatcht',
    },
    'en': {
        'tab-all': 'All',
        'tab-open-for-batching': 'Open for Batching',
        'tab-batched': 'Batched',
    },
    'es': {
        'tab-all': 'Todos',
        'tab-open-for-batching': 'Disponible para agrupar',
        'tab-batched': 'Agrupado',
    },
    'fa': {
        'tab-all': 'همه',
        'tab-open-for-batching': 'آماده برای دسته‌بندی',
        'tab-batched': 'دسته‌بندی شده',
    },
    'fr': {
        'tab-all': 'Tous',
        'tab-open-for-batching': 'Disponible pour regroupement',
        'tab-batched': 'Regroupé',
    },
    'he': {
        'tab-all': 'הכל',
        'tab-open-for-batching': 'פתוח לאיחוד',
        'tab-batched': 'מאוחד',
    },
    'hi_IN': {
        'tab-all': 'सभी',
        'tab-open-for-batching': 'बैचिंग के लिए उपलब्ध',
        'tab-batched': 'बैच किया गया',
    },
    'id': {
        'tab-all': 'Semua',
        'tab-open-for-batching': 'Tersedia untuk Pengelompokan',
        'tab-batched': 'Dikelompokkan',
    },
    'it': {
        'tab-all': 'Tutti',
        'tab-open-for-batching': 'Aperto per raggruppamento',
        'tab-batched': 'Raggruppato',
    },
    'ja': {
        'tab-all': 'すべて',
        'tab-open-for-batching': 'バッチ処理可能',
        'tab-batched': 'バッチ処理済み',
    },
    'nl': {
        'tab-all': 'Alles',
        'tab-open-for-batching': 'Beschikbaar voor batching',
        'tab-batched': 'Gebatcht',
    },
    'pl': {
        'tab-all': 'Wszystkie',
        'tab-open-for-batching': 'Dostępne do grupowania',
        'tab-batched': 'Zgrupowane',
    },
    'pt_BR': {
        'tab-all': 'Todos',
        'tab-open-for-batching': 'Disponível para lote',
        'tab-batched': 'Em lote',
    },
    'ru': {
        'tab-all': 'Все',
        'tab-open-for-batching': 'Доступно для объединения',
        'tab-batched': 'Объединено в партию',
    },
    'sin': {
        'tab-all': 'සියල්ල',
        'tab-open-for-batching': 'කාණ්ඩගත කිරීම සඳහා ඇත',
        'tab-batched': 'කාණ්ඩගත කර ඇත',
    },
    'tr': {
        'tab-all': 'Tümü',
        'tab-open-for-batching': 'Gruplama için Açık',
        'tab-batched': 'Gruplandı',
    },
    'uk': {
        'tab-all': 'Усі',
        'tab-open-for-batching': 'Доступно для групування',
        'tab-batched': 'Згруповано',
    },
    'zh_CN': {
        'tab-all': '全部',
        'tab-open-for-batching': '可进行批处理',
        'tab-batched': '已批处理',
    },
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, items in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'open-for-batching' =>"
        if "'tab-open-for-batching' =>" not in content and target in content:
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
            print(f"Updated {loc}/app.php with demand tabs")
        else:
            print(f"Skipped {loc}")

print("Done updating translations for demand tabs!")
