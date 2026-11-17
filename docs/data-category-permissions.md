# Data-category permissions (BasinEnergy.info)

Administrator-managed User roles are named manually, e.g. Student, Researcher,
Stakeholder, or any other appropriate name. The role name identifies its audience;
data access is controlled through independently assigned permissions.

Under **Role Management -> Create / Edit Role -> Type: User**, the
**Data Categories** section contains these selectable permissions:

| Label | Permission key |
| --- | --- |
| Public | `user.data.public` |
| Research only | `user.data.research` |
| Classified | `user.data.classified` |
| Industrial | `user.data.industrial` |
| Real-time | `user.data.realtime` |
| Weather | `user.data.weather` |

The **Data Categories** heading (`user.data`) is not a selectable permission.
A User account can have multiple administrator-defined User roles; Administrator
roles and permissions remain separate. New self-registered users do not receive
an audience role automatically.

## Deploy safely

The existing `roles`, `permissions` and Spatie pivot tables already support
this feature. No new database columns or tables are needed. On an existing
BasinEnergy installation, after deploying code, run:

```bash
php artisan migrate --path=database/migrations/2026_11_18_000000_replace_user_audience_permissions_with_data_categories.php --force
php artisan permission:cache-reset
```

This targeted data migration is repeatable and preserves existing role records,
user accounts and memberships. If obsolete `user.audience.*` permissions exist,
the migration retires the six original audience permissions, and removes the
old group only when it has no remaining children. It does not delete roles,
including any manually created audience-named roles.

**Do not run `php artisan db:seed` against a populated BasinEnergy database.**
The existing `AuthSeeder` truncates user, role and permission tables. The
`DataCategorySeeder` is safe to run separately when needed.

## Security boundary

Permission assignment alone does **not** protect datasets. Before exposing
restricted datasets, implement and test backend route/API authorization,
record-level filtering and access policies that enforce these permission keys.
Do not equate a UI-only category with confidentiality controls. Public data
can remain freely accessible where the application explicitly intends it.
