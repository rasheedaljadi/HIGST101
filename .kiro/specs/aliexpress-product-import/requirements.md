# Requirements Document

## Introduction

This feature delivers a proof-of-concept, single-product import from AliExpress into the Bagisto 2.4.x catalog. An administrator pastes one AliExpress product identifier (or a product URL containing it) into a dedicated admin screen and clicks Import. The system fetches the full product payload from the AliExpress Drop Shipping API (`aliexpress.ds.product.get`) using the existing stored OAuth access token and signed API client, then creates a corresponding Bagisto product so it appears in the standard Admin > Products listing.

The import maps everything that has a home in Bagisto's database: title, descriptions, all variants (configurable products with simple variant children, or a single simple product), all images (downloaded and stored locally), per-variant price and stock, variant attributes (auto-created when missing, including their option values), SEO fields, url_key, and a source reference linking the Bagisto product to its AliExpress product id. The product is assigned to the default category and a default attribute family.

This step is intentionally narrow. AI/content rewriting, bulk import, queues, and asynchronous processing are explicitly out of scope and reserved for future steps. The store operates with Arabic (`ar`) as the locale and USD as the base currency.

## Glossary

- **Admin_User**: An authenticated Bagisto administrator accessing the admin panel.
- **Import_System**: The new feature subsystem (admin menu, page, controller, and import service) introduced by this spec.
- **Import_Service**: The server-side component within the Import_System that orchestrates fetching AliExpress data and creating the Bagisto product.
- **ID_Extractor**: The component within the Import_System that derives an AliExpress product id from raw input or a pasted URL.
- **AliExpress_API_Client**: The existing `App\Services\AliExpress\AliExpressApiClient`, which performs signed calls via `call($method, $accessToken, $params)`.
- **OAuth_Service**: The existing `App\Services\AliExpress\AliExpressOAuthService`, whose `latestToken()` returns the most recent stored token (auto-refreshing when expired).
- **AliExpress_Token**: A stored OAuth credential record (`App\Models\AliExpressToken`) holding an encrypted access token.
- **Product_Get_Method**: The AliExpress API method `aliexpress.ds.product.get`.
- **AliExpress_Product_ID**: The numeric identifier of an AliExpress product.
- **Product_Payload**: The full decoded response body returned by the Product_Get_Method for one AliExpress_Product_ID.
- **Bagisto_Product**: A product record created in the Bagisto catalog through `ProductRepository::create()`.
- **Configurable_Product**: A Bagisto product of type `configurable` with one or more simple variant children, created with `super_attributes`.
- **Simple_Product**: A Bagisto product of type `simple` with no variant children.
- **Variant_Attribute**: A Bagisto EAV attribute used as a configurable axis (for example color or size), each having a Contract, Model, and Proxy.
- **Attribute_Option**: A selectable value of a Variant_Attribute, stored in `attribute_options` with a numeric id.
- **Default_Category**: The store's default category to which imported products are assigned.
- **Default_Attribute_Family**: The default attribute family assigned to imported products.
- **Source_Reference**: A persisted link associating a Bagisto_Product with the AliExpress_Product_ID it was imported from.
- **AliExpress_Log_Channel**: The existing `aliexpress` Laravel log channel (`storage/logs/aliexpress-*.log`).
- **Drop_Shipping_Menu**: A new top-level admin sidebar menu titled "Drop Shipping".
- **Import_Products_Page**: The admin page (sub-item of the Drop_Shipping_Menu) where the import is initiated.

## Requirements

### Requirement 1: Drop Shipping admin menu and Import Products page

**User Story:** As an Admin_User, I want a dedicated "Drop Shipping > Import Products" area in the admin panel, so that I can reach the product import screen from the standard navigation.

#### Acceptance Criteria

1. THE Import_System SHALL register a top-level Drop_Shipping_Menu in the Bagisto admin sidebar following the existing admin `menu.php` configuration pattern.
2. THE Import_System SHALL register an Import Products sub-item under the Drop_Shipping_Menu that links to the Import_Products_Page route.
3. WHEN an Admin_User opens the Import_Products_Page, THE Import_System SHALL display a single text input for an AliExpress_Product_ID or product URL and an Import control.
4. WHERE the requesting user is not an authenticated Admin_User, THE Import_System SHALL deny access to the Import_Products_Page using the standard admin authentication guard.

