import os

locales = {
    'ar': {
        'shipping-fee': 'رسوم الشحن',
        'total-cost-with-shipping': 'الإجمالي (شامل الشحن)',
    },
    'bn': {
        'shipping-fee': 'শিপিং ফি',
        'total-cost-with-shipping': 'মোট (শিপিং সহ)',
    },
    'ca': {
        'shipping-fee': 'Despeses d\'enviament',
        'total-cost-with-shipping': 'Total (amb enviament)',
    },
    'de': {
        'shipping-fee': 'Versandkosten',
        'total-cost-with-shipping': 'Gesamt (inkl. Versand)',
    },
    'en': {
        'shipping-fee': 'Shipping Fee',
        'total-cost-with-shipping': 'Total (Incl. Shipping)',
    },
    'es': {
        'shipping-fee': 'Gastos de envío',
        'total-cost-with-shipping': 'Total (con envío)',
    },
    'fa': {
        'shipping-fee': 'هزینه ارسال',
        'total-cost-with-shipping': 'مجموع (با ارسال)',
    },
    'fr': {
        'shipping-fee': 'Frais de port',
        'total-cost-with-shipping': 'Total (port inclus)',
    },
    'he': {
        'shipping-fee': 'דמי משלוח',
        'total-cost-with-shipping': 'סה"כ (כולל משלוח)',
    },
    'hi_IN': {
        'shipping-fee': 'शिपिंग शुल्क',
        'total-cost-with-shipping': 'कुल (शिपिंग सहित)',
    },
    'id': {
        'shipping-fee': 'Biaya Pengiriman',
        'total-cost-with-shipping': 'Total (Termasuk Ongkir)',
    },
    'it': {
        'shipping-fee': 'Spese di spedizione',
        'total-cost-with-shipping': 'Totale (spedizione inclusa)',
    },
    'ja': {
        'shipping-fee': '送料',
        'total-cost-with-shipping': '合計（送料込）',
    },
    'nl': {
        'shipping-fee': 'Verzendkosten',
        'total-cost-with-shipping': 'Totaal (incl. verzending)',
    },
    'pl': {
        'shipping-fee': 'Koszt wysyłki',
        'total-cost-with-shipping': 'Razem (z wysyłką)',
    },
    'pt_BR': {
        'shipping-fee': 'Taxa de frete',
        'total-cost-with-shipping': 'Total (com frete)',
    },
    'ru': {
        'shipping-fee': 'Стоимость доставки',
        'total-cost-with-shipping': 'Итого (с доставкой)',
    },
    'sin': {
        'shipping-fee': 'නැව් ගාස්තු',
        'total-cost-with-shipping': 'එකතුව (නැව් ගාස්තු ඇතුළුව)',
    },
    'tr': {
        'shipping-fee': 'Kargo Ücreti',
        'total-cost-with-shipping': 'Toplam (Kargo Dahil)',
    },
    'uk': {
        'shipping-fee': 'Вартість доставки',
        'total-cost-with-shipping': 'Разом (з доставкою)',
    },
    'zh_CN': {
        'shipping-fee': '运费',
        'total-cost-with-shipping': '总计（含运费）',
    },
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, items in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'unit-cost' =>"
        if "'shipping-fee' =>" not in content and target in content:
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
            print(f"Updated {loc}/app.php with shipping-fee and total-cost-with-shipping")
        else:
            print(f"Skipped {loc}")

print("Done updating translations for shipping-fee!")
