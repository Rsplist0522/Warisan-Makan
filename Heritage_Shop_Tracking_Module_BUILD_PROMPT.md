# BUILD PROMPT: Heritage Shop Tracking Module (WarisanMakan)

> Paste this whole file to your AI coding assistant (Claude Code, Cursor, etc.) inside the WarisanMakan Laravel repo. It is self-contained — DB schema, models, routes, controllers, validation, views, and API are all specified so the AI can generate working code directly instead of guessing.

## 0. Context for the AI

You are working inside an **existing Laravel project** called **WarisanMakan** — a Malaysian heritage food discovery Progressive Web App (PWA). The project follows **standard Laravel conventions** (Eloquent, Blade, `routes/web.php` + `routes/api.php`, `app/Http/Controllers`, `app/Models`) layered on an **MVC + Layered Architecture**. Assume `User` model + Laravel authentication (with roles: `user`/`tourist` and `admin`) already exist — if a `role` column or `Role` enum doesn't exist yet, add a simple `role` string column (`user`|`admin`) to the `users` table via migration rather than building a full RBAC package.

Your task: implement the **Heritage Shop Tracking Module** end-to-end — database, models, backend logic (web + API), Blade views for both public users and admin, and PWA-friendly behavior (offline cache, graceful degradation) — matching the functional requirements, non-functional requirements, use cases, and user stories below **exactly**. Do not skip alternative/error flows — they are graded requirements, not nice-to-haves.

Deliverable = full stack:
1. Migrations + Models (Eloquent, relationships, casts)
2. Form Requests (validation)
3. Policies/Middleware (RBAC: admin-only for management actions)
4. Web Controllers + Blade views (public shop browsing + admin CRUD)
5. API Controllers + API Resources (JSON endpoints for the PWA frontend)
6. Routes (web + api)
7. Seeders/Factories (sample data for testing)
8. PWA considerations (service worker cache strategy for the shop list, offline fallback)

---

## 1. Functional Requirements (all 18 — implement every one)

### User-facing (10)
| ID | Requirement |
|---|---|
| 2.1.1 | The system shall allow the user to view a paginated list of all published heritage shops. |
| 2.1.2 | The system shall allow the user to search heritage shops by name or keyword. |
| 2.1.3 | The system shall allow the user to filter the heritage shop list by food category, state, or location. |
| 2.1.4 | The system shall allow the user to view a heritage shop's full profile page. |
| 2.1.5 | The system shall allow the user to view the shop's establishment year on its profile page. |
| 2.1.6 | The system shall allow the user to view the shop's founder information (name, background) on its profile page. |
| 2.1.7 | The system shall allow the user to view the shop's current generation owner details on its profile page. |
| 2.1.8 | The system shall allow the user to view the shop's family heritage story on its profile page. |
| 2.1.9 | The system shall allow the user to view the shop's operating hours on its profile page. |
| 2.1.10 | The system shall allow the user to view the list of heritage food items associated with a shop, including item name and description. |

### Administrator-facing (8)
| ID | Requirement |
|---|---|
| 2.2.1 | The system shall allow the administrator to create a new heritage shop record by entering shop name, location, establishment year, founder information, current owner details, heritage story, and operating hours. |
| 2.2.2 | The system shall allow the administrator to update the information of an existing heritage shop record. |
| 2.2.3 | The system shall allow the administrator to delete a heritage shop record from the system. |
| 2.2.4 | The system shall allow the administrator to publish or unpublish a heritage shop listing, controlling its visibility to users. |
| 2.2.5 | The system shall allow the administrator to view a complete list of all heritage shop records, including unpublished/draft entries. |
| 2.2.6 | The system shall allow the administrator to create a new heritage food item and associate it with a specific heritage shop. |
| 2.2.7 | The system shall allow the administrator to update the details (name, description, image) of an existing heritage food item. |
| 2.2.8 | The system shall allow the administrator to delete a heritage food item from a shop's listing. |

Each ID should be traceable to a specific route + controller method — reference the ID in a code comment where it's satisfied (e.g. `// Satisfies FR 2.1.2`) so it's auditable later.

---

## 2. Visual Design System (Heritage Shop Tracking Module scope only)

> **Scope note:** this design system applies **only** to the views this module owns — the public shop list, shop profile, food items pages, and the admin shop/food-item management screens. Do **not** build the multi-section homepage (hero, Food Passport, Food Trail, Blind Box, Community Contribution, footer) — those belong to other modules/teammates. Apply the same color/type tokens to admin screens for consistency, but keep admin layouts utilitarian (data-dense tables/forms), not the full editorial treatment.

