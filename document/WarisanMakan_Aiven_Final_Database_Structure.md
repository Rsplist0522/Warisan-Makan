# WarisanMakan Aiven Final Database Structure

Purpose: clean schema reference for creating the current Laravel prototype database on Aiven MySQL after duplicate-column cleanup.

Cleanup summary:
- Removed from `heritage_shops`: `name`, `category`, `founder`, `description`, `location`.
- Kept canonical columns: `shop_name`, `primary_food_category`, `founder_name`, `heritage_story`, `address`, `city`, `state`.
- `location` remains only as an Eloquent computed display value, not a database column.

## users

Purpose: registered users and administrators.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | User ID |
| name | VARCHAR(255) | no | none | Display name |
| username | VARCHAR(255) UNIQUE | yes | null | Admin/login handle |
| email | VARCHAR(255) UNIQUE | no | none | Email address |
| phone | VARCHAR(255) | yes | null | Profile phone |
| city | VARCHAR(255) | yes | null | Profile city |
| bio | TEXT | yes | null | Profile bio |
| email_verified_at | TIMESTAMP | yes | null | Email verification time |
| password | VARCHAR(255) | yes | null | Local/admin hashed password |
| google_id | VARCHAR(255) | yes | null | Google auth ID |
| profile_photo | VARCHAR(255) | yes | null | Stored profile image path |
| role | VARCHAR(30) INDEX | no | user | `user` or `admin` |
| remember_token | VARCHAR(100) | yes | null | Laravel remember token |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

Indexes/constraints: unique `email`, unique `username`, index `role`.

## password_reset_tokens

Purpose: Laravel password reset token storage.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| email | VARCHAR(255) PK | no | none | Account email |
| token | VARCHAR(255) | no | none | Reset token |
| created_at | TIMESTAMP | yes | null | Token creation time |

## sessions

Purpose: Laravel database session storage.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | VARCHAR(255) PK | no | none | Session ID |
| user_id | BIGINT UNSIGNED INDEX | yes | null | Authenticated user ID |
| ip_address | VARCHAR(45) | yes | null | Client IP |
| user_agent | TEXT | yes | null | Browser user agent |
| payload | LONGTEXT | no | none | Serialized session payload |
| last_activity | INT INDEX | no | none | Last activity timestamp |

## cache

Purpose: Laravel cache values.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| key | VARCHAR(255) PK | no | none | Cache key |
| value | MEDIUMTEXT | no | none | Cached value |
| expiration | BIGINT INDEX | no | none | Expiration timestamp |

## cache_locks

Purpose: Laravel atomic cache locks.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| key | VARCHAR(255) PK | no | none | Lock key |
| owner | VARCHAR(255) | no | none | Lock owner token |
| expiration | BIGINT INDEX | no | none | Expiration timestamp |

## jobs

Purpose: queued jobs.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Job ID |
| queue | VARCHAR(255) INDEX | no | none | Queue name |
| payload | LONGTEXT | no | none | Serialized job payload |
| attempts | SMALLINT UNSIGNED | no | none | Attempt count |
| reserved_at | INT UNSIGNED | yes | null | Reservation timestamp |
| available_at | INT UNSIGNED | no | none | Availability timestamp |
| created_at | INT UNSIGNED | no | none | Creation timestamp |

## job_batches

Purpose: queued job batch metadata.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | VARCHAR(255) PK | no | none | Batch ID |
| name | VARCHAR(255) | no | none | Batch name |
| total_jobs | INT | no | none | Total jobs |
| pending_jobs | INT | no | none | Pending jobs |
| failed_jobs | INT | no | none | Failed job count |
| failed_job_ids | LONGTEXT | no | none | Failed job IDs |
| options | MEDIUMTEXT | yes | null | Serialized options |
| cancelled_at | INT | yes | null | Cancellation timestamp |
| created_at | INT | no | none | Creation timestamp |
| finished_at | INT | yes | null | Completion timestamp |

## failed_jobs

Purpose: failed queue jobs.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Failed job ID |
| uuid | VARCHAR(255) UNIQUE | no | none | Job UUID |
| connection | VARCHAR(255) | no | none | Queue connection |
| queue | VARCHAR(255) | no | none | Queue name |
| payload | LONGTEXT | no | none | Job payload |
| exception | LONGTEXT | no | none | Exception trace |
| failed_at | TIMESTAMP INDEX | no | CURRENT_TIMESTAMP | Failure time |

Index: composite `connection`, `queue`, `failed_at`.

## heritage_shop_contributions

