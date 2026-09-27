-- Sends a small Data API heartbeat. Run npm run db:keepalive to provision from env.
-- Required Vault secrets:
--   si_praktikum_supabase_url
--   si_praktikum_supabase_publishable_key
--   si_praktikum_keepalive_cron_utc

create extension if not exists pg_cron;
create extension if not exists pg_net with schema extensions;

create table if not exists public.app_heartbeats (
  name text primary key,
  last_seen_at timestamptz not null default pg_catalog.now(),
  constraint app_heartbeats_single_name check (name = 'application')
);

alter table public.app_heartbeats enable row level security;
revoke all on table public.app_heartbeats from anon, authenticated;

create or replace function public.keepalive()
returns jsonb
language sql
security definer
set search_path = ''
as $$
  insert into public.app_heartbeats (name, last_seen_at)
  values ('application', pg_catalog.now())
  on conflict (name) do update set last_seen_at = excluded.last_seen_at
  returning pg_catalog.jsonb_build_object('ok', true);
$$;

revoke all on function public.keepalive() from public;
grant execute on function public.keepalive() to anon;

select cron.unschedule(jobid)
from cron.job
where jobname in ('si-praktikum-hourly-keepalive', 'si-praktikum-daily-keepalive');

select cron.schedule(
  'si-praktikum-daily-keepalive',
  (select decrypted_secret from vault.decrypted_secrets where name = 'si_praktikum_keepalive_cron_utc'),
  $job$
    select net.http_post(
      url := (
        select rtrim(decrypted_secret, '/') || '/rest/v1/rpc/keepalive'
        from vault.decrypted_secrets
        where name = 'si_praktikum_supabase_url'
      ),
      headers := jsonb_build_object(
        'Content-Type', 'application/json',
        'apikey', (
          select decrypted_secret from vault.decrypted_secrets
          where name = 'si_praktikum_supabase_publishable_key'
        )
      ),
      body := '{}'::jsonb,
      timeout_milliseconds := 10000
    );
  $job$
);