### Visual direction
Warm, traditional Malaysian heritage feel — kopitiam interiors, batik linework, spice-market texture — executed with a contemporary, editorial finish. Think "heritage brand refreshed for 2026," not a vintage restaurant menu. Restrained, not ornate.

### Color palette
Add as CSS variables in `resources/css/app.css`:
```css
:root {
  --color-primary: #8C1F1F;      /* deep claypot-chili / peranakan tile red — used deliberately, not everywhere */
  --color-primary-dark: #6E1717; /* hover/active state */
  --color-bg: #F8F1E4;           /* warm cream background, not stark white */
  --color-surface: #FFFDF8;      /* card surfaces, slightly lighter than bg */
  --color-text: #2E2620;         /* charcoal/dark brown body text, not pure black */
  --color-text-muted: #6B5F52;
  --color-accent: #C8891F;       /* warm gold/turmeric — highlights, badges, CTAs */
  --color-accent-light: #E8C77A;
  --color-border: #E4D9C4;
}
```
Mirror these in `tailwind.config.js`:
```js
theme: {
  extend: {
    colors: {
      primary: { DEFAULT: '#8C1F1F', dark: '#6E1717' },
      cream: '#F8F1E4',
      surface: '#FFFDF8',
      ink: { DEFAULT: '#2E2620', muted: '#6B5F52' },
      gold: { DEFAULT: '#C8891F', light: '#E8C77A' },
      border: '#E4D9C4',
    },
    fontFamily: {
      display: ['"Fraunces"', 'serif'],
      sans: ['"Inter"', 'sans-serif'],
    },
  },
}
```
**Usage rule:** primary red is deliberate, not a wash — reserve it for CTAs, active filter chips, key headings/accents, and pull-quote borders. Gold is reserved for badges, highlights, hover states, and a "Verified Heritage Shop" tag. Page/card backgrounds stay on `cream`/`surface`.

