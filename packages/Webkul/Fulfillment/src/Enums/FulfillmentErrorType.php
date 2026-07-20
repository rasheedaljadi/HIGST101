<?php

namespace Webkul\Fulfillment\Enums;

enum FulfillmentErrorType: string
{
    case PROVIDER_ERROR      = 'provider_error';
    case NETWORK_ERROR       = 'network_error';
    case AUTH_ERROR          = 'auth_error';
    case VALIDATION_ERROR    = 'validation_error';
    case BUSINESS_RULE_ERROR = 'business_rule_error';
    case MANUAL_REVIEW       = 'manual_review';
}
