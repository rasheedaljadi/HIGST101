import os

root = r"e:\HIGESTO NEW1\higest\higest101"
terms = ["include_shipping_in_price", "exclude_choice_from_shipping_price", "shipping_margin", "shipping_extra_days"]

for dirpath, dirnames, filenames in os.walk(root):
    if any(skip in dirpath for skip in ["vendor", "node_modules", "storage", ".git"]):
        continue
    for f in filenames:
        if f.endswith(('.php', '.vue', '.js', '.blade.php', '.json', '.ts')):
            filepath = os.path.join(dirpath, f)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as file:
                    content = file.read()
                    for t in terms:
                        if t in content:
                            print(f"Match [{t}] in: {filepath}")
            except Exception as e:
                pass
