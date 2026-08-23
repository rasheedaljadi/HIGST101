import json

# AliExpress Open Platform Official Dropshipping API Capabilities Matrix
capabilities_matrix = [
    {
        "operation": "OAuth Token & Refresh",
        "api_method": "system.oauth.token / system.oauth.token.refresh",
        "scope": "General Dropshipping OAuth",
        "mode": "read/write token store",
        "status": "ACTIVE & VERIFIED (HTTP 200)",
        "rate_limit": "20 QPS / Standard IOP Policy",
        "details": "Exchanges authorization code for access/refresh tokens. Stored encrypted server-side in aliexpress_tokens."
    },
    {
        "operation": "Product Data & Price Query",
        "api_method": "aliexpress.ds.product.get",
        "scope": "aliexpress.ds.product",
        "mode": "read",
        "status": "ACTIVE & VERIFIED (HTTP 200)",
        "rate_limit": "100 QPS",
        "details": "Retrieves real-time product price, SKU variations, and stock availability."
    },
    {
        "operation": "Order Status & Tracking Query",
        "api_method": "aliexpress.ds.order.get (or aliexpress.trade.order.get)",
        "scope": "aliexpress.ds.order / AE-Order & Transaction",
        "mode": "read",
        "status": "ENABLED FOR POLLED PLATFORM ORDERS",
        "rate_limit": "50 QPS",
        "details": "Fetches order status, logistic tracking number, and carrier updates. Zero side-effects on inventory or accounting."
    },
    {
        "operation": "Order Creation (Unpaid Order Placement)",
        "api_method": "aliexpress.ds.order.create",
        "scope": "aliexpress.ds.order / AE-Order & Transaction",
        "mode": "external write",
        "status": "AWAITING PER-ORDER USER AUTHORIZATION",
        "rate_limit": "20 QPS",
        "details": "Creates an unpaid order under seller account 4586371333. Idempotency key derived from Supplier PO number."
    },
    {
        "operation": "Auto-Payment / Direct Debit",
        "api_method": "N/A (Strictly Prohibited by System Architecture)",
        "scope": "payment",
        "mode": "financial write",
        "status": "BLOCKED (100% Manual Payment in AliExpress Console)",
        "rate_limit": "N/A",
        "details": "All financial settlements are executed manually by the authorized finance officer in the AliExpress console."
    },
    {
        "operation": "Order Cancellation",
        "api_method": "aliexpress.ds.order.cancel",
        "scope": "aliexpress.ds.order",
        "mode": "external write",
        "status": "BLOCKED PENDING EXPLICIT SEPARATE ORDER",
        "rate_limit": "10 QPS",
        "details": "Can only be invoked following a formal exception resolution or manual cancellation command."
    }
]

with open('scripts/aliexpress_api_capabilities_matrix.json', 'w', encoding='utf-8') as f:
    json.dump(capabilities_matrix, f, indent=2, ensure_ascii=False)

print("AliExpress API Capabilities Matrix generated successfully.")
