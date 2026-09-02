import os
import re

locales_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

translations = {
    "ar": {
        "customer-selling-price": "سعر البيع للعميل",
        "system-cost": "التكلفة وفقاً للنظام",
        "aliexpress-cost": "تكلفة الشراء في علي إكسبرس",
    },
    "en": {
        "customer-selling-price": "Customer Selling Price",
        "system-cost": "System Cost",
        "aliexpress-cost": "AliExpress Cost",
    },
    "de": {
        "customer-selling-price": "Kundenverkaufspreis",
        "system-cost": "Systemkosten",
        "aliexpress-cost": "AliExpress-Kosten",
    },
    "es": {
        "customer-selling-price": "Precio de Venta al Cliente",
        "system-cost": "Costo del Sistema",
        "aliexpress-cost": "Costo de AliExpress",
    },
    "fr": {
        "customer-selling-price": "Prix de Vente Client",
        "system-cost": "Coût Système",
        "aliexpress-cost": "Coût AliExpress",
    },
    "it": {
        "customer-selling-price": "Prezzo di Vendita al Cliente",
        "system-cost": "Costo di Sistema",
        "aliexpress-cost": "Costo AliExpress",
    },
    "ja": {
        "customer-selling-price": "顧客販売価格",
        "system-cost": "システムコスト",
        "aliexpress-cost": "AliExpressコスト",
    },
    "nl": {
        "customer-selling-price": "Klantverkoopprijs",
        "system-cost": "Systeemkosten",
        "aliexpress-cost": "AliExpress-kosten",
    },
    "pl": {
        "customer-selling-price": "Cena Sprzedaży dla Klienta",
        "system-cost": "Koszt Systemowy",
        "aliexpress-cost": "Koszt AliExpress",
    },
    "pt_BR": {
        "customer-selling-price": "Preço de Venda ao Cliente",
        "system-cost": "Custo do Sistema",
        "aliexpress-cost": "Custo do AliExpress",
    },
    "ru": {
        "customer-selling-price": "Цена продажи клиенту",
        "system-cost": "Системная себестоимость",
        "aliexpress-cost": "Стоимость на AliExpress",
    },
    "tr": {
        "customer-selling-price": "Müşteri Satış Fiyatı",
        "system-cost": "Sistem Maliyeti",
        "aliexpress-cost": "AliExpress Maliyeti",
    },
    "zh_CN": {
        "customer-selling-price": "客户售价",
        "system-cost": "系统成本",
        "aliexpress-cost": "速卖通成本",
    },
    "id": {
        "customer-selling-price": "Harga Jual Pelanggan",
        "system-cost": "Biaya Sistem",
        "aliexpress-cost": "Biaya AliExpress",
    },
    "he": {
        "customer-selling-price": "מחיר מכירה ללקוח",
        "system-cost": "עלות מערכת",
        "aliexpress-cost": "עלות עלי אקספרס",
    },
    "hi_IN": {
        "customer-selling-price": "ग्राहक बिक्री मूल्य",
        "system-cost": "सिस्टम लागत",
        "aliexpress-cost": "AliExpress लागत",
    },
    "bn": {
        "customer-selling-price": "গ্রাহক বিক্রয় মূল্য",
        "system-cost": "সিস্টেম খরচ",
        "aliexpress-cost": "AliExpress খরচ",
    },
    "ca": {
        "customer-selling-price": "Preu de Venda al Client",
        "system-cost": "Cost del Sistema",
        "aliexpress-cost": "Cost AliExpress",
    },
    "fa": {
        "customer-selling-price": "قیمت فروش به مشتری",
        "system-cost": "هزینه سیستم",
        "aliexpress-cost": "هزینه علی اکسپرس",
    },
    "sin": {
        "customer-selling-price": "පාරිභෝගික විකුණුම් මිල",
        "system-cost": "පද්ධති පිරිවැය",
        "aliexpress-cost": "AliExpress පිරිවැය",
    },
    "uk": {
        "customer-selling-price": "Ціна продажу клієнту",
        "system-cost": "Системна собівартість",
        "aliexpress-cost": "Вартість AliExpress",
    },
}

for loc in os.listdir(locales_dir):
    app_file = os.path.join(locales_dir, loc, "app.php")
    if not os.path.isfile(app_file):
        continue
    
    with open(app_file, "r", encoding="utf-8") as f:
        content = f.read()

    trans = translations.get(loc, translations["en"])
    
    # Check if 'datagrid' => [ exists
    if "'datagrid' => [" in content:
        # Check if keys are already present
        if "'customer-selling-price'" not in content:
            replacement = f"""    'datagrid' => [
        'customer-selling-price' => '{trans['customer-selling-price']}',
        'system-cost' => '{trans['system-cost']}',
        'aliexpress-cost' => '{trans['aliexpress-cost']}',"""
            content = content.replace("    'datagrid' => [", replacement, 1)
            with open(app_file, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Updated {loc}/app.php")
        else:
            print(f"Already contains keys: {loc}")
    else:
        print(f"No datagrid block found in {loc}")

print("Done updating all 21 locales!")
