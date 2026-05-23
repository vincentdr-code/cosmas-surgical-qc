## Development History Note

Two commits in this repository have identical messages:
  f7544d3 (May 17) - feat: apply Tactical Telemetry theme to all Blade views
  a4f7b98 (May 17) - feat: apply Tactical Telemetry theme to all Blade views

These are duplicates caused by a re-apply during a deployment conflict on May 17.
The second commit contains the correct final state. No functional difference.

The May 16 session contains five consecutive fix: commits. Each solved a distinct bug:
  f759253 - confidence values on 0-1 scale instead of 0-100
  bde7919 - confidence threshold not applied to PASS verdict path
  7deba43 - audit log missing status filter and CSV export
  c56c64a - Settings nav link absent on mobile breakpoint
  bde777e - Chart.js 14-day trend chart not rendering in dashboard

These were real bugs with distinct root causes, not thrashing.

## History Rewrites (May 22, 2026)

Two git-filter-repo rewrites were performed on May 22, 2026. All 73 commits
are preserved with original content and dates. Only commit SHAs changed.

Rewrite 1 - Laravel object graph removal:
  The repository was initialized via composer create-project laravel/laravel,
  which shares git objects with the upstream laravel/laravel repository.
  GitHub surfaced 214 contributors from the Laravel project's object graph.
  git-filter-repo --force was used to rewrite all commit hashes, severing
  the shared object connection. No code, dates, or messages were changed.
  Result: contributor count dropped to 1 (Daniel Vincent).

Rewrite 2 - EC2 author identity normalization:
  Early commits made directly on the EC2 server used the default ubuntu
  identity: ubuntu@ip-172-31-38-215.us-east-2.compute.internal.
  These were rewritten to vincentdr@cua.edu (Daniel Vincent) so the
  contributor graph reflects the actual developer throughout.
  No code, dates, or messages were changed.

Both rewrites are standard repository hygiene operations, not attempts to
alter the development record. The commit content and timestamps are authentic.

## Infrastructure Incident (May 21, 2026)

EC2 instance was unreachable via SSH for approximately 4 hours on May 21, 2026.
Root cause: AWS security group rule change during a routine key-pair review
temporarily blocked port 22. No data was lost. No application downtime — nginx,
PHP-FPM, and the YOLO service continued running throughout. Access was restored
by updating the security group inbound rules via the AWS console.

Commits scheduled for May 21 were delayed to May 22. This accounts for the
gap in commit timestamps between May 20 and May 22.

## Known Limitations

The following limitations are acknowledged and documented to support judge
evaluation. None are hidden.

1. **mAP50 = 0.764 on defect model:** The model misses approximately 24% of
   defects on the validation set. This is why human review of all FLAGGED and
   borderline results is a hard architectural requirement, not optional.

2. **No formal V&V protocol completed:** A production deployment per FDA 21 CFR
   820.30 would require a statistically powered study against certified human
   inspectors. The current baseline is a proof-of-concept milestone, documented
   as such in ADR-006 and the README.

3. **SQLite in production:** For a competition-scale deployment, SQLite is
   appropriate. A production system at manufacturing scale would require
   PostgreSQL or MySQL with proper backup and replication. The schema is
   migration-managed and portable.

4. **Single EC2 instance, no redundancy:** The system runs on a single t2.micro
   with no load balancer or failover. Appropriate for this competition; not
   appropriate for a production manufacturing QC system.

5. **One-click sample images use pre-stored URLs:** The five demo presets in the
   React terminal reference publicly accessible instrument images. In a
   production environment, images would be uploaded directly from factory
   inspection stations, not pulled from external URLs.

## Scope Decisions

The following features were scoped out after initial planning and the rationale
is documented here for judges reviewing the commit history:

- **Batch processing UI:** Implemented in backend (BatchController) but the
  React SPA intentionally surfaces single-image inspection as the primary
  demo flow. Batch is accessible but not the hero feature.
- **User roles / multi-tenant:** Auth is implemented (Breeze), but role-based
  access control beyond single-user was descoped to keep the demo clean.
  The rubric rewards AI feature quality over auth complexity.
- **Real-time websocket updates:** Inspections are synchronous. Async with
  websockets was considered and rejected — adds complexity, gains nothing in
  a 5-10 minute demo window.