Purpose: staging table for user-submitted heritage shop records.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Contribution ID |
| user_id | BIGINT UNSIGNED FK | yes | null | Contributor |
| contribution_title | VARCHAR(255) | yes | null | Submission title for review lists |
| shop_name | VARCHAR(255) | yes | null | Proposed official shop name |
| primary_food_category | VARCHAR(255) | yes | null | Proposed food category |
| establishment_year | SMALLINT UNSIGNED | yes | null | Proposed establishment year |
| founder_name | VARCHAR(255) | yes | null | Founder name |
| founder_background | TEXT | yes | null | Founder background |
| current_owner_name | VARCHAR(255) | yes | null | Current owner |
| current_owner_details | TEXT | yes | null | Current owner details |
| heritage_story | LONGTEXT | yes | null | Heritage story |
| operating_hours | JSON | yes | null | Structured operating hours |
| food_items | JSON | yes | null | Proposed food item snapshots |
| contact_number | VARCHAR(30) | yes | null | Contact number |
| address | VARCHAR(255) | yes | null | Street/full address |
| city | VARCHAR(255) | yes | null | City |
| state | VARCHAR(255) | yes | null | State |
| postal_code | VARCHAR(20) | yes | null | Postal code |
| latitude | DECIMAL(10,7) | yes | null | Latitude |
| longitude | DECIMAL(10,7) | yes | null | Longitude |
| supporting_media | JSON | yes | null | Uploaded media paths |
| status | VARCHAR(255) INDEX | no | draft | Contribution workflow status |
| submitted_at | TIMESTAMP INDEX | yes | null | Submission time |
| reviewed_by_user_id | BIGINT UNSIGNED FK | yes | null | Reviewing admin |
| review_started_at | TIMESTAMP | yes | null | Review start time |
| admin_feedback | TEXT | yes | null | Admin feedback |
| resubmitted_at | TIMESTAMP | yes | null | Resubmission time |
| withdrawn_at | TIMESTAMP | yes | null | Withdrawal time |
| approved_by_user_id | BIGINT UNSIGNED FK | yes | null | Approving admin |
| approved_at | TIMESTAMP | yes | null | Approval time |
| rejection_reason | TEXT | yes | null | Rejection reason |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |
| deleted_at | TIMESTAMP | yes | null | Soft delete time |

FKs: `user_id`, `reviewed_by_user_id`, `approved_by_user_id` -> `users.id` null on delete.
Indexes: `user_id,status`; `status,submitted_at`.

## heritage_shops

Purpose: canonical official heritage shop table shared by crawler, approved contributions, corrections, Passport, and Blind Box.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Shop ID |
| source_contribution_id | BIGINT UNSIGNED FK | yes | null | Approved source contribution |
| shop_name | VARCHAR(255) | no | none | Official shop name |
| slug | VARCHAR(255) INDEX | yes | null | Optional public slug |
| country | VARCHAR(255) | yes | null | Country |
| primary_food_category | VARCHAR(255) | yes | null | Official category |
| establishment_year | SMALLINT UNSIGNED | yes | null | Establishment year |
| founder_name | VARCHAR(255) | yes | null | Founder name |
| founder_background | TEXT | yes | null | Founder background |
| current_owner_name | VARCHAR(255) | yes | null | Current owner |
| current_owner_details | TEXT | yes | null | Current owner details |
| heritage_story | LONGTEXT | yes | null | Official heritage story |
| operating_hours | JSON | yes | null | Operating hours |
| source_url | VARCHAR(255) UNIQUE | yes | null | Crawler source URL |
| food_items | JSON | yes | null | Food item snapshots |
| contact_number | VARCHAR(30) | yes | null | Contact number |
| address | VARCHAR(255) | yes | null | Street/full address |
| city | VARCHAR(255) | yes | null | City |
| state | VARCHAR(255) | yes | null | State |
| postal_code | VARCHAR(20) | yes | null | Postal code |
| latitude | DECIMAL(10,7) | yes | null | Latitude |
| longitude | DECIMAL(10,7) | yes | null | Longitude |
| supporting_media | JSON | yes | null | Media paths |
| publish_status | VARCHAR(255) | no | draft | Publication/recommendation status |
| participating_since | VARCHAR(255) | yes | null | Passport/partner display value |
| highlight | VARCHAR(255) | yes | null | Listing highlight |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: `source_contribution_id` -> `heritage_shop_contributions.id` null on delete.
Indexes/constraints: unique `source_url`, index `slug`.
Cleanup removed: `name`, `category`, `founder`, `description`, `location`.

## badges

Purpose: Passport achievement definitions.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| badge_id | BIGINT UNSIGNED PK | no | auto | Badge ID |
| badge_name | VARCHAR(100) | no | none | Badge name |
| description | TEXT | no | none | Badge description |
| icon | VARCHAR(255) | yes | null | Icon value/path |
| criteria_type | VARCHAR(255) | no | none | Criteria type |
| criteria_value | INT | no | none | Criteria threshold |
| points | INT | no | 0 | Award points |
| is_active | BOOLEAN | no | true | Active flag |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

## user_badges

Purpose: badges earned by users.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| user_badge_id | BIGINT UNSIGNED PK | no | auto | User badge ID |
| user_id | BIGINT UNSIGNED FK | no | none | User |
| badge_id | BIGINT UNSIGNED FK | no | none | Badge |
| earned_at | TIMESTAMP | no | CURRENT_TIMESTAMP | Award time |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: `user_id` -> `users.id` cascade delete; `badge_id` -> `badges.badge_id` cascade delete.
Unique: `user_id,badge_id`.