### Requirement 2: Extract the AliExpress product id from input

**User Story:** As an Admin_User, I want to paste either a raw AliExpress product id or a full product URL, so that I do not have to manually parse the id myself.

#### Acceptance Criteria

1. WHEN the submitted input consists solely of digits, THE ID_Extractor SHALL treat the input as the AliExpress_Product_ID.
2. WHEN the submitted input is a product URL containing a numeric product id segment, THE ID_Extractor SHALL extract the AliExpress_Product_ID from the URL.
3. IF the ID_Extractor cannot derive a numeric AliExpress_Product_ID from the submitted input, THEN THE Import_System SHALL reject the request and display a validation message identifying the invalid input.
4. WHEN the submitted input is empty, THE Import_System SHALL reject the request and display a validation message requiring an AliExpress_Product_ID or product URL.

### Requirement 3: Require a valid stored AliExpress access token

**User Story:** As an Admin_User, I want the system to confirm a usable AliExpress credential before importing, so that I receive a clear message instead of a silent failure when authorization is missing.

#### Acceptance Criteria

1. WHEN an import is requested, THE Import_System SHALL obtain the current AliExpress_Token via the OAuth_Service `latestToken()` method.
2. IF no AliExpress_Token is stored, THEN THE Import_System SHALL abort the import and display a message stating that AliExpress authorization is required.
3. IF the obtained AliExpress_Token has no valid access token after the OAuth_Service refresh attempt, THEN THE Import_System SHALL abort the import and display a message stating that the AliExpress access token is missing or expired.

### Requirement 4: Fetch full product data from AliExpress

**User Story:** As an Admin_User, I want the system to retrieve the complete product record from AliExpress, so that all importable details are available for the Bagisto_Product.

#### Acceptance Criteria

1. WHEN a valid AliExpress_Product_ID and a valid access token are available, THE Import_Service SHALL call the Product_Get_Method through the AliExpress_API_Client with the AliExpress_Product_ID.
2. WHEN the AliExpress_API_Client returns a successful response, THE Import_Service SHALL use the returned Product_Payload as the source data for product creation.
3. IF the AliExpress_API_Client returns an unsuccessful response, THEN THE Import_System SHALL abort the import and display a message containing the AliExpress error reason.
4. IF the Product_Payload contains no product data for the requested AliExpress_Product_ID, THEN THE Import_System SHALL abort the import and display a message stating that the product was not found.

### Requirement 5: Prevent duplicate import of the same AliExpress product

**User Story:** As an Admin_User, I want the system to recognize products I have already imported, so that I do not unintentionally create duplicate Bagisto_Products.

#### Acceptance Criteria

1. THE Import_System SHALL persist a Source_Reference linking each created Bagisto_Product to its originating AliExpress_Product_ID.
2. WHEN an import is requested for an AliExpress_Product_ID that already has a Source_Reference, THE Import_System SHALL abort the new import and display a message identifying the existing Bagisto_Product.

### Requirement 6: Create a Bagisto product of the correct type

**User Story:** As an Admin_User, I want imported products created with the right product type, so that products with variants become configurable products and single-SKU products become simple products.

#### Acceptance Criteria

1. WHEN the Product_Payload describes more than one purchasable variant, THE Import_Service SHALL create a Configurable_Product through `ProductRepository::create()` with `super_attributes` for the variant axes.
2. WHEN the Product_Payload describes exactly one purchasable SKU with no variant axes, THE Import_Service SHALL create a Simple_Product through `ProductRepository::create()`.
3. THE Import_Service SHALL assign each created Bagisto_Product to the Default_Attribute_Family.
4. THE Import_Service SHALL assign each created Bagisto_Product to the Default_Category.
5. WHEN a Bagisto_Product has been created, THE Import_System SHALL make it visible in the standard Admin > Products listing.

### Requirement 7: Import textual content and SEO fields

**User Story:** As an Admin_User, I want titles, descriptions, and SEO fields imported as-is, so that the Bagisto_Product reflects the AliExpress listing without manual re-entry.

#### Acceptance Criteria

