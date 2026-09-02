import os
import re

base_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"

# We read en/app.php structure
en_path = os.path.join(base_dir, 'en', 'app.php')
with open(en_path, 'r', encoding='utf-8') as f:
    en_content = f.read()

# All 21 locales
all_locales = [d for d in os.listdir(base_dir) if os.path.isdir(os.path.join(base_dir, d))]

# Keys to ensure exist in platform_orders:
platform_statuses_block = """        'statuses' => [
            'wait_buyer_pay' => 'Wait Buyer Pay',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'submission_failed' => 'Submission Failed',
        ],
"""

datagrid_view_line = "        'view' => 'View Details',\n"

batches_partially_submitted = "        'partially_submitted' => 'Partially Submitted',\n"

for loc in all_locales:
    if loc in ['en', 'ar']:
        continue
    loc_file = os.path.join(base_dir, loc, 'app.php')
    if not os.path.exists(loc_file):
        continue
    
    with open(loc_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    modified = False
    
    # 1. Add platform_orders.statuses if missing
    if "'statuses' =>" not in content and "'platform_orders' => [" in content:
        # insert after 'sync-all' or in platform_orders
        content = re.sub(
            r"('platform_orders'\s*=>\s*\[[^\]]*?)(\n\s*\],)",
            r"\1\n" + platform_statuses_block + r"\2",
            content,
            flags=re.DOTALL
        )
        modified = True

    # 2. Add 'view' in datagrid if missing
    if "'view' =>" not in content and "'datagrid' => [" in content:
        content = re.sub(
            r"('delete'\s*=>\s*'[^\n]*',\n)",
            r"\1" + datagrid_view_line,
            content
        )
        modified = True

    # 3. Add 'partially_submitted' in batches status if missing
    if "'partially_submitted' =>" not in content and "'submitted_to_provider' =>" in content:
        content = re.sub(
            r"('submitted_to_provider'\s*=>\s*'[^\n]*',\n)",
            r"\1" + batches_partially_submitted,
            content
        )
        modified = True
        
    if modified:
        with open(loc_file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {loc}/app.php successfully.")
    else:
        print(f"No changes needed for {loc}/app.php.")

print("Finished syncing Procurement translations!")
