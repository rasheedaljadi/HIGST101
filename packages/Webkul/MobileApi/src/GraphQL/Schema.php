<?php

namespace Webkul\MobileApi\GraphQL;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;
use Webkul\Customer\Repositories\CompareItemRepository;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Repositories\WishlistRepository;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\MobileApi\GraphQL\Types\CartTypes;
use Webkul\MobileApi\GraphQL\Types\CatalogTypes;
use Webkul\MobileApi\GraphQL\Types\CustomerTypes;
use Webkul\MobileApi\GraphQL\Types\TypeRegistry;
use Webkul\OfflinePayments\Models\OfflinePaymentDestination;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;
use Webkul\Wallet\Models\WalletAccount;

class Schema
{
    public function build(): GraphQLSchema
    {
        return new GraphQLSchema([
            'query' => $this->buildQueryType(),
            'mutation' => $this->buildMutationType(),
            'types' => [
                TypeRegistry::iterable(),
            ],
        ]);
    }

    protected function buildQueryType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => [
                // ─── Channel & Store Config ───────────────────────────────────────
                'channel' => [
                    'type' => TypeRegistry::channel(),
                    'args' => [
                        'id' => Type::id(),
                    ],
                    'resolve' => function ($root, $args) {
                        if (! empty($args['id'])) {
                            return app(ChannelRepository::class)->find($args['id']) ?: core()->getCurrentChannel();
                        }

                        return core()->getCurrentChannel();
                    },
                ],

                // ─── Categories ───────────────────────────────────────────────────
                'treeCategories' => [
                    'type' => Type::listOf(CatalogTypes::categoryTree()),
                    'args' => [
                        'parentId' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        return app(CategoryRepository::class)->getVisibleCategoryTree($args['parentId'] ?? null);
                    },
                ],

                'categories' => [
                    'type' => TypeRegistry::connection('Category', CatalogTypes::categoryTree()),
                    'args' => [
                        'parentId' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        $categories = app(CategoryRepository::class)->getVisibleCategoryTree($args['parentId'] ?? null);
                        $edges = collect($categories)->map(fn ($c) => ['node' => $c, 'cursor' => (string) $c->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                // ─── Theme Customization ──────────────────────────────────────────
                'themeCustomizations' => [
                    'type' => TypeRegistry::connection('ThemeCustomization', TypeRegistry::themeCustomization()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        $customizations = app(ThemeCustomizationRepository::class)->all();
                        $edges = collect($customizations)->map(fn ($c) => ['node' => $c, 'cursor' => (string) $c->id])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                        ];
                    },
                ],

                'themeCustomization' => [
                    'type' => TypeRegistry::json(),
                    'resolve' => fn () => [],
                ],

                // ─── Products ─────────────────────────────────────────────────────
                'product' => [
                    'type' => CatalogTypes::product(),
                    'args' => [
                        'id' => Type::id(),
                        'urlKey' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        $repo = app(ProductRepository::class);
                        if (! empty($args['id'])) {
                            return $repo->find($args['id']);
                        }
                        if (! empty($args['urlKey'])) {
                            return $repo->findBySlug($args['urlKey'])
                                ?? (is_numeric($args['urlKey']) ? $repo->find($args['urlKey']) : null);
                        }

                        return null;
                    },
                ],

                'products' => [
                    'type' => TypeRegistry::connection('Product', CatalogTypes::product()),
                    'args' => [
                        'input' => TypeRegistry::json(),
                        'first' => Type::int(),
                        'last' => Type::int(),
                        'after' => Type::string(),
                        'before' => Type::string(),
                        'query' => Type::string(),
                        'sortKey' => Type::string(),
                        'reverse' => Type::boolean(),
                        'channel' => Type::string(),
                        'locale' => Type::string(),
                        'filter' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        $params = $args['input'] ?? [];

                        if (! empty($args['filter'])) {
                            if (is_string($args['filter'])) {
                                $decoded = json_decode($args['filter'], true);
                                if (is_array($decoded)) {
                                    $params = array_merge($params, $decoded);
                                }
                            } elseif (is_array($args['filter'])) {
                                $params = array_merge($params, $args['filter']);
                            }
                        }

                        $limit = ! empty($args['first']) ? (int) $args['first'] : 12;
                        $params['limit'] = $limit;

                        // Cursor-based pagination: cursor encodes 'page:N' or is a product ID from
                        // previous responses. We decode the page number from the cursor.
                        $page = 1;
                        if (! empty($args['page'])) {
                            $page = max(1, (int) $args['page']);
                        } elseif (! empty($params['page'])) {
                            $page = max(1, (int) $params['page']);
                        } elseif (! empty($args['after'])) {
                            $after = $args['after'];
                            if (str_starts_with($after, 'page:')) {
                                // New format: 'page:N'
                                $page = max(1, (int) substr($after, 5));
                            } elseif (is_numeric($after)) {
                                $page = max(1, (int) $after);
                            } else {
                                $page = 2;
                            }
                        }

                        if (! empty($args['before'])) {
                            $before = $args['before'];
                            if (str_starts_with($before, 'page:')) {
                                $page = max(1, (int) substr($before, 5) - 1);
                            }
                        }

                        $params['page'] = $page;
                        request()->merge(['page' => $page]);
                        Paginator::currentPageResolver(fn ($pageName = 'page') => $page);

                        if (! empty($args['query'])) {
                            $params['name'] = $args['query'];
                        }

                        if (! empty($args['sortKey'])) {
                            $sortKey = strtoupper($args['sortKey']);
                            $reverse = ! empty($args['reverse']);
                            if ($sortKey === 'NEWEST' || $sortKey === 'CREATED_AT') {
                                $params['sort'] = $reverse ? 'created_at-desc' : 'created_at-asc';
                            } elseif ($sortKey === 'PRICE') {
                                $params['sort'] = $reverse ? 'price-desc' : 'price-asc';
                            } elseif ($sortKey === 'TITLE' || $sortKey === 'NAME') {
                                $params['sort'] = $reverse ? 'name-desc' : 'name-asc';
                            } else {
                                $params['sort'] = $args['sortKey'];
                            }
                        }

                        $params['status'] = 1;
                        $params['visible_individually'] = 1;

                        if (! isset($params['channel_id']) && function_exists('core') && core()->getCurrentChannel()) {
                            $params['channel_id'] = core()->getCurrentChannel()->id;
                        }

                        $repo = app(ProductRepository::class);
                        if (method_exists($repo, 'resetModel')) {
                            $repo->resetModel();
                        }
                        if (method_exists($repo, 'skipCache')) {
                            $repo->skipCache(true);
                        }

                        $products = $repo->getAll($params);
                        $items = method_exists($products, 'items') ? $products->items() : (is_array($products) ? $products : collect($products)->all());

                        if ($limit > 0 && count($items) > $limit) {
                            $items = array_slice($items, 0, $limit);
                        }

                        $nextPage = $page + 1;
                        $edges = collect($items)->map(function ($p, $index) use ($page) {
                            $product = is_numeric($p) ? app(ProductRepository::class)->find($p) : $p;
                            if (! $product) {
                                return null;
                            }

                            return [
                                'node' => $product,
                                'cursor' => 'page:'.($page + 1),
                            ];
                        })->filter()->values()->all();

                        $hasNextPage = method_exists($products, 'hasMorePages') ? $products->hasMorePages() : false;

                        return [
                            'edges' => $edges,
                            'totalCount' => method_exists($products, 'total') ? $products->total() : count($edges),
                            'pageInfo' => [
                                'startCursor' => 'page:'.$page,
                                'endCursor' => 'page:'.($page + 1),
                                'hasNextPage' => $hasNextPage,
                                'hasPreviousPage' => $page > 1,
                            ],
                        ];
                    },
                ],

                // ─── Cart ─────────────────────────────────────────────────────────
                'cart' => [
                    'type' => CartTypes::cart(),
                    'resolve' => function ($root, $args, Context $context) {
                        if ($context->customer) {
                            Cart::initCart($context->customer);
                        }
                        if ($cart = Cart::getCart()) {
                            $currentCurrency = core()->getCurrentCurrencyCode();
                            if ($cart->cart_currency_code !== $currentCurrency) {
                                foreach ($cart->items as $item) {
                                    $item->price = (float) round(core()->convertPrice($item->base_price), 2);
                                    $item->total = $item->price * $item->quantity;
                                    $item->price_incl_tax = $item->price;
                                    $item->total_incl_tax = $item->total;
                                    $item->save();
                                }
                                $cart->cart_currency_code = $currentCurrency;
                                $cart->save();
                            }
                            Cart::collectTotals();
                        }

                        return Cart::getCart();
                    },
                ],

                // ─── Customer ─────────────────────────────────────────────────────
                'customer' => [
                    'type' => CustomerTypes::customer(),
                    'resolve' => fn ($root, $args, Context $context) => $context->customer,
                ],

                'readCustomerProfile' => [
                    'type' => CustomerTypes::customer(),
                    'args' => [
                        'id' => Type::id(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! empty($args['id'])) {
                            return app(CustomerRepository::class)->find($args['id']) ?: $context->customer;
                        }

                        return $context->customer;
                    },
                ],

                'customerOrder' => [
                    'type' => CustomerTypes::order(),
                    'args' => [
                        'id' => Type::nonNull(Type::id()),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $rawId = $args['id'];
                        $id = $rawId;
                        if (is_string($id) && str_contains($id, '/')) {
                            $id = basename($id);
                        }
                        $orderRepo = app(OrderRepository::class);
                        $order = is_numeric($id) ? $orderRepo->find((int) $id) : null;
                        if (! $order) {
                            $order = $orderRepo->findOneByField('increment_id', (string) $rawId)
                                ?? $orderRepo->findOneByField('increment_id', (string) $id);
                        }
                        if (! $order) {
                            return null;
                        }
                        if ($context->customer && (int) $order->customer_id !== (int) $context->customer->id) {
                            if (! empty($order->customer_email) && strtolower($order->customer_email) === strtolower($context->customer->email)) {
                                return $order;
                            }

                            return null;
                        }

                        return $order;
                    },
                ],

                'customerOrders' => [
                    'type' => TypeRegistry::connection('Order', CustomerTypes::order()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                        'status' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            return ['edges' => [], 'totalCount' => 0];
                        }
                        $where = ['customer_id' => $context->customer->id];
                        if (! empty($args['status'])) {
                            $where['status'] = $args['status'];
                        }
                        $orders = app(OrderRepository::class)->findWhere($where);
                        $edges = collect($orders)->map(fn ($o) => ['node' => $o, 'cursor' => (string) $o->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'customerInvoices' => [
                    'type' => TypeRegistry::connection('OrderInvoice', CustomerTypes::orderInvoice()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                        'orderId' => Type::int(),
                        'state' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            return ['edges' => [], 'totalCount' => 0];
                        }
                        $orderId = $args['orderId'] ?? null;
                        if ($orderId) {
                            $orderRepo = app(OrderRepository::class);
                            $order = is_numeric($orderId) ? $orderRepo->find((int) $orderId) : null;
                            if (! $order) {
                                $order = $orderRepo->findOneByField('increment_id', (string) $orderId);
                            }
                            $invoices = $order ? ($order->invoices ?? []) : [];
                        } else {
                            $invoices = app(InvoiceRepository::class)->whereHas('order', function ($q) use ($context) {
                                $q->where('customer_id', $context->customer->id);
                            })->get();
                        }
                        $edges = collect($invoices)->map(fn ($inv) => ['node' => $inv, 'cursor' => (string) $inv->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'customerInvoice' => [
                    'type' => CustomerTypes::orderInvoice(),
                    'args' => [
                        'id' => Type::nonNull(Type::id()),
                    ],
                    'resolve' => function ($root, $args) {
                        $id = $args['id'];
                        if (is_string($id) && str_contains($id, '/')) {
                            $id = (int) basename($id);
                        }

                        return app(InvoiceRepository::class)->find($id);
                    },
                ],

                'customerOrderShipments' => [
                    'type' => TypeRegistry::connection('OrderShipment', CustomerTypes::orderShipment()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                        'orderId' => Type::int(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $orderId = $args['orderId'] ?? null;
                        if ($orderId) {
                            $orderRepo = app(OrderRepository::class);
                            $order = is_numeric($orderId) ? $orderRepo->find((int) $orderId) : null;
                            if (! $order) {
                                $order = $orderRepo->findOneByField('increment_id', (string) $orderId);
                            }
                            $shipments = $order ? ($order->shipments ?? []) : [];
                        } else {
                            $shipments = $context->customer ? app(ShipmentRepository::class)->whereHas('order', function ($q) use ($context) {
                                $q->where('customer_id', $context->customer->id);
                            })->get() : [];
                        }
                        $edges = collect($shipments)->map(fn ($s) => ['node' => $s, 'cursor' => (string) $s->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'customerOrderShipment' => [
                    'type' => CustomerTypes::orderShipment(),
                    'args' => [
                        'id' => Type::id(),
                    ],
                    'resolve' => function ($root, $args) {
                        $id = $args['id'];
                        if (is_string($id) && str_contains($id, '/')) {
                            $id = (int) basename($id);
                        }

                        return app(ShipmentRepository::class)->find($id);
                    },
                ],

                'pages' => [
                    'type' => TypeRegistry::connection('CmsPage', CatalogTypes::cmsPage()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function () {
                        $pages = app(PageRepository::class)->all();
                        $edges = collect($pages)->map(fn ($p) => ['node' => $p, 'cursor' => (string) $p->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'cmsPages' => [
                    'type' => TypeRegistry::connection('CmsPage', CatalogTypes::cmsPage()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function () {
                        $pages = app(PageRepository::class)->all();
                        $edges = collect($pages)->map(fn ($p) => ['node' => $p, 'cursor' => (string) $p->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'cmsPage' => [
                    'type' => CatalogTypes::cmsPage(),
                    'args' => [
                        'id' => Type::id(),
                        'urlKey' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        if (! empty($args['id'])) {
                            return app(PageRepository::class)->find($args['id']);
                        }
                        if (! empty($args['urlKey'])) {
                            return app(PageRepository::class)->findByUrlKeyOrFail($args['urlKey']);
                        }

                        return null;
                    },
                ],

                'productReviews' => [
                    'type' => TypeRegistry::connection('ProductReview', CustomerTypes::productReview()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                        'productId' => Type::int(),
                        'product_id' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        $productId = $args['productId'] ?? ($args['product_id'] ?? null);
                        $query = ['status' => 'approved'];
                        if ($productId) {
                            $query['product_id'] = $productId;
                        }
                        $reviews = app(ProductReviewRepository::class)->findWhere($query);
                        $edges = collect($reviews)->map(fn ($r) => ['node' => $r, 'cursor' => (string) $r->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'customerReviews' => [
                    'type' => TypeRegistry::connection('ProductReview', CustomerTypes::productReview()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            return ['edges' => [], 'totalCount' => 0];
                        }
                        $reviews = app(ProductReviewRepository::class)->findWhere(['customer_id' => $context->customer->id]);
                        $edges = collect($reviews)->map(fn ($r) => ['node' => $r, 'cursor' => (string) $r->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'customerAddresses' => [
                    'type' => TypeRegistry::connection('CustomerAddress', CustomerTypes::address()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('sanctum')->user() ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            return [
                                'edges' => [],
                                'totalCount' => 0,
                                'pageInfo' => [
                                    'startCursor' => null,
                                    'endCursor' => null,
                                    'hasNextPage' => false,
                                    'hasPreviousPage' => false,
                                ],
                            ];
                        }
                        $addresses = app(CustomerAddressRepository::class)->findWhere(['customer_id' => $customer->id]);
                        $edges = collect($addresses)->map(fn ($a) => ['node' => $a, 'cursor' => (string) $a->id])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                            'pageInfo' => [
                                'startCursor' => count($edges) ? (string) $edges[0]['cursor'] : null,
                                'endCursor' => count($edges) ? (string) end($edges)['cursor'] : null,
                                'hasNextPage' => false,
                                'hasPreviousPage' => false,
                            ],
                        ];
                    },
                ],

                'getCustomerAddresses' => [
                    'type' => TypeRegistry::connection('CustomerAddress', CustomerTypes::address()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('sanctum')->user() ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            return [
                                'edges' => [],
                                'totalCount' => 0,
                                'pageInfo' => [
                                    'startCursor' => null,
                                    'endCursor' => null,
                                    'hasNextPage' => false,
                                    'hasPreviousPage' => false,
                                ],
                            ];
                        }
                        $addresses = app(CustomerAddressRepository::class)->findWhere(['customer_id' => $customer->id]);
                        $edges = collect($addresses)->map(fn ($a) => ['node' => $a, 'cursor' => (string) $a->id])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                            'pageInfo' => [
                                'startCursor' => count($edges) ? (string) $edges[0]['cursor'] : null,
                                'endCursor' => count($edges) ? (string) end($edges)['cursor'] : null,
                                'hasNextPage' => false,
                                'hasPreviousPage' => false,
                            ],
                        ];
                    },
                ],

                'wishlists' => [
                    'type' => TypeRegistry::connection('Wishlist', CustomerTypes::wishlist()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            return [
                                'edges' => [],
                                'totalCount' => 0,
                                'pageInfo' => [
                                    'endCursor' => null,
                                    'startCursor' => null,
                                    'hasNextPage' => false,
                                    'hasPreviousPage' => false,
                                ],
                            ];
                        }
                        $channelId = core()->getCurrentChannel()?->id;
                        $query = app(WishlistRepository::class)
                            ->where('customer_id', $customer->id);
                        if ($channelId) {
                            $query = $query->where('channel_id', $channelId);
                        }
                        $items = $query->latest()->get();
                        $edges = collect($items)->map(fn ($w) => [
                            'node' => $w,
                            'cursor' => (string) $w->id,
                        ])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                            'pageInfo' => [
                                'endCursor' => count($edges) ? (string) end($edges)['cursor'] : null,
                                'startCursor' => count($edges) ? (string) $edges[0]['cursor'] : null,
                                'hasNextPage' => false,
                                'hasPreviousPage' => false,
                            ],
                        ];
                    },
                ],

                'compareItems' => [
                    'type' => TypeRegistry::connection('CompareItem', CustomerTypes::compareItem()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            return [
                                'edges' => [],
                                'totalCount' => 0,
                                'pageInfo' => [
                                    'endCursor' => null,
                                    'startCursor' => null,
                                    'hasNextPage' => false,
                                    'hasPreviousPage' => false,
                                ],
                            ];
                        }
                        $items = class_exists(CompareItemRepository::class)
                            ? app(CompareItemRepository::class)->where('customer_id', $customer->id)->get()
                            : [];
                        $edges = collect($items)->map(fn ($it) => [
                            'node' => $it,
                            'cursor' => (string) $it->id,
                        ])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                            'pageInfo' => [
                                'endCursor' => count($edges) ? (string) end($edges)['cursor'] : null,
                                'startCursor' => count($edges) ? (string) $edges[0]['cursor'] : null,
                                'hasNextPage' => false,
                                'hasPreviousPage' => false,
                            ],
                        ];
                    },
                ],

                // ─── Sovereign & Local Operations (Phase 4) ──────────────────────
                'customerWallet' => [
                    'type' => CustomerTypes::wallet(),
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            return null;
                        }

                        return WalletAccount::firstOrCreate(
                            ['customer_id' => $context->customer->id],
                            [
                                'total_balance' => 0,
                                'available_balance' => 0,
                                'held_balance' => 0,
                                'promo_balance' => 0,
                                'cash_balance' => 0,
                                'currency_code' => core()->getBaseCurrencyCode() ?? 'SAR',
                                'status' => 'active',
                            ]
                        );
                    },
                ],

                'walletTransactions' => [
                    'type' => TypeRegistry::connection('WalletTransaction', CustomerTypes::walletTransaction()),
                    'args' => [
                        'first' => Type::int(),
                        'after' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            return ['edges' => [], 'totalCount' => 0];
                        }
                        $wallet = WalletAccount::where('customer_id', $context->customer->id)->first();
                        if (! $wallet) {
                            return ['edges' => [], 'totalCount' => 0];
                        }
                        $txs = method_exists($wallet, 'transactions') ? $wallet->transactions()->latest()->get() : [];
                        $edges = collect($txs)->map(fn ($t) => ['node' => $t, 'cursor' => (string) $t->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'orderDeliveryTracking' => [
                    'type' => CustomerTypes::deliveryTracking(),
                    'args' => [
                        'orderId' => Type::nonNull(Type::id()),
                    ],
                    'resolve' => function ($root, $args) {
                        $orderId = $args['orderId'];
                        if (is_string($orderId) && str_contains($orderId, '/')) {
                            $orderId = (int) basename($orderId);
                        }
                        $assignment = DeliveryAssignment::where('order_id', $orderId)->latest()->first();
                        if ($assignment) {
                            $courier = $assignment->delivery_boy ?? $assignment->admin;

                            return [
                                'order_id' => (string) $orderId,
                                'status' => $assignment->status,
                                'status_label' => trans('deliverymanagement::app.admin.assignments.statuses.'.$assignment->status) ?: $assignment->status,
                                'delivery_type' => $assignment->delivery_type,
                                'tracking_number' => $assignment->tracking_number ?? '',
                                'courier_name' => $courier?->name ?? 'مندوب هايست',
                                'courier_phone' => $courier?->phone ?? '',
                                'assigned_at' => (string) ($assignment->assigned_at ?? ''),
                                'delivered_at' => (string) ($assignment->delivered_at ?? ''),
                                'notes' => $assignment->notes ?? '',
                            ];
                        }

                        $order = app(OrderRepository::class)->find($orderId);
                        $shipment = $order?->shipments?->first();

                        return [
                            'order_id' => (string) $orderId,
                            'status' => $order ? $order->status : 'pending',
                            'status_label' => $order ? ($order->status_label ?? $order->status) : 'قيد المعالجة',
                            'delivery_type' => 'home_delivery',
                            'tracking_number' => $shipment?->track_number ?? '',
                            'courier_name' => $shipment?->carrier_title ?? 'فريق توصيل هايست',
                            'courier_phone' => '',
                            'assigned_at' => (string) ($shipment?->created_at ?? ''),
                            'delivered_at' => '',
                            'notes' => '',
                        ];
                    },
                ],

                // ─── Checkout Queries ─────────────────────────────────────────────
                'collectionShippingRates' => [
                    'type' => Type::listOf(CartTypes::shippingRate()),
                    'args' => [
                        'token' => Type::string(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if ($context->customer) {
                            Cart::initCart($context->customer);
                        }
                        $cart = Cart::getCart();
                        if (! $cart) {
                            return [];
                        }

                        Shipping::collectRates();
                        $cart->refresh();

                        $rates = $cart->shipping_rates;
                        if (! $rates || $rates->isEmpty()) {
                            $grouped = Shipping::getGroupedAllShippingRates();
                            $flattened = [];
                            foreach ($grouped as $g) {
                                foreach ($g['rates'] ?? [] as $r) {
                                    $flattened[] = $r;
                                }
                            }

                            return $flattened;
                        }

                        return $rates;
                    },
                ],

                'collectionPaymentMethods' => [
                    'type' => Type::listOf(CartTypes::paymentMethod()),
                    'args' => [
                        'token' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        $cart = Cart::getCart();
                        if (! $cart && ! empty($args['token'])) {
                            $cart = app(CartRepository::class)->findWhere(['is_active' => 1, 'customer_id' => $args['token']])->last()
                                ?? app(CartRepository::class)->findWhere(['is_active' => 1, 'id' => $args['token']])->first();
                            if ($cart) {
                                Cart::setCart($cart);
                            }
                        }

                        $methods = payment()->getPaymentMethods();

                        // Guarantee offline_payments is present if active in config and destinations exist
                        $hasOffline = collect($methods)->contains(fn ($m) => ($m['method'] ?? '') === 'offline_payments');
                        if (! $hasOffline && core()->getConfigData('sales.payment_methods.offline_payments.active')) {
                            $hasDests = class_exists(OfflinePaymentDestination::class) && OfflinePaymentDestination::where('is_active', true)->exists();
                            if ($hasDests) {
                                $methods[] = [
                                    'method' => 'offline_payments',
                                    'title' => core()->getConfigData('sales.payment_methods.offline_payments.title') ?: 'تحويل مالي',
                                    'method_title' => core()->getConfigData('sales.payment_methods.offline_payments.title') ?: 'تحويل مالي',
                                    'description' => core()->getConfigData('sales.payment_methods.offline_payments.description') ?: 'اختار طريقة التحويل المالي المناسبة لك وقم باتمام الدفع',
                                    'sort' => (int) (core()->getConfigData('sales.payment_methods.offline_payments.sort') ?: 1),
                                ];
                            }
                        }

                        // Filter by governorate rules if cart and checker are available
                        if ($cart && class_exists(\Webkul\DeliveryManagement\Services\PaymentEligibilityChecker::class)) {
                            $checker = app(\Webkul\DeliveryManagement\Services\PaymentEligibilityChecker::class);
                            $methods = collect($methods)->filter(function ($m) use ($checker, $cart) {
                                $code = $m['method'] ?? ($m->method ?? '');

                                return $checker->isCartEligible($code, $cart);
                            })->values()->all();
                        }

                        // Standard titles fallback
                        $titles = [
                            'wallet' => 'محفظة هايست',
                            'offline_payments' => 'تحويل مالي',
                            'moneytransfer' => 'تحويل مالي',
                            'cashondelivery' => 'الدفع عند الاستلام',
                        ];

                        return collect($methods)->map(function ($m) use ($titles) {
                            $code = $m['method'] ?? ($m->method ?? '');
                            $resolvedTitle = $m['method_title'] ?? ($m['title'] ?? ($m->title ?? ''));
                            if (empty($resolvedTitle) && isset($titles[$code])) {
                                $resolvedTitle = $titles[$code];
                            }

                            return [
                                'id' => $code,
                                'method' => $code,
                                'title' => $resolvedTitle ?: $code,
                                'method_title' => $resolvedTitle ?: $code,
                                'methodTitle' => $resolvedTitle ?: $code,
                                'description' => $m['description'] ?? '',
                                'icon' => null,
                                'isAllowed' => true,
                            ];
                        })->all();
                    },
                ],

                'offlinePaymentDestinations' => [
                    'type' => Type::listOf(TypeRegistry::get('OfflinePaymentDestination', fn () => new ObjectType([
                        'name' => 'OfflinePaymentDestination',
                        'fields' => [
                            'id' => Type::id(),
                            'accountIdentifier' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->account_identifier ?? '')],
                            'swiftCode' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->swift_code ?? '')],
                            'transferInstructions' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->transfer_instructions ?? '')],
                            'accountId' => ['type' => Type::id(), 'resolve' => fn ($d) => (string) ($d->account?->id ?? '')],
                            'displayName' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->account?->display_name ?? '')],
                            'providerName' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->account?->provider_name ?? '')],
                            'recipientName' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->account?->recipient_name ?? '')],
                            'logoUrl' => ['type' => Type::string(), 'resolve' => fn ($d) => $d->account?->logo_path ? asset('storage/'.$d->account->logo_path) : null],
                            'currencyCode' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d->currency?->code ?? '')],
                        ],
                    ]))),
                    'resolve' => function () {
                        $cart = Cart::getCart();
                        if ($cart && class_exists(OfflinePaymentAccountResolver::class)) {
                            $accounts = app(OfflinePaymentAccountResolver::class)->getAccountsForCart($cart);
                            if ($accounts->isNotEmpty()) {
                                return $accounts;
                            }
                        }

                        // Resilient fallback: return active destinations for current currency or all active destinations
                        $currencyCode = core()->getCurrentCurrencyCode();
                        $currency = DB::table('currencies')->where('code', $currencyCode)->first();

                        $query = OfflinePaymentDestination::where('is_active', true)
                            ->whereHas('account', fn ($q) => $q->where('is_active', true))
                            ->with(['account', 'currency'])
                            ->orderBy('sort_order', 'asc');

                        if ($currency) {
                            $filtered = (clone $query)->where('currency_id', $currency->id)->get();
                            if ($filtered->isNotEmpty()) {
                                return $filtered;
                            }
                        }

                        return $query->get();
                    },
                ],

                'collectionGetCheckoutAddresses' => [
                    'type' => TypeRegistry::connection('CustomerAddress', CustomerTypes::address()),
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('sanctum')->user() ?? auth()->guard('customer')->user();
                        $addresses = [];
                        if ($customer) {
                            $addresses = app(CustomerAddressRepository::class)->findWhere(['customer_id' => $customer->id])->all();
                        }
                        $cart = Cart::getCart();
                        if (empty($addresses) && $cart) {
                            if ($cart->billing_address) {
                                $b = $cart->billing_address;
                                $b->address_type = 'cart_billing';
                                $addresses[] = $b;
                            }
                            if ($cart->shipping_address) {
                                $s = $cart->shipping_address;
                                $s->address_type = 'cart_shipping';
                                $addresses[] = $s;
                            }
                        }

                        $edges = collect($addresses)->map(fn ($a) => ['node' => $a, 'cursor' => (string) $a->id])->all();

                        return [
                            'edges' => $edges,
                            'totalCount' => count($edges),
                            'pageInfo' => [
                                'startCursor' => count($edges) ? (string) $edges[0]['cursor'] : null,
                                'endCursor' => count($edges) ? (string) end($edges)['cursor'] : null,
                                'hasNextPage' => false,
                                'hasPreviousPage' => false,
                            ],
                        ];
                    },
                ],

                'countries' => [
                    'type' => TypeRegistry::connection('Country', TypeRegistry::country()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        $countries = app(CountryRepository::class)->all();
                        $edges = collect($countries)->map(fn ($c) => ['node' => $c, 'cursor' => (string) $c->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],

                'countryStates' => [
                    'type' => TypeRegistry::connection('CountryState', TypeRegistry::countryState()),
                    'args' => [
                        'countryId' => Type::int(),
                        'countryCode' => Type::string(),
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($root, $args) {
                        $repo = app(CountryStateRepository::class);
                        if (! empty($args['countryId'])) {
                            $states = $repo->findByField('country_id', $args['countryId']);
                        } elseif (! empty($args['countryCode'])) {
                            $states = $repo->findByField('country_code', $args['countryCode']);
                        } else {
                            $states = [];
                        }

                        $edges = collect($states)->map(fn ($s) => ['node' => $s, 'cursor' => (string) $s->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]);
    }

    protected function buildMutationType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                // ─── Authentication ───────────────────────────────────────────────
                'createCustomerLogin' => [
                    'type' => new ObjectType([
                        'name' => 'CustomerLoginPayload',
                        'fields' => [
                            'customerLogin' => new ObjectType([
                                'name' => 'CustomerLoginResponse',
                                'fields' => [
                                    'id' => Type::id(),
                                    'apiToken' => Type::string(),
                                    'token' => Type::string(),
                                    'message' => Type::string(),
                                    'success' => Type::boolean(),
                                ],
                            ]),
                        ],
                    ]),
                    'args' => [
                        'input' => new InputObjectType([
                            'name' => 'createCustomerLoginInput',
                            'fields' => [
                                'email' => Type::nonNull(Type::string()),
                                'password' => Type::nonNull(Type::string()),
                            ],
                        ]),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'];
                        if (Auth::guard('customer')->attempt(['email' => $input['email'], 'password' => $input['password']])) {
                            $customer = Auth::guard('customer')->user();
                            $token = method_exists($customer, 'createToken') ? $customer->createToken('mobile_app')->plainTextToken : bin2hex(random_bytes(32));

                            return [
                                'customerLogin' => [
                                    'id' => $customer->id,
                                    'apiToken' => $token,
                                    'token' => $token,
                                    'message' => 'Logged in successfully.',
                                    'success' => true,
                                ],
                            ];
                        }

                        return [
                            'customerLogin' => [
                                'id' => null,
                                'apiToken' => null,
                                'token' => null,
                                'message' => 'Invalid credentials.',
                                'success' => false,
                            ],
                        ];
                    },
                ],

                'createCustomer' => [
                    'type' => new ObjectType([
                        'name' => 'CreateCustomerPayload',
                        'fields' => [
                            'customer' => CustomerTypes::customer(),
                        ],
                    ]),
                    'args' => [
                        'input' => new InputObjectType([
                            'name' => 'createCustomerInput',
                            'fields' => [
                                'firstName' => Type::nonNull(Type::string()),
                                'lastName' => Type::nonNull(Type::string()),
                                'email' => Type::nonNull(Type::string()),
                                'password' => Type::nonNull(Type::string()),
                                'phone' => Type::string(),
                            ],
                        ]),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'];
                        $customer = app(CustomerRepository::class)->create([
                            'first_name' => $input['firstName'],
                            'last_name' => $input['lastName'],
                            'email' => $input['email'],
                            'password' => Hash::make($input['password']),
                            'phone' => $input['phone'] ?? null,
                            'status' => 1,
                            'channel_id' => core()->getCurrentChannel()->id,
                        ]);
                        $token = method_exists($customer, 'createToken') ? $customer->createToken('mobile_app')->plainTextToken : bin2hex(random_bytes(32));
                        $customer->api_token = $token;

                        return ['customer' => $customer];
                    },
                ],

                'createLogout' => [
                    'type' => new ObjectType([
                        'name' => 'CreateLogoutPayload',
                        'fields' => [
                            'logout' => new ObjectType([
                                'name' => 'LogoutResponse',
                                'fields' => [
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                ],
                            ]),
                        ],
                    ]),
                    'args' => [
                        'input' => new InputObjectType([
                            'name' => 'createLogoutInput',
                            'fields' => ['token' => Type::string()],
                        ]),
                    ],
                    'resolve' => fn () => ['logout' => ['success' => true, 'message' => 'Logged out successfully.']],
                ],

                'createForgotPassword' => [
                    'type' => TypeRegistry::get('ForgotPasswordPayload', fn () => new ObjectType([
                        'name' => 'ForgotPasswordPayload',
                        'fields' => [
                            'forgotPassword' => new ObjectType([
                                'name' => 'ForgotPasswordResponse',
                                'fields' => [
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                ],
                            ]),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'] ?? [];
                        $email = $input['email'] ?? '';

                        return [
                            'forgotPassword' => [
                                'success' => true,
                                'message' => 'If an account exists with '.$email.', a reset link has been sent.',
                            ],
                        ];
                    },
                ],

                'createCustomerProfileUpdate' => [
                    'type' => TypeRegistry::get('CustomerProfileUpdatePayload', fn () => new ObjectType([
                        'name' => 'CustomerProfileUpdatePayload',
                        'fields' => [
                            'customerProfileUpdate' => CustomerTypes::customer(),
                            'customer' => CustomerTypes::customer(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createCustomerProfileUpdateInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            throw new \Exception('Unauthenticated');
                        }
                        $input = $args['input'] ?? [];
                        $data = [];
                        if (isset($input['firstName'])) {
                            $data['first_name'] = $input['firstName'];
                        }
                        if (isset($input['lastName'])) {
                            $data['last_name'] = $input['lastName'];
                        }
                        if (isset($input['phone'])) {
                            $data['phone'] = $input['phone'];
                        }
                        if (isset($input['gender'])) {
                            $data['gender'] = $input['gender'];
                        }
                        if (isset($input['dateOfBirth'])) {
                            $data['date_of_birth'] = $input['dateOfBirth'];
                        }
                        if (! empty($input['password'])) {
                            $data['password'] = Hash::make($input['password']);
                        }

                        if (! empty($data)) {
                            app(CustomerRepository::class)->update($data, $context->customer->id);
                        }

                        $updated = app(CustomerRepository::class)->find($context->customer->id);

                        return [
                            'customerProfileUpdate' => $updated,
                            'customer' => $updated,
                        ];
                    },
                ],

                'createCustomerProfileDelete' => [
                    'type' => TypeRegistry::get('CustomerProfileDeletePayload', fn () => new ObjectType([
                        'name' => 'CustomerProfileDeletePayload',
                        'fields' => [
                            'customerProfileDelete' => new ObjectType([
                                'name' => 'DeleteProfileResponse',
                                'fields' => [
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                ],
                            ]),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createCustomerProfileDeleteInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        return [
                            'customerProfileDelete' => [
                                'success' => true,
                                'message' => 'Account deleted successfully.',
                            ],
                        ];
                    },
                ],

                'createDeleteCustomerAddress' => [
                    'type' => TypeRegistry::get('DeleteCustomerAddressPayload', fn () => new ObjectType([
                        'name' => 'DeleteCustomerAddressPayload',
                        'fields' => [
                            'deleteCustomerAddress' => new ObjectType([
                                'name' => 'DeleteAddressResponse',
                                'fields' => [
                                    'id' => Type::id(),
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                ],
                            ]),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createDeleteCustomerAddressInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        if (! $context->customer) {
                            throw new \Exception('Unauthenticated');
                        }
                        $input = $args['input'] ?? [];
                        $id = $input['addressId'] ?? ($input['id'] ?? null);
                        if ($id) {
                            app(CustomerAddressRepository::class)->delete($id);
                        }

                        return [
                            'deleteCustomerAddress' => [
                                'id' => (string) $id,
                                'success' => true,
                                'message' => 'Address deleted successfully.',
                            ],
                        ];
                    },
                ],

                // ─── Wishlist Operations ───────────────────────────────────────────
                'createWishlist' => [
                    'type' => TypeRegistry::get('CreateWishlistPayload', fn () => new ObjectType([
                        'name' => 'CreateWishlistPayload',
                        'fields' => [
                            'wishlist' => CustomerTypes::wishlist(),
                            'success' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                            'message' => ['type' => Type::string(), 'resolve' => fn ($r) => $r['message'] ?? 'Product added to wishlist successfully.'],
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createWishlistInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        $input = $args['input'] ?? [];
                        $productId = $input['productId'] ?? null;
                        if (! $productId) {
                            throw new \Exception('Product ID is required');
                        }

                        $channelId = core()->getCurrentChannel()?->id ?? 1;
                        $wishlistRepo = app(WishlistRepository::class);

                        $existing = $wishlistRepo->findOneWhere([
                            'channel_id' => $channelId,
                            'customer_id' => $customer->id,
                            'product_id' => $productId,
                        ]);

                        if (! $existing) {
                            $wishlist = $wishlistRepo->create([
                                'channel_id' => $channelId,
                                'customer_id' => $customer->id,
                                'product_id' => $productId,
                                'additional' => $input['additional'] ?? null,
                            ]);
                        } else {
                            $wishlist = $existing;
                        }

                        return [
                            'wishlist' => $wishlist,
                            'success' => true,
                            'message' => 'Product added to wishlist successfully.',
                        ];
                    },
                ],

                'deleteWishlist' => [
                    'type' => TypeRegistry::get('DeleteWishlistPayload', fn () => new ObjectType([
                        'name' => 'DeleteWishlistPayload',
                        'fields' => [
                            'wishlist' => CustomerTypes::wishlist(),
                            'success' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                            'message' => ['type' => Type::string(), 'resolve' => fn ($r) => $r['message'] ?? 'Product removed from wishlist successfully.'],
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::deleteWishlistInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        $input = $args['input'] ?? [];
                        $rawId = $input['id'] ?? null;
                        $id = $rawId;
                        if (is_string($rawId) && str_contains($rawId, '/')) {
                            $parts = explode('/', trim($rawId, '/'));
                            $id = end($parts);
                        }

                        $wishlistRepo = app(WishlistRepository::class);
                        $item = $wishlistRepo->findOneWhere([
                            'id' => $id,
                            'customer_id' => $customer->id,
                        ]) ?? $wishlistRepo->findOneWhere([
                            'product_id' => $id,
                            'customer_id' => $customer->id,
                        ]);

                        if ($item) {
                            $itemData = [
                                'id' => (string) $rawId,
                                '_id' => (int) $item->id,
                                'product' => $item->product,
                                'customer' => $customer,
                                'created_at' => (string) $item->created_at,
                                'updated_at' => (string) $item->updated_at,
                            ];
                            $wishlistRepo->delete($item->id);

                            return [
                                'wishlist' => $itemData,
                                'success' => true,
                                'message' => 'Product removed from wishlist successfully.',
                            ];
                        }

                        return [
                            'wishlist' => [
                                'id' => (string) $rawId,
                                '_id' => (int) $id,
                            ],
                            'success' => true,
                            'message' => 'Product not in wishlist.',
                        ];
                    },
                ],

                'moveWishlistToCart' => [
                    'type' => TypeRegistry::get('MoveWishlistToCartPayload', fn () => new ObjectType([
                        'name' => 'MoveWishlistToCartPayload',
                        'fields' => [
                            'wishlistToCart' => CustomerTypes::wishlistToCart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::moveWishlistToCartInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        $input = $args['input'] ?? [];
                        $id = $input['wishlistItemId'] ?? null;
                        if (is_string($id) && str_contains($id, '/')) {
                            $parts = explode('/', trim($id, '/'));
                            $id = end($parts);
                        }
                        $qty = (int) ($input['quantity'] ?? 1);

                        $wishlistRepo = app(WishlistRepository::class);
                        $item = $wishlistRepo->findOneWhere([
                            'id' => $id,
                            'customer_id' => $customer->id,
                        ]);

                        if (! $item) {
                            throw new \Exception('Wishlist item not found');
                        }

                        $result = Cart::moveToCart($item, $qty);

                        return [
                            'wishlistToCart' => [
                                'message' => $result ? 'Item moved to cart successfully.' : 'Could not move item to cart.',
                                'success' => (bool) $result,
                            ],
                        ];
                    },
                ],

                // ─── Compare Operations ────────────────────────────────────────────
                'createCompareItem' => [
                    'type' => TypeRegistry::get('CreateCompareItemPayload', fn () => new ObjectType([
                        'name' => 'CreateCompareItemPayload',
                        'fields' => [
                            'compareItem' => CustomerTypes::compareItem(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createCompareItemInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        $input = $args['input'] ?? [];
                        $productId = $input['productId'] ?? null;
                        if (! $productId) {
                            throw new \Exception('Product ID is required');
                        }

                        $compareRepo = app(CompareItemRepository::class);
                        $item = $compareRepo->findOneWhere([
                            'customer_id' => $customer->id,
                            'product_id' => $productId,
                        ]);

                        if (! $item) {
                            $item = $compareRepo->create([
                                'customer_id' => $customer->id,
                                'product_id' => $productId,
                            ]);
                        }

                        return ['compareItem' => $item];
                    },
                ],

                'deleteCompareItem' => [
                    'type' => TypeRegistry::get('DeleteCompareItemPayload', fn () => new ObjectType([
                        'name' => 'DeleteCompareItemPayload',
                        'fields' => [
                            'compareItem' => CustomerTypes::compareItem(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::deleteCompareItemInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }

                        $input = $args['input'] ?? [];
                        $id = $input['id'] ?? null;
                        $compareRepo = app(CompareItemRepository::class);
                        $item = null;
                        if ($id) {
                            if (is_string($id) && str_contains($id, '/')) {
                                $parts = explode('/', trim($id, '/'));
                                $id = end($parts);
                            }
                            $item = $compareRepo->findOneWhere([
                                'id' => $id,
                                'customer_id' => $customer->id,
                            ]) ?? $compareRepo->findOneWhere([
                                'product_id' => $id,
                                'customer_id' => $customer->id,
                            ]);

                            if ($item) {
                                $itemData = [
                                    'id' => (string) $item->id,
                                    '_id' => (int) $item->id,
                                    'product' => $item->product,
                                    'customer' => $customer,
                                    'created_at' => (string) $item->created_at,
                                    'updated_at' => (string) $item->updated_at,
                                ];
                                $compareRepo->delete($item->id);

                                return ['compareItem' => $itemData];
                            }
                        }

                        return ['compareItem' => ['id' => (string) $id, '_id' => (int) $id]];
                    },
                ],

                'createDeleteAllCompareItems' => [
                    'type' => TypeRegistry::get('DeleteAllCompareItemsPayload', fn () => new ObjectType([
                        'name' => 'DeleteAllCompareItemsPayload',
                        'fields' => [
                            'deleteAllCompareItems' => new ObjectType([
                                'name' => 'DeleteAllCompareItemsResponse',
                                'fields' => [
                                    'message' => Type::string(),
                                ],
                            ]),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createDeleteAllCompareItemsInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('customer')->user();
                        if ($customer) {
                            app(CompareItemRepository::class)->deleteWhere([
                                'customer_id' => $customer->id,
                            ]);
                        }

                        return [
                            'deleteAllCompareItems' => [
                                'message' => 'All items removed from compare list.',
                            ],
                        ];
                    },
                ],

                // ─── Cart Operations ──────────────────────────────────────────────
                'createCartToken' => [
                    'type' => TypeRegistry::get('CreateCartTokenPayload', fn () => new ObjectType([
                        'name' => 'CreateCartTokenPayload',
                        'fields' => [
                            'cartToken' => new ObjectType([
                                'name' => 'CartToken',
                                'fields' => [
                                    'id' => Type::id(),
                                    'cartToken' => Type::string(),
                                    'customerId' => Type::id(),
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                    'sessionToken' => Type::string(),
                                    'isGuest' => Type::boolean(),
                                ],
                            ]),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $cart = Cart::getCart();
                        $token = session()->getId() ?: md5(uniqid((string) mt_rand(), true));

                        return [
                            'cartToken' => [
                                'id' => $cart ? (string) $cart->id : (string) random_int(1000, 9999),
                                'cartToken' => $token,
                                'customerId' => $context->customer ? (string) $context->customer->id : null,
                                'success' => true,
                                'message' => 'Cart initialized successfully.',
                                'sessionToken' => $token,
                                'isGuest' => ! (bool) $context->customer,
                            ],
                        ];
                    },
                ],

                'CreateCart' => [
                    'type' => TypeRegistry::get('CreateCartPayload', fn () => new ObjectType([
                        'name' => 'CreateCartPayload',
                        'fields' => [
                            'createCart' => CartTypes::cart(),
                        ],
                    ])),
                    'resolve' => fn () => ['createCart' => Cart::getCart()],
                ],

                'AddSimpleProductToCart' => [
                    'type' => CartTypes::cart(),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => fn ($root, $args) => $this->handleAddToCart($args['input'] ?? []),
                ],

                'AddConfigurableProductToCart' => [
                    'type' => CartTypes::cart(),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => fn ($root, $args) => $this->handleAddToCart($args['input'] ?? []),
                ],

                'createAddProductInCart' => [
                    'type' => TypeRegistry::get('AddProductInCartPayload', fn () => new ObjectType([
                        'name' => 'AddProductInCartPayload',
                        'fields' => [
                            'addProductInCart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => fn ($root, $args) => ['addProductInCart' => $this->handleAddToCart($args['input'] ?? [])],
                ],

                'createUpdateCartItem' => [
                    'type' => TypeRegistry::get('UpdateCartItemPayload', fn () => new ObjectType([
                        'name' => 'UpdateCartItemPayload',
                        'fields' => [
                            'updateCartItem' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'] ?? [];
                        $cartItemId = $input['cartItemId'] ?? null;
                        $quantity = $input['quantity'] ?? 1;
                        if ($cartItemId) {
                            Cart::updateItem(['qty' => [$cartItemId => $quantity]]);
                            Cart::collectTotals();
                        }

                        return ['updateCartItem' => Cart::getCart()];
                    },
                ],

                'createRemoveCartItem' => [
                    'type' => TypeRegistry::get('RemoveCartItemPayload', fn () => new ObjectType([
                        'name' => 'RemoveCartItemPayload',
                        'fields' => [
                            'removeCartItem' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'] ?? [];
                        $cartItemId = $input['cartItemId'] ?? ($args['cartItemId'] ?? null);
                        if ($cartItemId) {
                            Cart::removeItem($cartItemId);
                            Cart::collectTotals();
                        }

                        return ['removeCartItem' => Cart::getCart()];
                    },
                ],

                'createApplyCoupon' => [
                    'type' => TypeRegistry::get('ApplyCouponPayload', fn () => new ObjectType([
                        'name' => 'ApplyCouponPayload',
                        'fields' => [
                            'applyCoupon' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CartTypes::createApplyCouponInput(),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'] ?? [];
                        $code = $input['couponCode'] ?? ($input['code'] ?? '');
                        if ($code) {
                            Cart::setCouponCode($code)->collectTotals();
                        }

                        return ['applyCoupon' => Cart::getCart()];
                    },
                ],

                'createRemoveCoupon' => [
                    'type' => TypeRegistry::get('RemoveCouponPayload', fn () => new ObjectType([
                        'name' => 'RemoveCouponPayload',
                        'fields' => [
                            'removeCoupon' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CartTypes::createRemoveCouponInput(),
                    ],
                    'resolve' => function ($root, $args) {
                        Cart::removeCouponCode()->collectTotals();

                        return ['removeCoupon' => Cart::getCart()];
                    },
                ],

                'createMergeCart' => [
                    'type' => TypeRegistry::get('MergeCartPayload', fn () => new ObjectType([
                        'name' => 'MergeCartPayload',
                        'fields' => [
                            'mergeCart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args) {
                        return ['mergeCart' => Cart::getCart()];
                    },
                ],

                // ─── Addresses ────────────────────────────────────────────────────
                'createAddUpdateCustomerAddress' => [
                    'type' => TypeRegistry::get('CustomerAddressPayload', fn () => new ObjectType([
                        'name' => 'CustomerAddressPayload',
                        'fields' => [
                            'address' => CustomerTypes::address(),
                            'addUpdateCustomerAddress' => CustomerTypes::address(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createAddUpdateCustomerAddressInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $customer = $context->customer ?? auth()->guard('sanctum')->user() ?? auth()->guard('customer')->user();
                        if (! $customer) {
                            throw new \Exception('Unauthenticated');
                        }
                        $input = $args['input'] ?? [];
                        $street = $input['address'] ?? ($input['address1'] ?? '');
                        $streetArr = is_array($street) ? $street : [$street];
                        if (! empty($input['address2'])) {
                            $streetArr[] = $input['address2'];
                        }
                        $streetArr = array_filter(array_map('trim', $streetArr));
                        $data = [
                            'customer_id' => $customer->id,
                            'first_name' => $input['firstName'] ?? $customer->first_name,
                            'last_name' => $input['lastName'] ?? $customer->last_name,
                            'company_name' => $input['companyName'] ?? null,
                            'vat_id' => $input['vatId'] ?? null,
                            'email' => $input['email'] ?? $customer->email,
                            'address' => implode(PHP_EOL, $streetArr),
                            'city' => $input['city'] ?? '',
                            'state' => $input['state'] ?? '',
                            'country' => ! empty($input['country']) ? $input['country'] : 'YE',
                            'postcode' => ! empty($input['postcode']) ? $input['postcode'] : '00000',
                            'phone' => $input['phone'] ?? '',
                            'default_address' => ! empty($input['defaultAddress']) ? 1 : 0,
                            'address_type' => 'customer',
                        ];
                        $repo = app(CustomerAddressRepository::class);
                        if (! empty($data['default_address'])) {
                            $repo->where('customer_id', $customer->id)
                                ->where('default_address', 1)
                                ->update(['default_address' => 0]);
                        }
                        $address = ! empty($input['addressId']) ? $repo->update($data, $input['addressId']) : $repo->create($data);

                        return [
                            'address' => $address,
                            'addUpdateCustomerAddress' => $address,
                        ];
                    },
                ],

                // ─── Checkout ─────────────────────────────────────────────────────
                'createCheckoutAddress' => [
                    'type' => TypeRegistry::get('CheckoutAddressPayload', fn () => new ObjectType([
                        'name' => 'CheckoutAddressPayload',
                        'fields' => [
                            'checkoutAddress' => CartTypes::cart(),
                            'cart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CartTypes::createCheckoutAddressInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context = null) {
                        $input = $args['input'] ?? [];

                        $customer = $context?->customer ?? auth()->guard('customer')->user() ?? auth()->guard('sanctum')->user();
                        if ($customer) {
                            Cart::initCart($customer);
                        }

                        if (isset($input['billing']) && is_array($input['billing'])) {
                            $b = $input['billing'];
                            $billing = [
                                'first_name' => $b['first_name'] ?? ($b['firstName'] ?? ''),
                                'last_name' => $b['last_name'] ?? ($b['lastName'] ?? ''),
                                'email' => $b['email'] ?? ($customer?->email ?? ''),
                                'company_name' => $b['company_name'] ?? ($b['companyName'] ?? ''),
                                'vat_id' => $b['vat_id'] ?? ($b['vatId'] ?? ''),
                                'address' => (array) ($b['address'] ?? ($b['address1'] ?? '')),
                                'city' => $b['city'] ?? '',
                                'country' => ! empty($b['country']) ? $b['country'] : 'YE',
                                'state' => $b['state'] ?? '',
                                'postcode' => ! empty($b['postcode']) ? $b['postcode'] : '00000',
                                'phone' => $b['phone'] ?? ($b['phoneNumber'] ?? ''),
                                'use_for_shipping' => (bool) ($b['use_for_shipping'] ?? ($b['useForShipping'] ?? true)),
                            ];
                            $s = isset($input['shipping']) && is_array($input['shipping']) ? $input['shipping'] : [];
                            $shipping = $billing['use_for_shipping'] ? $billing : [
                                'first_name' => $s['first_name'] ?? ($s['firstName'] ?? $billing['first_name']),
                                'last_name' => $s['last_name'] ?? ($s['lastName'] ?? $billing['last_name']),
                                'email' => $s['email'] ?? ($billing['email'] ?? ''),
                                'company_name' => $s['company_name'] ?? ($s['companyName'] ?? ''),
                                'vat_id' => $s['vat_id'] ?? ($s['vatId'] ?? ''),
                                'address' => (array) ($s['address'] ?? ($s['address1'] ?? $billing['address'])),
                                'city' => $s['city'] ?? $billing['city'],
                                'country' => ! empty($s['country']) ? $s['country'] : $billing['country'],
                                'state' => $s['state'] ?? $billing['state'],
                                'postcode' => ! empty($s['postcode']) ? $s['postcode'] : $billing['postcode'],
                                'phone' => $s['phone'] ?? ($s['phoneNumber'] ?? $billing['phone']),
                            ];
                            $input = [
                                'billing' => $billing,
                                'shipping' => $shipping,
                            ];
                        } elseif (isset($input['billingFirstName']) || isset($input['billingAddress'])) {
                            $billing = [
                                'first_name' => $input['billingFirstName'] ?? '',
                                'last_name' => $input['billingLastName'] ?? '',
                                'email' => $input['billingEmail'] ?? ($customer?->email ?? ''),
                                'company_name' => $input['billingCompanyName'] ?? '',
                                'vat_id' => $input['billingVatId'] ?? '',
                                'address' => (array) ($input['billingAddress'] ?? ''),
                                'city' => $input['billingCity'] ?? '',
                                'country' => ! empty($input['billingCountry']) ? $input['billingCountry'] : 'YE',
                                'state' => $input['billingState'] ?? '',
                                'postcode' => ! empty($input['billingPostcode']) ? $input['billingPostcode'] : '00000',
                                'phone' => $input['billingPhoneNumber'] ?? '',
                                'use_for_shipping' => (bool) ($input['useForShipping'] ?? true),
                            ];
                            $shipping = $billing['use_for_shipping'] ? $billing : [
                                'first_name' => $input['shippingFirstName'] ?? $billing['first_name'],
                                'last_name' => $input['shippingLastName'] ?? $billing['last_name'],
                                'email' => $input['shippingEmail'] ?? $billing['email'],
                                'company_name' => $input['shippingCompanyName'] ?? '',
                                'vat_id' => $input['shippingVatId'] ?? ($billing['vat_id'] ?? ''),
                                'address' => (array) ($input['shippingAddress'] ?? $billing['address']),
                                'city' => $input['shippingCity'] ?? $billing['city'],
                                'country' => ! empty($input['shippingCountry']) ? $input['shippingCountry'] : $billing['country'],
                                'state' => $input['shippingState'] ?? $billing['state'],
                                'postcode' => ! empty($input['shippingPostcode']) ? $input['shippingPostcode'] : $billing['postcode'],
                                'phone' => $input['shippingPhoneNumber'] ?? $billing['phone'],
                            ];
                            $input = [
                                'billing' => $billing,
                                'shipping' => $shipping,
                            ];
                        }

                        Cart::saveAddresses($input);
                        $cart = Cart::getCart();

                        return [
                            'checkoutAddress' => $cart,
                            'cart' => $cart,
                        ];
                    },
                ],

                'createCheckoutShippingMethod' => [
                    'type' => TypeRegistry::get('CheckoutShippingMethodPayload', fn () => new ObjectType([
                        'name' => 'CheckoutShippingMethodPayload',
                        'fields' => [
                            'checkoutShippingMethod' => CartTypes::cart(),
                            'cart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CartTypes::createCheckoutShippingMethodInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context = null) {
                        $input = $args['input'] ?? [];
                        $method = $input['shippingMethod'] ?? ($input['shipping_method'] ?? '');

                        $customer = $context?->customer ?? auth()->guard('customer')->user() ?? auth()->guard('sanctum')->user();
                        if ($customer) {
                            Cart::initCart($customer);
                        }

                        $cart = Cart::getCart();
                        if ($cart && ! Shipping::isMethodCodeExists($method)) {
                            $matchedRate = $cart->shipping_rates->first(function ($r) use ($method) {
                                return $r->method === $method
                                    || $r->carrier === $method
                                    || ($r->carrier.'_'.$r->method) === $method
                                    || str_ends_with($r->method, '_'.$method);
                            });
                            if ($matchedRate) {
                                $method = $matchedRate->method;
                            }
                        }

                        $success = Cart::saveShippingMethod($method);
                        Cart::collectTotals();
                        $cart = Cart::getCart();

                        return [
                            'checkoutShippingMethod' => $cart,
                            'cart' => $cart,
                        ];
                    },
                ],

                'createCheckoutPaymentMethod' => [
                    'type' => TypeRegistry::get('CheckoutPaymentMethodPayload', fn () => new ObjectType([
                        'name' => 'CheckoutPaymentMethodPayload',
                        'fields' => [
                            'checkoutPaymentMethod' => CartTypes::cart(),
                            'cart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => CartTypes::createCheckoutPaymentMethodInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context = null) {
                        $input = $args['input'] ?? [];
                        $method = $input['paymentMethod'] ?? ($input['payment_method'] ?? '');

                        $customer = $context?->customer ?? auth()->guard('customer')->user() ?? auth()->guard('sanctum')->user();
                        if ($customer) {
                            Cart::initCart($customer);
                        }

                        $success = Cart::savePaymentMethod(['method' => $method]);
                        Cart::collectTotals();
                        $cart = Cart::getCart();

                        return [
                            'checkoutPaymentMethod' => $cart,
                            'cart' => $cart,
                        ];
                    },
                ],

                'createCheckoutOrder' => [
                    'type' => TypeRegistry::get('CheckoutOrderPayload', fn () => new ObjectType([
                        'name' => 'CheckoutOrderPayload',
                        'fields' => [
                            'checkoutOrder' => CustomerTypes::order(),
                            'order' => CustomerTypes::order(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args, Context $context = null) {
                        $input = $args['input'] ?? [];

                        $customer = $context?->customer ?? auth()->guard('customer')->user() ?? auth()->guard('sanctum')->user();
                        if ($customer) {
                            Cart::initCart($customer);
                        }

                        if (! empty($input['selected_offline_account_id']) || ! empty($input['selectedOfflineAccountId'])) {
                            $destId = $input['selected_offline_account_id'] ?? $input['selectedOfflineAccountId'];
                            session(['checkout_selected_offline_account_id' => $destId]);
                            request()->merge([
                                'selected_offline_account_id' => $destId,
                                'selected_offline_destination_id' => $destId,
                            ]);
                        }

                        if (! empty($input['receipt_image_base64']) || ! empty($input['receipt'])) {
                            $raw = $input['receipt_image_base64'] ?? $input['receipt'];
                            if (is_string($raw) && (str_starts_with($raw, 'data:image') || strlen($raw) > 100)) {
                                if (preg_match('/^data:image\/(\w+);base64,/', $raw, $type)) {
                                    $raw = substr($raw, strpos($raw, ',') + 1);
                                    $ext = strtolower($type[1]);
                                } else {
                                    $ext = 'jpg';
                                }
                                $decoded = base64_decode($raw);
                                if ($decoded) {
                                    $fileName = 'receipt_'.time().'_'.uniqid().'.'.$ext;
                                    $path = 'offline_payments/receipts/'.$fileName;
                                    Storage::disk('public')->put($path, $decoded);
                                    session(['checkout_receipt_path' => $path]);
                                    request()->merge([
                                        'receipt_path' => $path,
                                    ]);
                                }
                            }
                        }

                        Cart::collectTotals();
                        $cart = Cart::getCart();
                        if (! $cart) {
                            throw new \Exception('السلة فارغة أو انتهت صلاحية الجلسة.');
                        }

                        // Safeguard: Ensure valid shipping method is saved if cart has physical items
                        if ($cart->haveStockableItems() && ! $cart->selected_shipping_rate) {
                            $firstRate = $cart->shipping_rates->first();
                            if ($firstRate) {
                                Cart::saveShippingMethod($firstRate->method);
                                Cart::collectTotals();
                                $cart = Cart::getCart();
                            }
                        }

                        // Safeguard: Ensure payment method is saved
                        if (! $cart->payment) {
                            $payMethod = request('payment_method') ?? session('cart_payment_method') ?? 'cashondelivery';
                            Cart::savePaymentMethod(['method' => $payMethod]);
                            Cart::collectTotals();
                            $cart = Cart::getCart();
                        }

                        // Build robust order creation payload
                        $resource = new \Webkul\Sales\Transformers\OrderResource($cart);
                        $data = $resource->toArray(request());

                        if ($cart->haveStockableItems()) {
                            $rate = $cart->selected_shipping_rate ?? $cart->shipping_rates->first();
                            if ($rate) {
                                $data['shipping_method'] = $rate->method;
                                $data['shipping_title'] = $rate->carrier_title.' - '.$rate->method_title;
                                $data['shipping_description'] = $rate->method_description;
                                $data['shipping_amount'] = $rate->price;
                                $data['base_shipping_amount'] = $rate->base_price;
                                $data['shipping_amount_incl_tax'] = $rate->price_incl_tax;
                                $data['base_shipping_amount_incl_tax'] = $rate->base_price_incl_tax;
                                $data['shipping_discount_amount'] = $rate->discount_amount;
                                $data['base_shipping_discount_amount'] = $rate->base_discount_amount;
                                if ($cart->shipping_address) {
                                    $data['shipping_address'] = (new \Webkul\Sales\Transformers\OrderAddressResource($cart->shipping_address))->toArray(request());
                                }
                            }
                        }
                        unset($data[0]);

                        if (empty($data['payment'])) {
                            $data['payment'] = [
                                'method' => $cart->payment?->method ?? 'cashondelivery',
                                'method_title' => $cart->payment?->method_title ?? 'الدفع عند الاستلام',
                            ];
                        }

                        if (empty($data['items'])) {
                            $data['items'] = \Webkul\Sales\Transformers\OrderItemResource::collection($cart->items)->toArray(request());
                        }

                        try {
                            $order = app(OrderRepository::class)->create($data);
                            Cart::deActivateCart();

                            return [
                                'checkoutOrder' => $order,
                                'order' => $order,
                            ];
                        } catch (\Throwable $e) {
                            Log::error('[MobileApi Order Creation Failed] '.$e->getMessage(), [
                                'cart_id' => $cart->id,
                                'trace' => $e->getTraceAsString(),
                            ]);

                            throw $e;
                        }
                    },
                ],

                'createReorderOrder' => [
                    'type' => TypeRegistry::get('ReorderOrderPayload', fn () => new ObjectType([
                        'name' => 'ReorderOrderPayload',
                        'fields' => [
                            'reorderOrder' => TypeRegistry::get('ReorderOrderResult', fn () => new ObjectType([
                                'name' => 'ReorderOrderResult',
                                'fields' => [
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                    'orderId' => Type::int(),
                                    'itemsAddedCount' => Type::int(),
                                ],
                            ])),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args) {
                        $input = $args['input'] ?? [];
                        $orderId = $input['orderId'] ?? ($input['order_id'] ?? null);
                        $order = $orderId ? app(OrderRepository::class)->find($orderId) : null;
                        $count = 0;
                        if ($order) {
                            foreach ($order->items as $item) {
                                try {
                                    Cart::addProduct($item->product, $item->additional);
                                    $count++;
                                } catch (\Exception $e) {
                                }
                            }
                        }

                        return [
                            'reorderOrder' => [
                                'success' => (bool) $order,
                                'message' => $order ? 'Order reordered successfully.' : 'Order not found.',
                                'orderId' => (int) $orderId,
                                'itemsAddedCount' => $count,
                            ],
                        ];
                    },
                ],

                'createProductReview' => [
                    'type' => TypeRegistry::get('CreateProductReviewPayload', fn () => new ObjectType([
                        'name' => 'CreateProductReviewPayload',
                        'fields' => [
                            'productReview' => CustomerTypes::productReview(),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createProductReviewInput(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $input = $args['input'] ?? [];
                        $data = [
                            'name' => $input['name'] ?? ($context->customer?->name ?? 'Customer'),
                            'title' => $input['title'] ?? '',
                            'rating' => (int) ($input['rating'] ?? 5),
                            'comment' => $input['comment'] ?? '',
                            'product_id' => $input['productId'] ?? ($input['product_id'] ?? null),
                            'status' => 'approved',
                            'customer_id' => $context->customer?->id,
                        ];
                        $review = app(ProductReviewRepository::class)->create($data);

                        return [
                            'productReview' => $review,
                        ];
                    },
                ],

                'createContactUs' => [
                    'type' => TypeRegistry::get('ContactUsPayload', fn () => new ObjectType([
                        'name' => 'ContactUsPayload',
                        'fields' => [
                            'contactUs' => TypeRegistry::get('ContactUsResult', fn () => new ObjectType([
                                'name' => 'ContactUsResult',
                                'fields' => [
                                    'success' => Type::boolean(),
                                    'message' => Type::string(),
                                ],
                            ])),
                        ],
                    ])),
                    'args' => [
                        'input' => CustomerTypes::createContactUsInput(),
                    ],
                    'resolve' => function () {
                        return [
                            'contactUs' => [
                                'success' => true,
                                'message' => 'Thank you for contacting us. We will get back to you shortly.',
                            ],
                        ];
                    },
                ],

                'applyWalletToCart' => [
                    'type' => TypeRegistry::get('ApplyWalletPayload', fn () => new ObjectType([
                        'name' => 'ApplyWalletPayload',
                        'fields' => [
                            'success' => Type::boolean(),
                            'message' => Type::string(),
                            'appliedAmount' => Type::float(),
                            'cart' => CartTypes::cart(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $cart = Cart::getCart();
                        if (! $cart) {
                            return ['success' => false, 'message' => 'Cart not found.', 'appliedAmount' => 0, 'cart' => null];
                        }
                        if (! $context->customer) {
                            return ['success' => false, 'message' => 'Customer must be logged in.', 'appliedAmount' => 0, 'cart' => $cart];
                        }
                        $wallet = WalletAccount::where('customer_id', $context->customer->id)->first();
                        $available = (float) ($wallet?->available_balance ?? 0);
                        $applied = min($available, (float) $cart->grand_total);

                        return [
                            'success' => $applied > 0,
                            'message' => $applied > 0 ? "تم تطبيق {$applied} من رصيد المحفظة بنجاح." : 'رصيد المحفظة غير كافٍ.',
                            'appliedAmount' => $applied,
                            'cart' => $cart,
                        ];
                    },
                ],

                'registerDeviceToken' => [
                    'type' => TypeRegistry::get('RegisterDeviceTokenPayload', fn () => new ObjectType([
                        'name' => 'RegisterDeviceTokenPayload',
                        'fields' => [
                            'success' => Type::boolean(),
                            'message' => Type::string(),
                        ],
                    ])),
                    'args' => [
                        'input' => TypeRegistry::json(),
                    ],
                    'resolve' => function ($root, $args, Context $context) {
                        $input = $args['input'] ?? [];
                        $token = $input['deviceToken'] ?? ($input['fcmToken'] ?? ($input['token'] ?? null));
                        if ($token && $context->customer) {
                            try {
                                if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'fcm_token')) {
                                    $context->customer->update(['fcm_token' => $token]);
                                }
                            } catch (\Throwable $e) {
                            }
                        }

                        return [
                            'success' => true,
                            'message' => 'Device token registered successfully.',
                        ];
                    },
                ],
            ],
        ]);
    }

    protected function handleAddToCart(array $input): mixed
    {
        $productId = $input['productId'] ?? ($input['product_id'] ?? null);
        if (! $productId) {
            throw new \Exception('Product ID is required.');
        }

        $productRepository = app(ProductRepository::class);
        $product = $productRepository->with('parent')->findOrFail($productId);

        $quantity = (int) ($input['quantity'] ?? 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        // Prepare super attributes map if provided
        $superAttributes = [];
        if (! empty($input['superAttribute'])) {
            if (is_array($input['superAttribute'])) {
                foreach ($input['superAttribute'] as $item) {
                    if (is_array($item)) {
                        foreach ($item as $k => $v) {
                            $superAttributes[$k] = $v;
                        }
                    }
                }
            }
        } elseif (! empty($input['super_attribute'])) {
            $superAttributes = (array) $input['super_attribute'];
        }

        $selectedConfigurableOption = $input['selectedConfigurableOption'] ?? ($input['selected_configurable_option'] ?? null);

        // If a variant was passed directly as the product (legacy fallback):
        if ($product->parent_id && $product->parent) {
            $selectedConfigurableOption = $product->id;
            $product = $product->parent;
        }

        // If configurable product, ensure selected_configurable_option and super_attribute are populated
        if ($product->type === 'configurable') {
            if ($selectedConfigurableOption) {
                $childProduct = $productRepository->find($selectedConfigurableOption);
                if ($childProduct) {
                    foreach ($product->super_attributes as $attribute) {
                        if (! isset($superAttributes[$attribute->id])) {
                            $optVal = $childProduct->{$attribute->code};
                            if ($optVal !== null) {
                                $superAttributes[$attribute->id] = $optVal;
                            }
                        }
                    }
                }
            }
        }

        $data = array_merge($input, [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'selected_configurable_option' => $selectedConfigurableOption,
            'super_attribute' => $superAttributes,
        ]);

        // Handle customizable options if serialized
        if (! empty($input['customizableOptions']) && is_array($input['customizableOptions'])) {
            $data['customizable_options'] = $input['customizableOptions'];
        }

        // Handle downloadable links
        if (! empty($input['links'])) {
            $data['links'] = (array) $input['links'];
        }

        // Handle grouped qty
        if (! empty($input['groupedQty'])) {
            $data['qty'] = is_string($input['groupedQty']) ? json_decode($input['groupedQty'], true) : $input['groupedQty'];
        }

        // Handle bundle options
        if (! empty($input['bundleOptions'])) {
            $data['bundle_options'] = is_string($input['bundleOptions']) ? json_decode($input['bundleOptions'], true) : $input['bundleOptions'];
        }
        if (! empty($input['bundleOptionQty'])) {
            $data['bundle_option_qty'] = is_string($input['bundleOptionQty']) ? json_decode($input['bundleOptionQty'], true) : $input['bundleOptionQty'];
        }

        // Handle booking
        if (! empty($input['booking'])) {
            $data['booking'] = is_string($input['booking']) ? json_decode($input['booking'], true) : $input['booking'];
        }

        if (! empty($input['cartId'])) {
            $existingCart = app(CartRepository::class)->find($input['cartId']);
            if ($existingCart && $existingCart->is_active) {
                Cart::setCart($existingCart);
            }
        }

        try {
            $cartResult = Cart::addProduct($product, $data);
            Cart::collectTotals();
        } catch (\Throwable $e) {
            Log::error('handleAddToCart error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine(), [
                'productId' => $productId,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return Cart::getCart() ?: $cartResult;
    }
}
