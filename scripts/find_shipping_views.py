import os

root = r"e:\HIGESTO NEW1\higest\higest101"

for dirpath, dirnames, filenames in os.walk(root):
    if any(skip in dirpath for skip in ["vendor", "node_modules", "storage", ".git"]):
        continue
    for f in filenames:
        if f.endswith(('.blade.php', '.php')):
            filepath = os.path.join(dirpath, f)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as file:
                    content = file.read()
                    if "base_shipping_cost" in content or "aliexpress_product_imports" in content:
                        print(f"Match in: {filepath}")
            except Exception as e:
                pass
