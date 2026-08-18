# WarisanMakan – Current Integrated Database Structure for Codex

## 1. Purpose

This document is the **current agreed database baseline** for the WarisanMakan Laravel project.

Codex should use this file as the main reference before changing migrations, models, relationships, controllers, or database-related code.

### Important rules

1. **Do not rebuild or drop the Aiven database unless explicitly requested.**
2. All database changes must be implemented through **Laravel migrations**.
3. Do not re-introduce duplicate heritage shop columns that were previously removed.
4. Images and videos are stored in **Cloudflare R2**, not directly inside MySQL.
5. MySQL should store only the Cloudflare R2 object key/path and media metadata.
6. Do not create speculative tables for teammates' modules unless their implementation actually requires them.
7. Existing teammate tables should not be deleted unless confirmed unused.
8. The previous standalone **Heritage Food Story Contribution** feature is no longer part of the current Community Contribution database plan.
9. Keep naming consistent across migrations, Eloquent models, validation rules, controllers, Blade views, seeders, and tests.

---

# 2. Infrastructure

## Database

- Provider: **Aiven MySQL**
- Framework: **Laravel**
- ORM: **Eloquent ORM**

## Media Storage

- Provider: **Cloudflare R2**
- Used for:
  - profile photos
  - heritage shop images
  - heritage shop videos
  - contribution supporting images
  - contribution supporting videos
  - correction request evidence
  - future module media

### Storage rule

Do **not** store:

- image BLOB
- video BLOB
- base64 encoded media
- large binary files

inside MySQL.

Store the actual file in Cloudflare R2 and save the R2 object key in the database.

Example:

```text
heritage-shops/15/front-shop.webp
contributions/38/evidence-01.jpg
contributions/38/story-video.mp4
users/7/profile/profile.webp
correction-requests/21/evidence.jpg
```

Prefer storing the **R2 object key** instead of a hard-coded public URL.

Good:

```text
heritage-shops/15/front-shop.webp
```

Avoid:

```text
https://example.r2.dev/heritage-shops/15/front-shop.webp
```

The Laravel application should generate the usable URL from the configured R2 disk.

---

# 3. Main Database Structure

## 3.1 `users`

Purpose: registered users and system administrators.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | User ID |
| name | VARCHAR(255) | No | - | Display name |
| username | VARCHAR(255) UNIQUE | Yes | NULL | Optional username/admin login handle |
| email | VARCHAR(255) UNIQUE | No | - | User email |
| phone | VARCHAR(30) | Yes | NULL | Profile phone |
| city | VARCHAR(255) | Yes | NULL | Profile city |
| bio | TEXT | Yes | NULL | Profile biography |
| email_verified_at | TIMESTAMP | Yes | NULL | Laravel email verification |
| password | VARCHAR(255) | Yes | NULL | Nullable because Google authentication may be used |
| google_id | VARCHAR(255) UNIQUE | Yes | NULL | Google OAuth identifier |
| profile_photo | VARCHAR(500) | Yes | NULL | R2 object key for profile image if this field remains in use |
| role | VARCHAR(30) INDEX | No | `user` | `user` or `admin` |
| status | VARCHAR(20) INDEX | No | `active` | `active` or `deactivated` |
| deactivated_at | TIMESTAMP | Yes | NULL | Time account was deactivated |
| deactivated_by_user_id | BIGINT UNSIGNED FK | Yes | NULL | Admin who deactivated the account |
| remember_token | VARCHAR(100) | Yes | NULL | Laravel remember token |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |

### Constraints

```text
UNIQUE(email)
UNIQUE(username)
UNIQUE(google_id)
INDEX(role)
INDEX(status)

deactivated_by_user_id
    -> users.id
    ON DELETE SET NULL
```

### User status values

Current supported values:

```text
active
deactivated
```

Do not permanently delete a user merely to deactivate the account.

---

## 3.2 `heritage_shop_contributions`

