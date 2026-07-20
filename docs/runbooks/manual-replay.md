# Runbook: Manual Event Replay

## Overview
Allows operations to manually trigger reprocessing of webhook payloads or state replays.

## Steps
1. Locate the event ID inside `procurement_inbox_events`.
2. Clear the event status back to 'pending'.
3. Trigger the replay utility via terminal or Admin dashboard.
