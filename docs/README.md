# Bagisto Engineering Documentation Hub

Welcome to the central engineering documentation index for the Bagisto e-commerce platform.

---

## 🗺️ Documentation Directory Map

```text
docs/
├── README.md                   <-- (This Master Index)
├── engineering/
│   ├── change-management.md    <-- Governance & Change Lifecycle Policy
│   ├── service-level-objectives.md <-- System SLO/SLA Metric Targets
│   ├── deployment.md           <-- Production Rollout Procedures
│   └── rollback.md             <-- Incident Rollback Protocols
├── adr/                        <-- Architecture Decision Records
│   └── ADR-001-Payload-Snapshot-Source-of-Truth.md
├── runbooks/                   <-- Operational Runbooks
│   ├── runbook-rebuild-projections.md
│   └── runbook-queue-dead-letters.md
├── rcas/                       <-- Root Cause Analysis Reports
│   └── RCA-002-Missing-Projections.md
└── releases/                   <-- Release Readiness & Audit Reports
    └── v2.4.1.md
```

---

## 📚 Key Sections

### ⚙️ Engineering Policies
- 📖 [Change Management Policy](engineering/change-management.md): Lifecycle, Change Matrix, Merge Criteria, Definition of Done.
- 🎯 [Service Level Objectives (SLO/SLA)](engineering/service-level-objectives.md): Metrics for queue latency, sync failure rates, and incident response SLAs.

### 🏛️ Architecture Decision Records (ADRs)
- 📝 [ADR-001: Payload Snapshot as Source of Truth](adr/ADR-001-Payload-Snapshot-Source-of-Truth.md): Decoupling projection rebuilds from EAV attribute dependencies.

### 🛠️ Operational Runbooks
- 🔧 [Runbook: Projection Rebuild](runbooks/runbook-rebuild-projections.md): Procedures for executing `aliexpress:rebuild-projections`.
- 🔧 [Runbook: Queue Failure Management](runbooks/runbook-queue-dead-letters.md): Guidelines for inspecting and retrying `failed_jobs`.

### 🔍 Root Cause Analyses (RCAs)
- 🐞 [RCA-002: Missing External Variant Projections](rcas/RCA-002-Missing-Projections.md): Analysis and fix for null `variant_id` outbox events.

### 🚀 Release Audits
- 📦 [Release v2.4.1 Audit Report](releases/v2.4.1.md): Summary of Sync Engine Recovery & Stabilization (Phases 1–3).
