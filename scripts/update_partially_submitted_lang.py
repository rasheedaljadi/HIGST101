import os

locales = {
    'ar': {'partially_submitted': 'تم الإرسال جزئياً'},
    'bn': {'partially_submitted': 'আংশিকভাবে জমা দেওয়া হয়েছে'},
    'ca': {'partially_submitted': 'Enviat parcialment'},
    'de': {'partially_submitted': 'Teilweise übermittelt'},
    'en': {'partially_submitted': 'Partially Submitted'},
    'es': {'partially_submitted': 'Enviado parcialmente'},
    'fa': {'partially_submitted': 'به‌صورت جزئی ارسال شد'},
    'fr': {'partially_submitted': 'Partiellement soumis'},
    'he': {'partially_submitted': 'נשלח חלקית'},
    'hi_IN': {'partially_submitted': 'आंशिक रूप से जमा किया गया'},
    'id': {'partially_submitted': 'Sebagian Dikirim'},
    'it': {'partially_submitted': 'Parzialmente inviato'},
    'ja': {'partially_submitted': '一部送信済み'},
    'nl': {'partially_submitted': 'Gedeeltelijk verzonden'},
    'pl': {'partially_submitted': 'Częściowo przesłane'},
    'pt_BR': {'partially_submitted': 'Parcialmente enviado'},
    'ru': {'partially_submitted': 'Частично отправлено'},
    'sin': {'partially_submitted': 'කොටසක් යවන ලදී'},
    'tr': {'partially_submitted': 'Kısmen Gönderildi'},
    'uk': {'partially_submitted': 'Частково надіслано'},
    'zh_CN': {'partially_submitted': '部分已提交'},
}

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

for loc, items in locales.items():
    file_path = os.path.join(base_dir, loc, 'app.php')
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        target = "'submitted_to_provider' =>"
        if "'partially_submitted' =>" not in content and target in content:
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
            print(f"Updated {loc}/app.php with partially_submitted")
        else:
            print(f"Skipped {loc}")

print("Done updating translations for partially_submitted!")