Purpose: staging table for community-submitted heritage shop information before administrator approval.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Contribution ID |
| user_id | BIGINT UNSIGNED FK | Yes | NULL | Contributor |
| contribution_title | VARCHAR(255) | Yes | NULL | Submission title |
| shop_name | VARCHAR(255) | Yes | NULL | Proposed shop name |
| primary_food_category | VARCHAR(255) | Yes | NULL | Proposed category |
| establishment_year | SMALLINT UNSIGNED | Yes | NULL | Establishment year |
| founder_name | VARCHAR(255) | Yes | NULL | Founder |
| founder_background | TEXT | Yes | NULL | Founder background |
| current_owner_name | VARCHAR(255) | Yes | NULL | Current owner |
| current_owner_details | TEXT | Yes | NULL | Current owner details |
| heritage_story | LONGTEXT | Yes | NULL | Heritage/history story |
| operating_hours | JSON | Yes | NULL | Structured operating hours |
| food_items | JSON | Yes | NULL | Submission-time food item snapshots |
| contact_number | VARCHAR(30) | Yes | NULL | Contact number |
| address | VARCHAR(255) | Yes | NULL | Address |
| city | VARCHAR(255) | Yes | NULL | City |
| state | VARCHAR(255) | Yes | NULL | State |
| postal_code | VARCHAR(20) | Yes | NULL | Postal code |
| latitude | DECIMAL(10,7) | Yes | NULL | Latitude |
| longitude | DECIMAL(10,7) | Yes | NULL | Longitude |
| status | VARCHAR(40) INDEX | No | `draft` | Contribution workflow status |
| submitted_at | TIMESTAMP | Yes | NULL | Submission time |
| reviewed_by_user_id | BIGINT UNSIGNED FK | Yes | NULL | Reviewing admin |
| review_started_at | TIMESTAMP | Yes | NULL | Review start |
| admin_feedback | TEXT | Yes | NULL | Admin feedback |
| resubmitted_at | TIMESTAMP | Yes | NULL | Resubmission time |
| withdrawn_at | TIMESTAMP | Yes | NULL | Withdrawal time |
| approved_by_user_id | BIGINT UNSIGNED FK | Yes | NULL | Approving admin |
| approved_at | TIMESTAMP | Yes | NULL | Approval time |
| rejection_reason | TEXT | Yes | NULL | Rejection reason |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| deleted_at | TIMESTAMP | Yes | NULL | Soft delete |

### Foreign keys

```text
user_id
    -> users.id
    ON DELETE SET NULL

reviewed_by_user_id
    -> users.id
    ON DELETE SET NULL

approved_by_user_id
    -> users.id
    ON DELETE SET NULL
```

### Suggested indexes

```text
INDEX(user_id, status)
INDEX(status, submitted_at)
```

### Media

Do not store contribution image/video binary content in this table.

Preferred design:

```text
media.attachable_type = heritage_shop_contribution
media.attachable_id   = heritage_shop_contributions.id
```

If an old `supporting_media` JSON column still exists, migrate away from it only after confirming the application code has been updated to use the shared `media` table.

---

## 3.3 `heritage_shops`

Purpose: canonical official heritage shop table used by the application after data has been approved or otherwise verified.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Shop ID |
| source_contribution_id | BIGINT UNSIGNED FK | Yes | NULL | Original approved contribution |
| shop_name | VARCHAR(255) | No | - | Official shop name |
| slug | VARCHAR(255) UNIQUE | Yes | NULL | Public slug |
| country | VARCHAR(100) | Yes | `Malaysia` | Country |
| primary_food_category | VARCHAR(255) | Yes | NULL | Main food category |
| establishment_year | SMALLINT UNSIGNED | Yes | NULL | Establishment year |
| founder_name | VARCHAR(255) | Yes | NULL | Founder |
| founder_background | TEXT | Yes | NULL | Founder background |
| current_owner_name | VARCHAR(255) | Yes | NULL | Current owner |
| current_owner_details | TEXT | Yes | NULL | Current owner details |
| heritage_story | LONGTEXT | Yes | NULL | Official heritage story |
| operating_hours | JSON | Yes | NULL | Structured operating hours |
| food_items | JSON | Yes | NULL | Existing current implementation snapshot; do not redesign until relevant teammate module is confirmed |
| contact_number | VARCHAR(30) | Yes | NULL | Contact number |
| address | VARCHAR(255) | Yes | NULL | Address |
| city | VARCHAR(255) | Yes | NULL | City |
| state | VARCHAR(255) | Yes | NULL | State |
| postal_code | VARCHAR(20) | Yes | NULL | Postal code |
| latitude | DECIMAL(10,7) | Yes | NULL | Latitude |
| longitude | DECIMAL(10,7) | Yes | NULL | Longitude |
| source_url | VARCHAR(500) UNIQUE | Yes | NULL | Web crawler/source URL |
| publish_status | VARCHAR(30) INDEX | No | `draft` | Publication status |
| participating_since | VARCHAR(255) | Yes | NULL | Existing display value used by other modules |
| highlight | VARCHAR(255) | Yes | NULL | Optional listing highlight |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| deleted_at | TIMESTAMP | Yes | NULL | Soft delete if implemented |

