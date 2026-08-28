import os
import re

ROOT = r"e:\HIGESTO NEW1\higest\higest101"

route_files = []
config_files = []

for dirpath, dirnames, filenames in os.walk(ROOT):
    if any(skip in dirpath for skip in ["vendor", "node_modules", "storage", ".git"]):
        continue
    for f in filenames:
        fp = os.path.join(dirpath, f)
        if "Routes" in dirpath or "routes" in dirpath:
            if f.endswith('.php'):
                route_files.append(fp)
        if "Config" in dirpath or "config" in dirpath:
            if f.endswith('.php'):
                config_files.append(fp)

print(f"Found {len(route_files)} route files and {len(config_files)} config files.")

print("\n--- 1. Scanning Route Files for Closures ---")
closure_pattern = re.compile(r'Route::\w+\s*\([^,]+,\s*(?:function\s*\(|fn\s*\()')
for rf in route_files:
    with open(rf, 'r', encoding='utf-8', errors='ignore') as file:
        content = file.read()
        matches = closure_pattern.findall(content)
        if matches:
            print(f"[Closure found] in {rf}:")
            for i, line in enumerate(content.splitlines()):
                if "function" in line or "fn " in line:
                    if any(m in line for m in ["Route::", "get(", "post(", "put(", "delete(", "patch("]):
                        print(f"   Line {i+1}: {line.strip()}")

print("\n--- 2. Scanning Config Files for Closures ---")
for cf in config_files:
    with open(cf, 'r', encoding='utf-8', errors='ignore') as file:
        content = file.read()
        if "function (" in content or "function(" in content or "fn(" in content or "fn (" in content:
            # check if it's a closure in array
            lines = content.splitlines()
            for i, line in enumerate(lines):
                if re.search(r'=>\s*(function|fn)\s*\(', line):
                    print(f"[Closure found in Config] {cf} line {i+1}: {line.strip()}")

print("\nScan complete.")
