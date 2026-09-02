import os
import glob

locales = {
    'ar': 'التكلفة شامل رسوم الشحن',
    'bn': 'শিপিং ফি সহ খরচ',
    'ca': 'Cost incloses les despeses d\'enviament',
    'de': 'Kosten inklusive Versandkosten',
    'en': 'Cost Incl. Shipping',
    'es': 'Coste incluidos los gastos de envío',
    'fa': 'هزینه شامل هزینه ارسال',
    'fr': 'Coût incluant les frais de livraison',
    'he': 'עלות כולל דמי משלוח',
    'hi_IN': 'शिपिंग शुल्क सहित लागत',
    'id': 'Biaya Termasuk Ongkos Kirim',
    'it': 'Costo incluse spese di spedizione',
    'ja': '送料込みの原価',
    'nl': 'Kosten inclusief verzendkosten',
    'pl': 'Koszt z kosztami wysyłki',
    'pt_BR': 'Custo incluindo frete',
    'ru': 'Себестоимость с учетом доставки',
    'sin': 'නැව් ගාස්තු ඇතුළත් පිරිවැය',
    'tr': 'Kargo Dahil Maliyet',
    'uk': 'Собівартість з урахуванням доставки',
    'zh_CN': '含运费成本',
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, label in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'system-cost' =>"
        if "'cost-with-shipping' =>" not in content and target in content:
            # Add cost-with-shipping right after system-cost
            escaped_label = label.replace("'", "\\'")
            replacement = f"'system-cost' =>"
            lines = content.splitlines(True)
            new_lines = []
            for line in lines:
                new_lines.append(line)
                if "'system-cost'" in line:
                    indent = line[:len(line) - len(line.lstrip())]
                    new_lines.append(f"{indent}'cost-with-shipping' => '{escaped_label}',\n")
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.writelines(new_lines)
            print(f"Updated {loc}/app.php with cost-with-shipping")
        else:
            print(f"Skipped {loc} (already present or target not found)")

print("All 21 locale files processed!")
