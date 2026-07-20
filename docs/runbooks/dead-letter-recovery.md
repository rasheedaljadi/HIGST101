# Runbook: Dead Letter Recovery

## Overview
Sessions failing after exhausting all retries are logged into `procurement_dead_letters`.

## Steps
1. Query `procurement_dead_letters` to identify failure reasons.
2. If failure was due to price mismatch, get operator approval.
3. If failure was transient, clear the error and run retry.