### Typography
- **Display/headline:** Fraunces (Google Fonts) — a characterful serif whose bold cuts read almost slab-like, evoking heritage signage without feeling dated. Use for h1–h3, shop names on cards, and the shop profile title. Weights 600–900.
- **Body/UI:** Inter — clean modern sans for paragraphs, labels, buttons, form fields, filters. Weights 400–600.
- Load in the main layout (`resources/views/layouts/app.blade.php`):
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
```
- Type scale: shop profile title `text-4xl md:text-5xl font-display font-bold`; section headings `text-2xl font-display font-semibold`; body `text-base font-sans text-ink`; meta/labels `text-sm font-sans text-ink-muted uppercase tracking-wide`.

### Batik motif — one signature detail, used sparingly
- One subtle repeating SVG line pattern (thin, single-color, ~5–8% opacity), inspired by batik geometry — not a literal batik print.
- Use it in exactly **two** places for this module: (1) a faint background texture behind the shop list header/filter bar, and (2) a thin border/frame treatment on the shop profile's cover-image hero. Do not repeat it inside cards or every section.
- Implement as an inline SVG data-uri utility class `.bg-batik-subtle` and a reusable Blade component `<x-batik-divider />`, not large image assets — keeps page weight low for the NFR 2.1.1–2.1.3 load-time targets.

### Component styling for this module's views

**Shop List (`shops.index`)**
- Header: `bg-cream` with `.bg-batik-subtle`, `font-display` page title, short sans subline.
- Filter bar: pill chips — inactive `bg-surface border border-border text-ink`, active `bg-primary text-cream`. Search input on `surface`, rounded, `focus:ring-gold`.
- Shop cards: `bg-surface rounded-xl border border-border`, photo, `font-display` shop name, small gold-accented category tag, muted-ink location line. Hover: `hover:shadow-md hover:-translate-y-0.5 transition`.
- Empty/no-match states: centered, muted-ink message, primary-colored "Reset filters" link — no stock illustrations.

**Shop Profile (`shops.show`)**
- Hero: cover image framed with the thin batik-line border, `font-display text-4xl` shop name, gold "Est. {year}" badge.
- Story section: `max-w-2xl` measure, generous line-height; founder/current-owner block set apart as a pull-quote with a left `border-primary` rule, not a plain paragraph.
- Operating hours/contact: simple two-column meta block, muted-ink labels, ink values.
- Food items teaser: card grid/scroll leading into the Food Items page, gold price tag, primary "View all food items" CTA.

**Food Items page**
- Cards match shop-card styling for consistency; "heritage significance" text as a secondary/expandable block, not competing with name/price hierarchy.

**Admin views (Shop & Food Item management)**
- Utilitarian: white/`surface` background, standard table + form layout. Carry the color tokens only for status/action elements — e.g. "Published" badge in a soft primary tint, "Draft" in muted gray, "Unpublish" as an outline-primary button. No batik motif, no display font beyond page titles — admin prioritizes speed and density over editorial styling.

### Layout principles (mobile-first PWA)
- Build mobile-first: base styles for ~360–420px, then scale with `md:`/`lg:`.
- Generous spacing — section padding `py-12 md:py-20`, card padding `p-5`.
- One confident visual moment per page: the cover-image hero on the shop profile, the header/filter block on the shop list. No decoration scattered elsewhere.
- Reminder: this is scoped to this module's own pages only — not the app's shared homepage.

---

## 3. Database Schema

### `heritage_shops`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| name | string(150) | required, unique with `location` (composite unique constraint per NFR 2.6.2) |
| slug | string(180) | unique, auto-generated from name |
| description | text | nullable |
| category | string(100) | food category, indexed (used for filtering, FR 2.1.3) |
| state | string(100) | indexed (used for filtering) |
| location | string(255) | address text |
| latitude | decimal(10,7) | nullable |
| longitude | decimal(10,7) | nullable |
| establishment_year | year (smallInteger) | nullable |
| founder_name | string(150) | nullable |
| founder_background | text | nullable |
| current_owner_name | string(150) | nullable |
| current_owner_details | text | nullable |
| heritage_story | text | nullable |
| operating_hours | json | structured `{mon: {open, close}, ..., sun: {open, close}}`; nullable |
| contact_number | string(30) | nullable |
| cover_image_path | string | nullable |
| status | enum('draft','published','unpublished') | default `draft`; drives FR 2.2.4 |
| view_count | unsignedInteger | default 0 |
| created_by | foreignId → users.id | nullable |
| updated_by | foreignId → users.id | nullable |
| deleted_at | softDeletes | shops may need archival, not hard delete, when linked to check-ins (see Alt Flow A3 of Manage Shop Record) |
| timestamps | | |

**Indexes:** unique(`name`,`location`), index(`category`), index(`state`), index(`status`).

### `heritage_shop_images` (photo gallery — supports "photo gallery" in View Shop Profile use case)
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| heritage_shop_id | foreignId → heritage_shops.id | cascade on delete |
| image_path | string | required |
| sort_order | unsignedInteger | default 0 |
| timestamps | | |

### `heritage_food_items`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| heritage_shop_id | foreignId → heritage_shops.id | cascade on delete |
| name | string(150) | required, unique per shop (composite unique with heritage_shop_id) |
| description | text | nullable |
| price | decimal(8,2) | nullable |
| image_path | string | nullable |
| heritage_significance | text | nullable — "heritage significance and story" from View Food Items use case |
| status | enum('published','unpublished') | default `published` |
| created_by | foreignId → users.id | nullable |
| timestamps | | |
| deleted_at | softDeletes | |

**Indexes:** unique(`heritage_shop_id`,`name`).

---

## 4. Eloquent Models

### `App\Models\HeritageShop`
- `use SoftDeletes;`
- Fillable: all schema fields except id/timestamps.
- Casts: `operating_hours => 'array'`, `latitude/longitude => 'decimal:7'`, `establishment_year => 'integer'`.
- Relationships:
  - `foodItems()` → `hasMany(HeritageFoodItem::class)`
  - `images()` → `hasMany(HeritageShopImage::class)->orderBy('sort_order')`
  - `creator()` / `updater()` → `belongsTo(User::class, ...)`
- Scopes:
  - `scopePublished($q)` → `where('status', 'published')`
  - `scopeSearch($q, $term)` → `where('name', 'like', "%$term%")->orWhere('description', 'like', "%$term%")`
  - `scopeFilter($q, array $filters)` → apply `category`, `state` when present
- Accessors: `getIsCompleteAttribute()` → bool, true only if name, location, description, operating_hours, and at least one image are present (used to gate publishing per Alt Flow A5 of Publish/Unpublish Shop).
- Route model binding: use `slug` (`getRouteKeyName()` returns `'slug'`).

### `App\Models\HeritageFoodItem`
- `use SoftDeletes;`
- Fillable: all fields except id/timestamps.
- Casts: `price => 'decimal:2'`.
- Relationships: `shop()` → `belongsTo(HeritageShop::class, 'heritage_shop_id')`.
- Scope: `scopePublished($q)`.

### `App\Models\HeritageShopImage`
- Fillable: `heritage_shop_id`, `image_path`, `sort_order`.
- Relationship: `shop()` → `belongsTo(HeritageShop::class, 'heritage_shop_id')`.

---

## 5. Validation (Form Requests)

### `App\Http\Requests\StoreHeritageShopRequest` / `UpdateHeritageShopRequest`
```php
'name' => ['required','string','max:150', Rule::unique('heritage_shops','name')->where(fn($q) => $q->where('location', $this->location))->ignore($this->route('shop'))],
'location' => ['required','string','max:255'],
'category' => ['required','string','max:100'],
'state' => ['required','string','max:100'],
'description' => ['nullable','string'],
'establishment_year' => ['nullable','integer','min:1800','max:'.date('Y')],
'founder_name' => ['nullable','string','max:150'],
'founder_background' => ['nullable','string'],
'current_owner_name' => ['nullable','string','max:150'],
'current_owner_details' => ['nullable','string'],
'heritage_story' => ['nullable','string'],
'operating_hours' => ['nullable','array'],
'contact_number' => ['nullable','regex:/^\+?[0-9\-\s]{7,20}$/'],
'cover_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'], // 5MB, satisfies Alt Flow A6
'images.*' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
```
On failure: return with `withErrors()->withInput()` (web) so the admin's unsaved input is retained per NFR 2.4.1 — **never** discard the submitted form on validation error.

### `App\Http\Requests\StoreHeritageFoodItemRequest` / `UpdateHeritageFoodItemRequest`
```php
'name' => ['required','string','max:150', Rule::unique('heritage_food_items','name')->where(fn($q) => $q->where('heritage_shop_id', $this->route('shop')->id))->ignore($this->route('foodItem'))],
'description' => ['nullable','string'],
'price' => ['nullable','numeric','min:0'],
'heritage_significance' => ['nullable','string'],
'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
```

All string inputs are auto-escaped by Blade; for API responses, strip tags on free-text fields (`heritage_story`, `founder_background`, etc.) to satisfy NFR 2.5.2 (XSS prevention).

---

## 6. Authorization

Create `App\Policies\HeritageShopPolicy` and `HeritageFoodItemPolicy`:
- `viewAny`, `view` → always `true` (public read), but controller filters unpublished shops for non-admins.
- `create`, `update`, `delete`, `publish` → `return $user->role === 'admin';`

Register both policies in `AuthServiceProvider`. Apply `->middleware(['auth','can:create,App\Models\HeritageShop'])` (or route-level `authorize()` calls) on all admin routes. Unauthorized attempts must return **403 with an "Access Denied" message** (satisfies Alt Flow A4 of Publish/Unpublish Shop) — do not silently redirect.

---

## 7. Routes

### `routes/web.php`
```php
// Public
Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shops/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');
Route::get('/shops/{shop:slug}/food-items', [ShopController::class, 'foodItems'])->name('shops.food-items');
Route::get('/shops/{shop:slug}/food-items/{foodItem}', [ShopController::class, 'foodItemShow'])->name('shops.food-items.show');

