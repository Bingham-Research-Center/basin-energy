# HTMLPurifier cache permissions in production

## Symptom

`POST /admin/broadcast/editor/autosave/{kind}` returns HTTP 500:
`Directory /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer not writable`.

## Root cause and fix

HTMLPurifier uses its own vendor-directory cache path by default. Installing
Composer dependencies as root (or deploying a read-only vendor directory)
means the PHP web process cannot write cache files there. Laravel converts
HTMLPurifier's permission warning into an exception when saving rich text.

`App\\Services\\BlogHtmlSanitizer` now explicitly sets
`Cache.SerializerPath` to
`storage/framework/cache/htmlpurifier`. On first use, the application
creates that directory and checks writability. HTMLPurifier retains its
normal serialized definition cache; sanitization is **not** disabled.
This shared sanitizer serves Blog and Broadcast content.

## Production deployment

No schema migration, Composer update, or asset build is required.

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Ensure `/var/www/html/storage/framework/cache` is writable by the
**PHP web process** inside the Docker container and persists according
to your deployment setup. For diagnostics:

```bash
docker exec -it brc_site ls -ld /var/www/html/storage /var/www/html/storage/framework/cache
```

If you still receive a permission error, fix the ownership/permissions
of **Laravel storage** for the actual web process and Docker mount.
Do **not** make `vendor/` writable and do not use recursive `chmod 777`.

Once deployed, open an Announcement or Newsletter draft and edit its
rich text. The autosave status should become `Saved automatically`.
Restore any unsaved browser-local draft through the editor's recovery
prompt and confirm it saves to the server before clearing browser data.

## Regression checks

`tests/Feature/Broadcast/BroadcastPurifierCacheTest.php` confirms that
HTMLPurifier definitions are cached in Laravel storage, sanitization
still removes script tags, and Broadcast rich text continues working.
