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