1. THE Import_Service SHALL set the Bagisto_Product name from the Product_Payload title.
2. THE Import_Service SHALL set the Bagisto_Product description and short description from the corresponding Product_Payload content.
3. THE Import_Service SHALL set the Bagisto_Product meta_title, meta_keywords, and meta_description from the Product_Payload, using the imported title as the meta_title when the Product_Payload provides no SEO metadata.
4. THE Import_Service SHALL set a unique url_key for the Bagisto_Product derived from the imported title.
5. IF a url_key derived from the imported title already exists, THEN THE Import_Service SHALL append the AliExpress_Product_ID to the url_key to keep the value unique.
6. THE Import_Service SHALL store imported textual content as-is without AI rewriting or content transformation.

### Requirement 8: Auto-create missing variant attributes and option values

**User Story:** As an Admin_User, I want variant properties such as color and size created automatically, so that imported variants map to real Bagisto attributes without manual setup.

#### Acceptance Criteria

1. WHEN the Product_Payload defines a variant property that has no matching Variant_Attribute in Bagisto, THE Import_Service SHALL create the Variant_Attribute before creating variants.
2. WHEN the Product_Payload defines a variant property value that has no matching Attribute_Option for its Variant_Attribute, THE Import_Service SHALL create the Attribute_Option before creating variants.
3. THE Import_Service SHALL reference each Attribute_Option by its numeric attribute_options id when assigning variant attribute values.
4. WHEN a matching Variant_Attribute or Attribute_Option already exists, THE Import_Service SHALL reuse the existing record rather than creating a duplicate.

### Requirement 9: Map per-variant price, stock, and attribute values

**User Story:** As an Admin_User, I want each variant's price and stock imported accurately, so that the configurable product reflects the AliExpress variant data.

#### Acceptance Criteria

1. WHEN creating a Configurable_Product, THE Import_Service SHALL create one simple variant product for each purchasable variant in the Product_Payload.
2. THE Import_Service SHALL set each variant product price from the corresponding Product_Payload variant price.
3. THE Import_Service SHALL set each variant product inventory quantity from the corresponding Product_Payload variant stock using `ProductInventoryRepository::saveInventories()`.
4. THE Import_Service SHALL assign each variant product the Attribute_Option values that identify its variant axes using `attributeValueRepository->saveValues()`.
5. WHEN creating a Simple_Product, THE Import_Service SHALL set the product price and inventory quantity from the single SKU in the Product_Payload.
6. THE Import_Service SHALL record all monetary values in USD as the base currency.

### Requirement 10: Download and store product and variant images locally

**User Story:** As an Admin_User, I want product and variant images saved into the store, so that the Bagisto_Product displays its own locally hosted images.

#### Acceptance Criteria

1. WHEN the Product_Payload provides product image URLs, THE Import_Service SHALL download each image and store it locally through `ProductImageRepository::upload()`.
2. WHERE the Product_Payload provides variant-specific image URLs, THE Import_Service SHALL download and associate those images with the corresponding variant product.
3. IF an individual image download fails, THEN THE Import_Service SHALL record the failure to the AliExpress_Log_Channel and continue importing the remaining images.

### Requirement 11: Synchronous single-product processing

**User Story:** As an Admin_User, I want the import to run immediately for one product, so that I can verify the result without configuring background queues.

#### Acceptance Criteria

1. WHEN an import is requested, THE Import_System SHALL process exactly one AliExpress_Product_ID per request.
2. THE Import_System SHALL process the import synchronously within the request without enqueuing background jobs.
3. WHEN the import completes successfully, THE Import_System SHALL display a confirmation that identifies the created Bagisto_Product.

### Requirement 12: Error handling and safe logging

**User Story:** As an Admin_User, I want failures handled cleanly and recorded without exposing secrets, so that I can diagnose problems while credentials stay protected.

#### Acceptance Criteria

1. WHEN an import step succeeds or fails, THE Import_System SHALL write a corresponding entry to the AliExpress_Log_Channel.
2. THE Import_System SHALL exclude access tokens, refresh tokens, and the app secret from all log entries.
3. IF any import step raises an error after the Bagisto_Product creation has begun, THEN THE Import_System SHALL report the failure to the Admin_User with a descriptive message.
4. WHEN an import fails, THE Import_System SHALL display the failure reason to the Admin_User on the Import_Products_Page.