## passport_stamps

Purpose: Food Passport check-in/stamp records.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| stamp_id | BIGINT UNSIGNED PK | no | auto | Stamp ID |
| user_id | BIGINT UNSIGNED FK | no | none | User |
| shop_id | BIGINT UNSIGNED | no | none | Heritage shop ID; FK missing in current implementation |
| stamp_datetime | DATETIME | no | none | Check-in time |
| gps_latitude | DECIMAL(10,8) | no | none | User latitude |
| gps_longitude | DECIMAL(11,8) | no | none | User longitude |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: `user_id` -> `users.id` cascade delete.

## blind_box_draws

Purpose: Blind Box draw history snapshots.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Draw ID |
| user_id | BIGINT UNSIGNED FK | yes | null | User |
| period | VARCHAR(255) | no | none | Draw period key |
| shop_name | VARCHAR(255) | no | none | Snapshot shop name |
| category | VARCHAR(255) | yes | null | Snapshot category |
| state | VARCHAR(255) | yes | null | Snapshot state |
| year | VARCHAR(255) | yes | null | Snapshot establishment/heritage year |
| description | TEXT | yes | null | Snapshot description |
| image | VARCHAR(255) | yes | null | Snapshot image URL/path |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: `user_id` -> `users.id` null on delete.

## contribution_versions

Purpose: immutable snapshots of contribution edits/submissions.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Version ID |
| heritage_shop_contribution_id | BIGINT UNSIGNED FK | no | none | Contribution |
| user_id | BIGINT UNSIGNED FK | yes | null | Actor |
| version_number | INT UNSIGNED | no | none | Version number |
| reason | VARCHAR(50) | no | none | Version reason |
| snapshot | JSON | no | none | Contribution snapshot |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: contribution cascade delete; user null on delete.
Unique: `heritage_shop_contribution_id,version_number`.

## moderation_activities

Purpose: moderation audit trail for contributions and correction requests.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Activity ID |
| heritage_shop_contribution_id | BIGINT UNSIGNED FK | yes | null | Related contribution |
| correction_request_id | BIGINT UNSIGNED FK | yes | null | Related correction request |
| actor_user_id | BIGINT UNSIGNED FK | yes | null | Acting user/admin |
| action | VARCHAR(50) INDEX | no | none | Moderation action |
| from_status | VARCHAR(40) | yes | null | Previous status |
| to_status | VARCHAR(40) | yes | null | New status |
| comment | TEXT | yes | null | Moderator/user comment |
| metadata | JSON | yes | null | Extra metadata |
| created_at | TIMESTAMP INDEX | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: contribution cascade delete; correction request cascade delete; actor null on delete.
Index: `action,created_at`.

## notifications

Purpose: Laravel notification storage.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | CHAR(36) UUID PK | no | none | Notification ID |
| type | VARCHAR(255) | no | none | Notification class |
| notifiable_type | VARCHAR(255) INDEX | no | none | Recipient model type |
| notifiable_id | BIGINT UNSIGNED INDEX | no | none | Recipient model ID |
| data | TEXT | no | none | Serialized notification payload |
| read_at | TIMESTAMP | yes | null | Read time |
| created_at | TIMESTAMP | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

Index: `notifiable_type,notifiable_id`.

## correction_requests

Purpose: user-submitted correction requests for existing official heritage shops.

| Column | Type | Nullable | Default | Purpose |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED PK | no | auto | Correction request ID |
| user_id | BIGINT UNSIGNED FK | yes | null | Requesting user |
| heritage_shop_id | BIGINT UNSIGNED FK | no | none | Target heritage shop |
| field_name | VARCHAR(80) | no | none | Canonical field being corrected |
| current_value | TEXT | no | none | Value captured at submission time |
| suggested_value | TEXT | no | none | User-proposed value |
| reason | TEXT | no | none | Request reason |
| evidence_paths | JSON | yes | null | Uploaded evidence paths |
| status | VARCHAR(40) INDEX | no | pending | Correction workflow status |
| admin_comment | TEXT | yes | null | Admin decision/comment |
| additional_information | TEXT | yes | null | Extra info from user |
| reviewed_by_user_id | BIGINT UNSIGNED FK | yes | null | Reviewing admin |
| review_started_at | TIMESTAMP | yes | null | Review start time |
| reviewed_at | TIMESTAMP | yes | null | Decision time |
| created_at | TIMESTAMP INDEX | yes | null | Creation time |
| updated_at | TIMESTAMP | yes | null | Last update |

FKs: `user_id` -> `users.id` null on delete; `heritage_shop_id` -> `heritage_shops.id` cascade delete; `reviewed_by_user_id` -> `users.id` null on delete.
Indexes: `user_id,status`; `heritage_shop_id,status`; `status,created_at`.