### Foreign key

```text
source_contribution_id
    -> heritage_shop_contributions.id
    ON DELETE SET NULL
```

### Canonical naming rule

The following columns are the approved canonical fields:

```text
shop_name
primary_food_category
founder_name
heritage_story
address
city
state
```

Do **not** re-add these removed duplicate columns:

```text
name
category
founder
description
location
```

`location` should remain an Eloquent/computed display value derived from fields such as:

```text
address
city
state
postal_code
```

### Media

Use the shared `media` table.

Example:

```text
media.attachable_type = heritage_shop
media.attachable_id   = heritage_shops.id
```

---

## 3.4 `media`

Purpose: shared metadata table for images/videos stored physically in Cloudflare R2.

This table should be reusable by different modules.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Media ID |
| uploaded_by_user_id | BIGINT UNSIGNED FK | Yes | NULL | User who uploaded the media |
| attachable_type | VARCHAR(100) INDEX | No | - | Logical owner type |
| attachable_id | BIGINT UNSIGNED INDEX | No | - | Logical owner ID |
| media_type | VARCHAR(20) | No | - | `image` or `video` |
| r2_object_key | VARCHAR(500) | No | - | Cloudflare R2 object key |
| original_name | VARCHAR(255) | Yes | NULL | Original file name |
| mime_type | VARCHAR(100) | Yes | NULL | MIME type |
| file_size_bytes | BIGINT UNSIGNED | Yes | NULL | File size |
| caption | VARCHAR(255) | Yes | NULL | Optional caption |
| display_order | INT UNSIGNED | No | `0` | Ordering |
| is_primary | BOOLEAN | No | `false` | Main/cover image indicator |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| deleted_at | TIMESTAMP | Yes | NULL | Soft delete |

### Foreign key

```text
uploaded_by_user_id
    -> users.id
    ON DELETE SET NULL
```

### Logical attachment examples

```text
heritage_shop
heritage_shop_contribution
correction_request
user
heritage_food_item
```

### Important

`attachable_type` + `attachable_id` is a polymorphic logical relationship.

Do not attempt to create a normal database foreign key from `attachable_id` to multiple different tables.

Recommended Eloquent design:

```php
morphMany()
morphTo()
```

---

## 3.5 `correction_requests`

Purpose: users report incorrect or outdated data on published heritage shop profiles.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Correction request ID |
| user_id | BIGINT UNSIGNED FK | Yes | NULL | Requesting user |
| heritage_shop_id | BIGINT UNSIGNED FK | No | - | Target shop |
| field_name | VARCHAR(80) | No | - | Canonical field being corrected |
| current_value | TEXT | No | - | Current value snapshot |
| suggested_value | TEXT | No | - | User proposed value |
| reason | TEXT | No | - | Reason |
| status | VARCHAR(40) INDEX | No | `pending` | Workflow status |
| admin_comment | TEXT | Yes | NULL | Admin comment |
| additional_information | TEXT | Yes | NULL | Extra information supplied later |
| reviewed_by_user_id | BIGINT UNSIGNED FK | Yes | NULL | Reviewing admin |
| review_started_at | TIMESTAMP | Yes | NULL | Review started |
| reviewed_at | TIMESTAMP | Yes | NULL | Final review time |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |

