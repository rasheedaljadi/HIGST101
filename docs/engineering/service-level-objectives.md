# Service Level Objectives (SLOs) & Metric Targets

## 🎯 Target Matrix

| Metric | Target / SLA | Threshold for Escalation |
|--------|--------------|--------------------------|
| **Sync Failure Rate** | 0 unhandled fatal crashes | $>0\%$ fatal errors in `storage/logs/laravel.log` |
| **Dead-letter Queue** | 0 unexpected failures | $\ge 5$ jobs in `failed_jobs` within 15 mins |
| **Queue Wait Latency** | $< 5$ seconds average wait time | $> 60$ seconds max pending job age |
| **Outbox Event Processing** | 100% processed (`status = processed`) | $> 0$ pending events after outbox run |