// Admin (prefix + middleware)
Route::middleware(['auth','can:create,App\Models\HeritageShop'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('shops', Admin\ShopManagementController::class)->except(['show']);
    Route::patch('shops/{shop}/publish', [Admin\ShopManagementController::class, 'togglePublish'])->name('shops.publish');
    Route::resource('shops.food-items', Admin\FoodItemManagementController::class)->shallow()->except(['show']);
});
```

### `routes/api.php` (v1, for the PWA frontend / offline sync)
```php
Route::prefix('v1')->group(function () {
    Route::get('/shops', [Api\ShopController::class, 'index']);
    Route::get('/shops/{shop:slug}', [Api\ShopController::class, 'show']);
    Route::get('/shops/{shop:slug}/food-items', [Api\ShopController::class, 'foodItems']);
    Route::get('/shops/{shop:slug}/food-items/{foodItem}', [Api\ShopController::class, 'foodItemShow']);

    Route::middleware(['auth:sanctum','can:create,App\Models\HeritageShop'])->group(function () {
        Route::apiResource('admin/shops', Api\Admin\ShopController::class);
        Route::patch('admin/shops/{shop}/publish', [Api\Admin\ShopController::class, 'togglePublish']);
        Route::apiResource('admin/shops.food-items', Api\Admin\FoodItemController::class)->shallow();
    });
});
```

---

## 8. Controller Logic (map 1:1 to the use cases)

### `ShopController@index` (public — implements *View Shop List* use case)
- Query: `HeritageShop::published()->when($request->search, fn($q,$s)=>$q->search($s))->filter($request->only(['category','state']))->paginate(20)`.
- If DB/query throws → catch, log, return view with `$error = true` and a retry link (Alt Flow A2).
- If result is empty and no filters applied → pass `$noShopsYet = true` → view shows "No shops available at the moment."
- If result is empty and filters/search applied → pass `$noMatch = true` → view shows "No matching shops found."
- Support `?page=` for pagination (AJAX partial for infinite-scroll "load more" on the PWA, returning a Blade partial or JSON fragment).

### `ShopController@show` (public — *View Shop Profile*)
- Resolve by slug; if shop exists but `status !== 'published'` and current user isn't admin → 404 with a **"This shop is no longer available"** flash message, redirect to `shops.index` (Alt Flow A1/A3).
- Eager-load `images`, `foodItems` (published only).
- Increment `view_count` (non-blocking, e.g. queued job or simple increment — don't block page render).
- If `latitude`/`longitude` are null → view renders address as text only, no map (Alt Flow A7).

### `ShopController@foodItems` / `foodItemShow` (public — *View Food Items*)
- If shop has zero published food items → show **"No food items available for this shop."**
- If requested food item is unpublished/deleted → **"This item is no longer available"**, redirect back to food item list.

### `Admin\ShopManagementController` (*Manage Shop Record*)
- `index` → paginated list of **all** shops regardless of status (FR 2.2.5), with status badges.
- `store`/`update` → validate via Form Request; on success, `flash('success', 'Shop saved.')`; on validation failure, Laravel automatically retains old input — confirm this behavior in the Blade form via `old()`.
- `destroy` →
  - If shop has related `foodItems` → confirm dialog first (client-side JS `confirm()` or a modal), warn items will be removed too.
  - If shop has related check-ins/badges (only relevant once those modules exist — leave a `// TODO: check food_passport_checkins before hard delete` comment) → soft-delete (`$shop->delete()`) instead of `forceDelete()`.
  - Wrap in `DB::transaction()`; on failure, catch and `return back()->withInput()->with('error', 'Save failed, please retry.')` (Alt Flow A10).

