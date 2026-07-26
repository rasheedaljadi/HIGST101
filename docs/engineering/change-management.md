# Engineering Change Management Policy & Governance Framework

## 📌 Overview
This document defines the mandatory engineering change management process for synchronization, fulfillment, and critical domain components within the Bagisto repository. Its objective is to guarantee predictable, deterministic, and fully auditable code updates across all environments.

---

## 🏗️ Repository Documentation Structure

```text
docs/
├── engineering/
│   ├── change-management.md    <-- (This Policy Document)
│   ├── architecture.md         <-- System Architecture Boundaries
│   ├── deployment.md           <-- Sequential Deployment Procedures
│   ├── rollback.md             <-- Incident Rollback Protocols
│   ├── monitoring.md           <-- Post-Deployment Metric SLAs
│   └── incident-response.md    <-- Outage & Failover Guidelines
├── rcas/                       <-- Root Cause Analysis Forensic Reports
│   ├── RCA-001.md
│   ├── RCA-002.md
│   └── ...
└── releases/                   <-- Release Readiness Audit Reports & Logs
    ├── v2.4.1.md
    ├── v2.4.2.md
    └── ...
```

---

## 📊 Change Classification Matrix

| Change Type | Impact & Examples | Mandatory Requirements & Governance |
|-------------|-------------------|--------------------------------------|
| **Patch** | Bug fixes, minor logic corrections, hotfixes | Code review, unit/integration test coverage, Pint formatting |
| **Minor** | New features, secondary API integrations | RFC/Plan, integration tests, Release Readiness Review, Staged Deployment |
| **Major** | Architectural refactoring, breaking schema changes | Formal RFC, Architecture Review, extensive Rollback & Failover Plan |

---

## 🔁 Standard Change Lifecycle

```
┌───────┐     ┌─────────────────────┐     ┌─────────────┐     ┌───────────────────┐
│  RCA  │ ──> │ Implementation Plan │ ──> │ Development │ ──> │ Integration Tests │
└───────┘     └─────────────────────┘     └─────────────┘     └───────────────────┘
                                                                        │
┌────────────────────────┐     ┌──────────────┐     ┌───────────────────┴──┐
│ Post-Deployment Review │ <── │ Staged Deploy│ <── │ Pull Request Review  │
└────────────────────────┘     └──────────────┘     └──────────────────────┘
```

1. **Root Cause Analysis (RCA)**: Perform a data-driven, empirical investigation to isolate the primary defect before writing code.
2. **Implementation Plan**: Define a scoped implementation plan documenting in-scope changes, out-of-scope boundaries, and risk assessments.
3. **Development Scope**: Execute code modifications exclusively in isolated development branches or local working trees.
4. **Integration Testing**: Implement repeatable integration tests proving expected runtime behaviors and edge-case resilience.
5. **Release Readiness Review**: Conduct a pre-deployment audit (`git diff --stat`, test suite validation, deployment sequence, rollback plan).
6. **Pull Request Review**: Code review focusing on regression risks, concurrency safety, edge-case coverage, and performance SLAs.
7. **Staged Deployment**: Perform step-by-step production rollout (Backup → Deploy → Migration/Rebuild → Worker Restart → Smoke Test → Monitoring).
8. **Post-Deployment Review**: Verify post-deployment metrics (`failed_jobs`, latency, outbox processing) and document lessons learned.

---

## ✅ Mandatory Merge Criteria Checklist

Before merging any Pull Request into `main`, the following criteria MUST be verified:

- [ ] **Test Coverage**: All unit, integration, and feature test suites pass (100% PASS).
- [ ] **Cleanliness**: 0 debug statements (`dd()`, `dump()`, `var_dump()`), 0 temporary TODOs, 0 scratch files in Git history.
- [ ] **Security**: 0 hardcoded secrets, API keys, or credentials in commits.
- [ ] **Rollback**: Explicit rollback steps and tag checkpoints documented.
- [ ] **Documentation**: System architecture or operational docs updated if behavior changed.

---

## 🏷️ Release Tagging & Changelog Policy

1. **Semantic Version Tags**: Every production deployment MUST create an annotated Git tag (e.g., `git tag -a v2.4.1 -m "Release v2.4.1: Sync engine stabilization"`).
2. **CHANGELOG Linkage**: Every release entry in `docs/releases/` must link directly to the corresponding PR and RCA documents.
3. **Traceability**: All production hotfixes must be traceable to a specific Git commit and tag.

---

## ⏱️ Post-Deployment Monitoring & Decision Ownership

1. **Monitoring Window**: A mandatory **30–60 minute post-deployment monitoring window** must be observed following any production deployment.
2. **Monitored Metrics**:
   - Application error logs (`storage/logs/laravel.log`) for unhandled fatal crashes.
   - Dead-letter queue count (`failed_jobs` table).
   - Oldest pending queue job age (`jobs` table latency).
   - Outbox processing status (`domain_outbox_events` table).
3. **Rollback Decision Owner**: The **Lead Software Architect** holds sole authority and responsibility for triggering a rollback if error thresholds are exceeded during the monitoring window.

---

## 🔄 Lessons Learned & Continuous Improvement

Following the post-deployment monitoring window, the team must complete a brief **Post-Deployment Review**:
- **What went as expected?** (Features, performance, stability).
- **What could be improved?** (Deployment speed, test coverage gaps).
- **Policy Updates Required?** (Adjust change management rules if new risks were identified).

---

## 📜 Non-Negotiable Governance Rules

1. **Zero Direct Production Patching**: All code modifications must be built, formatted (`pint`), syntax-checked (`php -l`), and tested in local Development environments before pushing.
2. **Independent Plans for RCAs**: Every identified root cause must have a separate, scoped implementation plan to prevent scope creep.
3. **Mandatory Integration Tests**: Any change affecting queue architecture, state machines, or synchronization lifecycles must include automated integration tests.
4. **Rollback Procedures Required**: Critical path fixes must specify an explicit rollback trigger and tag/release rollback commands (`git checkout <tag>`).
5. **Zero Merge Without Test Passing**: PRs cannot be merged without 100% passage of relevant test suites and style checks.