### Foreign keys

```text
user_id
    -> users.id
    ON DELETE SET NULL

heritage_shop_id
    -> heritage_shops.id
    ON DELETE CASCADE

reviewed_by_user_id
    -> users.id
    ON DELETE SET NULL
```

### Suggested indexes

```text
INDEX(user_id, status)
INDEX(heritage_shop_id, status)
INDEX(status, created_at)
```

### Evidence media

Correction evidence should be stored in Cloudflare R2 and linked through `media`.

```text
media.attachable_type = correction_request
media.attachable_id   = correction_requests.id
```

If an old `evidence_paths` JSON column currently exists, remove or migrate it only after the application has been updated to the shared media design.

---

## 3.6 `contribution_versions`

Purpose: immutable snapshots of contribution edits/submissions.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Version ID |
| heritage_shop_contribution_id | BIGINT UNSIGNED FK | No | - | Contribution |
| user_id | BIGINT UNSIGNED FK | Yes | NULL | User/admin responsible for version |
| version_number | INT UNSIGNED | No | - | Sequential version |
| reason | VARCHAR(50) | No | - | Why version was created |
| snapshot | JSON | No | - | Contribution snapshot |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |

### Constraints

```text
heritage_shop_contribution_id
    -> heritage_shop_contributions.id
    ON DELETE CASCADE

user_id
    -> users.id
    ON DELETE SET NULL

UNIQUE(heritage_shop_contribution_id, version_number)
```

---

## 3.7 `moderation_activities`

Purpose: audit trail for contribution and correction request moderation.

| Column | Type | Nullable | Default | Notes |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | No | Auto | Activity ID |
| heritage_shop_contribution_id | BIGINT UNSIGNED FK | Yes | NULL | Related contribution |
| correction_request_id | BIGINT UNSIGNED FK | Yes | NULL | Related correction |
| actor_user_id | BIGINT UNSIGNED FK | Yes | NULL | User/admin performing action |
| action | VARCHAR(50) INDEX | No | - | Moderation action |
| from_status | VARCHAR(40) | Yes | NULL | Previous status |
| to_status | VARCHAR(40) | Yes | NULL | New status |
| comment | TEXT | Yes | NULL | Comment/reason |
| metadata | JSON | Yes | NULL | Additional structured metadata |
| created_at | TIMESTAMP | Yes | NULL | Laravel timestamp |
| updated_at | TIMESTAMP | Yes | NULL | Laravel timestamp |

### Foreign keys

```text
heritage_shop_contribution_id
    -> heritage_shop_contributions.id
    ON DELETE CASCADE

correction_request_id
    -> correction_requests.id
    ON DELETE CASCADE

actor_user_id
    -> users.id
    ON DELETE SET NULL
```

### Suggested actions

Examples:

```text
SUBMIT
START_REVIEW
APPROVE
REJECT
REQUEST_REVISION
RESUBMIT
WITHDRAW
DELETE
REQUEST_ADDITIONAL_INFORMATION
APPROVE_CORRECTION
REJECT_CORRECTION
```

---

## 3.8 `notifications`

Purpose: Laravel database notifications.

| Column | Type | Nullable | Default |
|---|---|---:|---|
| id | CHAR(36) PK | No | - |
| type | VARCHAR(255) | No | - |
| notifiable_type | VARCHAR(255) INDEX | No | - |
| notifiable_id | BIGINT UNSIGNED INDEX | No | - |
| data | TEXT | No | - |
| read_at | TIMESTAMP | Yes | NULL |
| created_at | TIMESTAMP | Yes | NULL |
| updated_at | TIMESTAMP | Yes | NULL |

Use Laravel's shared notification mechanism rather than creating a separate notification table for each module.

---

# 4. Existing Tables Owned by Other Modules

The following tables already exist in the current integrated database and should **not be deleted merely because their final design has not been reviewed yet**.

Codex should preserve them unless the responsible teammate's implementation explicitly changes them.

---

## 4.1 `badges`

Current purpose: Food Passport achievement definitions.

