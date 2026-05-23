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
