# ADR-002: SQLite over PostgreSQL/MySQL

**Date:** May 14, 2026  **Status:** Implemented

## Decision
Use SQLite for the production database on EC2 t2.micro.

## Context
EC2 t2.micro: 1 vCPU, 1GB RAM. Running: nginx + php8.5-fpm + uvicorn (YOLO).

## Options Considered
Option A: RDS MySQL - ~25/month, outside Free Tier. Rejected.

Option B: MySQL on EC2
  On May 14, mysqld was OOM-killed during the first YOLO inference run.
  nginx + php-fpm + uvicorn + mysqld exceeded 1GB RAM. Experienced directly.

Option C (chosen): SQLite
  Zero RAM overhead. Laravel PDO supports it natively.
  Sub-10ms queries with proper indexes. No service to manage.
  File at /home/ubuntu/cosmas/database/database.sqlite

## Consequences
Not suitable for multi-server deployments. Correct for single-EC2 demo.
