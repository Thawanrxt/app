-- =========================================================
-- PostgreSQL Safe Baseline Script
-- Project: admin-panel
-- =========================================================
--
-- How to use:
-- 1. Open this file in pgAdmin4.
-- 2. Run the whole script against the target database.
-- 3. Run `php artisan migrate:status` again.
--
-- Goal:
-- - Mark only the migrations that are already represented by the live schema.
-- - Leave risky / mismatched migrations as Pending on purpose.
--
-- Important:
-- - This script is idempotent. Running it more than once is safe.
-- - It does NOT create tables or columns. It only inserts rows into
--   `public.migrations` when the required schema is already present.


-- =========================================================
-- 0) MIGRATIONS WE INTENTIONALLY DO NOT BASELINE
-- =========================================================
--
-- We do not mark these automatically because the live schema does not fully
-- match the migration, or the project now uses a different replacement table.
--
-- 0001_01_01_000000_create_users_table
-- 0001_01_01_000001_create_cache_table
-- 0001_01_01_000002_create_jobs_table
-- 2026_03_24_170000_add_admin_fields_to_users_table
-- 2026_03_24_180000_create_tracking_advices_table
-- 2026_03_24_190000_create_dashboard_work_items_table
-- 2026_03_29_170000_create_water_tracking_activities_table
-- 2026_03_29_171000_create_disease_tracking_activities_table
-- 2026_03_29_172000_create_pest_tracking_activities_table
-- 2026_03_29_173000_create_prep_tracking_activities_table
-- 2026_03_29_174000_create_fertilizer_tracking_activities_table
-- 2026_03_29_175000_create_harvest_tracking_activities_table
-- 2026_03_29_176000_create_mill_tracking_activities_table
-- 2026_03_31_120000_trim_app_settings_table_to_active_fields
--
-- Reason:
-- - `users` in the live DB is not the same shape as Laravel default.
-- - old `tracking_advices` / `dashboard_work_items` migrations were replaced
--   by newer admin support tables.
-- - `*_tracking_activities` tables do not exist in the live DB.
-- - `trim_app_settings` is data-shape sensitive and should stay manual.


-- =========================================================
-- 1) SAFE AUTO-BASELINE
-- =========================================================

-- create_rice_varieties_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_24_160000_create_rice_varieties_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'rice_varieties'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_24_160000_create_rice_varieties_table'
);


-- create_app_settings_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_24_171000_create_app_settings_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'app_settings'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_24_171000_create_app_settings_table'
);


-- add_admin_fields_to_rice_varieties_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_29_200000_add_admin_fields_to_rice_varieties_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'rice_varieties'
      AND column_name = 'rice_type'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'rice_varieties'
      AND column_name = 'standard_duration_days'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'rice_varieties'
      AND column_name = 'disease_resistance'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'rice_varieties'
      AND column_name = 'pest_resistances'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_29_200000_add_admin_fields_to_rice_varieties_table'
);


-- add_admin_review_fields_to_activity_events_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_30_120000_add_admin_review_fields_to_activity_events_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'activity_events'
      AND column_name = 'reviewed_by'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'activity_events'
      AND column_name = 'reviewed_at'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'activity_events'
      AND column_name = 'admin_note'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_30_120000_add_admin_review_fields_to_activity_events_table'
);


-- create_api_access_tokens_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_31_140000_create_api_access_tokens_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'api_access_tokens'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_31_140000_create_api_access_tokens_table'
);


-- add_device_fields_to_api_access_tokens_table
INSERT INTO public.migrations (migration, batch)
SELECT '2026_03_31_141000_add_device_fields_to_api_access_tokens_table', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'api_access_tokens'
      AND column_name = 'device_id'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'api_access_tokens'
      AND column_name = 'platform'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'api_access_tokens'
      AND column_name = 'revoked_at'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_03_31_141000_add_device_fields_to_api_access_tokens_table'
);


-- create_admin_support_tables
INSERT INTO public.migrations (migration, batch)
SELECT '2026_04_21_080621_create_admin_support_tables', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'dashboard_work_items'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'tracking_advices'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'admin_activity_logs'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_04_21_080621_create_admin_support_tables'
);


-- add_missing_admin_columns
INSERT INTO public.migrations (migration, batch)
SELECT '2026_04_21_080639_add_missing_admin_columns', 1
WHERE EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'support_tickets'
      AND column_name = 'assigned_to'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'support_tickets'
      AND column_name = 'admin_note'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'support_tickets'
      AND column_name = 'resolved_at'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'farmer_profiles'
      AND column_name = 'subdistrict'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'farmer_profiles'
      AND column_name = 'postcode'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'plots'
      AND column_name = 'subdistrict'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'plots'
      AND column_name = 'postcode'
)
AND EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'plots'
      AND column_name = 'is_primary'
)
AND NOT EXISTS (
    SELECT 1
    FROM public.migrations
    WHERE migration = '2026_04_21_080639_add_missing_admin_columns'
);


-- =========================================================
-- 2) VERIFICATION: BASELINED MIGRATIONS
-- =========================================================
SELECT id, migration, batch
FROM public.migrations
WHERE migration IN (
    '2026_03_24_160000_create_rice_varieties_table',
    '2026_03_24_171000_create_app_settings_table',
    '2026_03_29_200000_add_admin_fields_to_rice_varieties_table',
    '2026_03_30_120000_add_admin_review_fields_to_activity_events_table',
    '2026_03_31_140000_create_api_access_tokens_table',
    '2026_03_31_141000_add_device_fields_to_api_access_tokens_table',
    '2026_04_21_080621_create_admin_support_tables',
    '2026_04_21_080639_add_missing_admin_columns'
)
ORDER BY id;


-- =========================================================
-- 3) VERIFICATION: REMAINING PENDING CANDIDATES (MANUAL)
-- =========================================================
-- Run this after `php artisan migrate:status` if you want to compare.
-- These are the ones expected to remain Pending for now:
--
-- 0001_01_01_000000_create_users_table
-- 0001_01_01_000001_create_cache_table
-- 0001_01_01_000002_create_jobs_table
-- 2026_03_24_170000_add_admin_fields_to_users_table
-- 2026_03_24_180000_create_tracking_advices_table
-- 2026_03_24_190000_create_dashboard_work_items_table
-- 2026_03_29_170000_create_water_tracking_activities_table
-- 2026_03_29_171000_create_disease_tracking_activities_table
-- 2026_03_29_172000_create_pest_tracking_activities_table
-- 2026_03_29_173000_create_prep_tracking_activities_table
-- 2026_03_29_174000_create_fertilizer_tracking_activities_table
-- 2026_03_29_175000_create_harvest_tracking_activities_table
-- 2026_03_29_176000_create_mill_tracking_activities_table
-- 2026_03_31_120000_trim_app_settings_table_to_active_fields
