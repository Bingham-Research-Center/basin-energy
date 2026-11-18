# Admin Livewire table contrast fix

In both Light and Dark modes, the Rappasoft Livewire Users and Roles tables
could render light gray striped cells with nearly white inherited text.
This made names, emails, roles and permissions difficult to read.

The fix scopes contrast CSS to a `brc-admin-data-table` wrapper around the
User Management (active, deactivated, deleted) and Role Management Livewire
components. It sets coordinated background and foreground colors for headers,
normal rows, alternating stripes, and hover states under each CoreUI theme.
Buttons, badges, actions, and unrelated tables keep their existing styling.

The stylesheet is `public/css/dashboard-theme.css` and the layout URL uses
`?v=2` to avoid a stale browser cache after deployment. No database changes,
new Node packages, JavaScript changes, or asset build are required.

Before merging, confirm visually in Light, Dark, and Auto on User Management,
Deactivated Users, Deleted Users and Role Management. Check sorting,
pagination, More dropdowns, filters, long text and narrow screens. The
regression tests check that the scoped wrapper and CSS are present, not
pixel-level contrast; browser visual verification remains necessary.