Existing key fields include:

```text
badge_id
badge_name
description
icon
criteria_type
criteria_value
points
is_active
created_at
updated_at
```

Do not redesign this table without checking the Food Passport implementation.

---

## 4.2 `user_badges`

Current purpose: badges earned by users.

Existing relationship:

```text
user_id  -> users.id
badge_id -> badges.badge_id
```

Existing uniqueness rule:

```text
UNIQUE(user_id, badge_id)
```

---

## 4.3 `passport_stamps`

Current purpose: Food Passport check-in/stamp records.

Existing key fields include:

```text
stamp_id
user_id
shop_id
stamp_datetime
gps_latitude
gps_longitude
created_at
updated_at
```

Important known issue:

```text
shop_id should reference heritage_shops.id
```

Do not modify this until coordinating with the Food Passport module owner unless explicitly requested.

---

## 4.4 `blind_box_draws`

Current purpose: Blind Box recommendation history snapshots.

Existing key fields include:

```text
id
user_id
period
shop_name
category
state
year
description
image
created_at
updated_at
```

This is an existing teammate table. Preserve it until the Blind Box implementation owner confirms a redesign.

If image handling is later migrated to Cloudflare R2, the responsible module should decide whether `image` stores an R2 object key or whether it should use the shared `media` table.

---

# 5. Laravel Infrastructure Tables

These tables are normal Laravel infrastructure and should remain unless the Laravel configuration changes.

```text
password_reset_tokens
sessions
cache
cache_locks
jobs
job_batches
failed_jobs
```

They are not the main business-domain ERD but are valid database tables.

---

# 6. Removed / Deprecated Database Design

## 6.1 Removed duplicate `heritage_shops` columns

Do not recreate:

```text
name
category
founder
description
location
```

Use:

```text
shop_name
primary_food_category
founder_name
heritage_story
address
city
state
```

`location` may exist only as a computed/accessor display value in the Laravel model.

---

## 6.2 Heritage Food Story Contribution

Do not create a new table such as:

```text
heritage_food_story_contributions
food_story_contributions
contribution_food_story_details
```

unless the project requirements are changed again later.

The current Community Contribution implementation should focus on:

```text
heritage shop contribution
draft / submit
edit before review according to workflow
supporting media
status tracking
revision / resubmission
withdrawal before review
moderation
correction requests
notifications
audit/version history
```

---

# 7. Main Relationships

```text
users
│
├──< heritage_shop_contributions
│       │
│       ├──< contribution_versions
│       ├──< moderation_activities
│       └── approved contribution can create/update
│
├──< correction_requests >── heritage_shops
│       │
│       └──< moderation_activities
│
├──< media
│
├──< user_badges
│
├──< passport_stamps
│
└──< blind_box_draws


heritage_shop_contributions
│
└── heritage_shops.source_contribution_id


heritage_shops
│
├──< correction_requests
├──< passport_stamps    [expected relationship]
└──< media              [polymorphic]


media
├── user
├── heritage_shop
├── heritage_shop_contribution
└── correction_request
```

---

# 8. Cloudflare R2 Media Flow

Recommended upload flow:

```text
User selects image/video
        ↓
Laravel validates file
        ↓
Laravel uploads file to Cloudflare R2
        ↓
Cloudflare R2 returns/stores object under an object key
        ↓
Laravel creates `media` database record
        ↓
Database stores metadata + r2_object_key only
```

Example:

```text
Cloudflare R2 actual object:
contributions/38/01JXABC123-photo.jpg

Aiven MySQL:
media.id = 55
media.attachable_type = heritage_shop_contribution
media.attachable_id = 38
media.media_type = image
media.r2_object_key = contributions/38/01JXABC123-photo.jpg
media.mime_type = image/jpeg
media.file_size_bytes = 482193
```

---

# 9. Media Deletion Rule

When deleting/replacing media:

1. Confirm the media record belongs to the authenticated user or the acting administrator is authorized.
2. Delete the object from Cloudflare R2.
3. Delete or soft-delete the corresponding `media` record.
4. Do not leave orphaned R2 objects.
5. Do not delete an R2 object merely because a database transaction failed before completion.