### `Admin\ShopManagementController@togglePublish` (*Publish/Unpublish Shop*)
- `$this->authorize('publish', $shop);`
- If `$shop->status === $target` already → flash "No change needed," redirect back (Alt Flow A1).
- If publishing and `!$shop->is_complete` → reject with **"Please complete the shop profile before publishing"** (Alt Flow A5).
- Else update status, log the action (`activity()->log(...)` or a simple `audit_logs` insert — see §10), flash success.

### `Admin\FoodItemManagementController` (*Manage Food Item*)
- Nested under shop (`shops/{shop}/food-items`), mirrors `ShopManagementController` logic: confirm-before-delete, duplicate-name rejection, validation-retains-input, DB-transaction-wrapped save.

### API controllers (`Api\ShopController`, `Api\Admin\...`)
- Same query/authorization logic as web controllers, but return `HeritageShopResource` / `HeritageFoodItemResource` (API Resources) instead of Blade views, with standard envelope:
```json
{ "data": [...], "meta": { "current_page":1, "last_page":5, "total":93 } }
```
- On error, return proper HTTP codes: `404` (not found/unpublished), `409` (duplicate name), `422` (validation), `403` (unauthorized), `500` (server error) — the PWA frontend needs these to distinguish "retry" vs "show message" states.

---

## 9. API Resources

