import os
import sys

root = r"e:\HIGESTO NEW1\higest\higest101"
terms = ["Choice", "خيارات الشحن", "دمج تكلفة", "Free Shipping", "shipping", "aliexpress", "dropship"]

print(f"Searching in {root}...")
for dirpath, dirnames, filenames in os.walk(root):
    # skip vendor, node_modules, storage, .git
    if any(skip in dirpath for skip in ["vendor", "node_modules", "storage", ".git"]):
        continue
    for f in filenames:
        if f.endswith(('.php', '.vue', '.js', '.blade.php', '.json', '.ts')):
            filepath = os.path.join(dirpath, f)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as file:
                    content = file.read()
                    for t in ["Choice", "خيارات الشحن", "دمج تكلفة", "Free Shipping Model", "مدة النقل", "حفظ خيارات"]:
                        if t.lower() in content.lower():
                            print(f"Match [{t}] in: {filepath}")
            except Exception as e:
                pass
print("Done search.")
