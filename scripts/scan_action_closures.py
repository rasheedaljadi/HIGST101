import os
import re

ROOT = r"e:\HIGESTO NEW1\higest\higest101"

route_files = []
for dirpath, dirnames, filenames in os.walk(ROOT):
    if any(skip in dirpath for skip in ["vendor", "node_modules", "storage", ".git"]):
        continue
    for f in filenames:
        if ("Routes" in dirpath or "routes" in dirpath) and f.endswith('.php'):
            route_files.append(os.path.join(dirpath, f))

print(f"Scanning {len(route_files)} route files for direct route action closures...")

# Pattern for Route::get/post/put/delete/match/any with closure action (not group callback)
pattern = re.compile(r'Route::(get|post|put|delete|patch|options|any|match)\s*\([^;]+?(function\s*\(|fn\s*\()[^;]+?\);', re.DOTALL)

for rf in route_files:
    with open(rf, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        # Find lines where Route::verb is followed by function/fn and not ->group
        lines = content.splitlines()
        for i, line in enumerate(lines):
            # Check if this line has a route action closure
            if re.search(r'Route::(get|post|put|delete|patch|options|any|match)\s*\([^,]+,\s*(function|fn)\s*\(', line):
                print(f"[ACTION CLOSURE] {rf}:{i+1}")
                print(f"   --> {line.strip()}")

print("Done scanning action closures.")