Use safe transaction/error handling where appropriate.

---

# 10. Migration Rules for Codex

When Codex is asked to implement this structure:

## Must do

- Inspect existing migrations before creating new ones.
- Inspect the current Aiven schema/migration history.
- Create **new additive migrations** for changes already deployed.
- Preserve existing data.
- Use foreign keys consistently.
- Use `nullOnDelete()` / `cascadeOnDelete()` according to the relationships documented above.
- Use Laravel model casts for JSON fields.
- Use soft deletes only where the existing model/workflow expects them.
- Add appropriate indexes for status/filter/search fields.
- Update corresponding Eloquent `$fillable`, `$casts`, relationships, validation, controllers, factories, and seeders when a schema change requires it.

## Must not do

- Do not edit an old migration that has already been run on shared Aiven unless explicitly instructed.
- Do not run `migrate:fresh`.
- Do not run destructive SQL against Aiven.
- Do not drop teammate tables.
- Do not reintroduce duplicate shop fields.
- Do not save images/videos as BLOB/base64 in MySQL.
- Do not hard-code Cloudflare credentials.
- Do not commit `.env` secrets.
- Do not assume a teammate module's missing table design.

---

# 11. Immediate Changes Currently Approved

The following changes are currently approved for implementation.

## 11.1 Add user account status

If the columns do not already exist:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('status', 20)
        ->default('active')
        ->index();

    $table->timestamp('deactivated_at')
        ->nullable();

    $table->foreignId('deactivated_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();
});
```

Also ensure `google_id` is unique if it is used as the Google account identity and making it unique does not conflict with existing data.

---

## 11.2 Add shared media table

If the project has not yet implemented a shared R2 media table:

```php
Schema::create('media', function (Blueprint $table) {
    $table->id();

    $table->foreignId('uploaded_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->string('attachable_type', 100);
    $table->unsignedBigInteger('attachable_id');

    $table->string('media_type', 20);
    $table->string('r2_object_key', 500);

    $table->string('original_name')->nullable();
    $table->string('mime_type', 100)->nullable();
    $table->unsignedBigInteger('file_size_bytes')->nullable();

    $table->string('caption')->nullable();
    $table->unsignedInteger('display_order')->default(0);
    $table->boolean('is_primary')->default(false);

    $table->timestamps();
    $table->softDeletes();

    $table->index(['attachable_type', 'attachable_id']);
});
```

Recommended Eloquent implementation:

```php
// Media.php
public function attachable()
{
    return $this->morphTo();
}

// HeritageShop.php / HeritageShopContribution.php / CorrectionRequest.php
public function media()
{
    return $this->morphMany(Media::class, 'attachable');
}
```

---

# 12. Current Baseline Summary

```text
WarisanMakan
│
├── User Management
│   └── users
│
├── Heritage Shop
│   └── heritage_shops
│
├── Community Contribution
│   ├── heritage_shop_contributions
│   ├── contribution_versions
│   ├── correction_requests
│   └── moderation_activities
│
├── Shared Services
│   ├── media
│   └── notifications
│
├── Food Passport / Achievement
│   ├── badges
│   ├── user_badges
│   └── passport_stamps
│
├── Blind Box
│   └── blind_box_draws
│
└── Laravel Infrastructure
    ├── password_reset_tokens
    ├── sessions
    ├── cache
    ├── cache_locks
    ├── jobs
    ├── job_batches
    └── failed_jobs
```

---

# 13. Instruction to Codex Before Making Changes

Before modifying the project, Codex should:

1. Inspect the current Laravel migrations.
2. Inspect the relevant Eloquent models.
3. Inspect the current controller/service code using the affected columns.
4. Compare the code against this document.
5. Identify conflicts or already-existing columns before generating a migration.
6. Prefer minimal additive changes.
7. Report exactly:
   - files changed
   - migrations added
   - schema changes
   - model relationship changes
   - R2/media handling changes
   - commands the developer must run
8. Do not silently redesign another teammate's module.

This file describes the **current agreed integrated baseline**, not an instruction to invent every possible final table for all modules.