`App\Http\Resources\HeritageShopResource`:
```php
return [
    'id' => $this->id,
    'slug' => $this->slug,
    'name' => $this->name,
    'thumbnail' => $this->cover_image_path ? Storage::url($this->cover_image_path) : null,
    'category' => $this->category,
    'state' => $this->state,
    'location' => $this->location,
    'establishment_year' => $this->establishment_year,
    'founder' => $this->when($request->routeIs('*.show'), [
        'name' => $this->founder_name, 'background' => $this->founder_background,
    ]),
    'current_owner' => $this->when($request->routeIs('*.show'), [
        'name' => $this->current_owner_name, 'details' => $this->current_owner_details,
    ]),
    'heritage_story' => $this->when($request->routeIs('*.show'), $this->heritage_story),
    'operating_hours' => $this->operating_hours,
    'coordinates' => $this->latitude && $this->longitude ? ['lat'=>$this->latitude,'lng'=>$this->longitude] : null,
    'gallery' => $this->when($request->routeIs('*.show'), HeritageShopImageResource::collection($this->whenLoaded('images'))),
    'status' => $this->when($request->user()?->role === 'admin', $this->status),
];
```
(List endpoint stays lightweight — full bio fields only on the detail/`show` endpoint, to hit NFR 2.1.1's 2-second load target.)

`App\Http\Resources\HeritageFoodItemResource` — `id, name, description, price, image, heritage_significance` (significance only on detail view, same pattern).

---

## 10. Audit Logging (NFR: "action is logged for audit purposes")

Simplest compliant approach — a lightweight `audit_logs` table:
```php
Schema::create('audit_logs', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained();
    $t->string('action'); // e.g. 'shop.created', 'shop.published'
    $t->string('subject_type');
    $t->unsignedBigInteger('subject_id');
    $t->json('changes')->nullable();
    $t->timestamps();
});
```
Log on every create/update/delete/publish/unpublish in the admin controllers. (If `spatie/laravel-activitylog` is already installed in the repo, use that instead — check `composer.json` first.)

---

## 11. PWA / Offline Behavior

- **Shop list caching:** register `/shops` (and its paginated API responses) in the service worker with a **stale-while-revalidate** strategy so a previously loaded list still renders offline (Alt Flow A1 of View Shop List: "displays offline notice, showing a locally cached list if one exists").
- **Offline banner:** a small JS check (`navigator.onLine` + `online`/`offline` event listeners) toggles a persistent "You're offline — showing saved data" banner on the shop list and profile pages.
- **Image fallback:** every `<img>` for shop/food thumbnails gets `onerror` swapping to a local placeholder asset (Alt Flow A5/A3 broken-image handling) — implement as a small reusable Blade component `<x-shop-image>`.
- **Retry buttons:** any fetch failure (list, profile, food items) shows a "Retry" button that re-triggers the same request rather than a full page reload, to preserve scroll position/pagination state.

---

## 12. Seeders

`HeritageShopSeeder` — create ~15 shops (mix of `published`/`draft`/`unpublished`) across at least 4 states and 4 categories using a factory, each with 3–5 food items and 2–4 gallery images, so pagination, filtering, and empty-state paths are all testable out of the box.

---

## 13. Acceptance Checklist (the AI should self-verify against this before considering the module done)

- [ ] All 18 functional requirements (2.1.1–2.1.10, 2.2.1–2.2.8) have a corresponding, working code path.
- [ ] All 6 use cases' **main flows** are implemented.
- [ ] Every listed **alternative flow** (offline, error, empty-state, duplicate, validation, permission, conflict, timeout) has explicit handling — not just the happy path.
- [ ] Admin-only actions are blocked for non-admins with a 403/"Access Denied," not a silent failure.
- [ ] Validation errors retain the admin's input (no data re-typing).
- [ ] Duplicate shop name+location and duplicate food item name (per shop) are rejected.
- [ ] Deleting a shop with related food items warns first; deleting a shop with history soft-deletes instead of hard-deletes.
- [ ] Publishing is blocked if the shop profile is incomplete.
- [ ] Shop list responds within the 2-second budget for 20 records (use eager loading, indexed columns, and pagination — avoid N+1 queries; run `php artisan route:list` and check with `DB::enableQueryLog()` in dev).
- [ ] All admin-writable text fields are sanitized/escaped.
- [ ] Works responsively from 360px to 1920px (test the Blade views, not just the API).
- [ ] Migrations run cleanly (`php artisan migrate:fresh --seed`) and seeders populate a realistic dataset.
- [ ] Public views (shop list, shop profile, food items) use the color tokens, Fraunces/Inter fonts, and batik motif exactly as scoped in §2 — restrained, not decorated throughout.
- [ ] Admin views use the same color tokens for status/action elements only, without the editorial/batik treatment.
- [ ] No homepage sections outside this module's scope (hero, Food Passport, Blind Box, etc.) were built.

---

## 14. Reference Data (for context — do not duplicate elsewhere)

**User stories this module must satisfy (18 total, 47 story points):** US001–US010 (user-facing: list, search, filter by category/state, profile details, heritage story, operating hours, food items) and US011–US018 (admin: create/update/delete/publish shop, view all shops, add/update/delete food items).