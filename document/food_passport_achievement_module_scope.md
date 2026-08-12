# Food Passport & Achievement Module — Development Scope

**Project:** WarisanMakan  
**Assigned module:** Food Passport & Achievement  
**Primary actors:** User and System Administrator  
**Technology:** Laravel, PHP, Blade, MySQL, Progressive Web Application (PWA)

---

## 1. Module Purpose

The Food Passport & Achievement module encourages users to physically visit participating heritage food shops. It verifies location-based check-ins, records passport stamps, tracks progress, awards badges, displays statistics, and provides a leaderboard.

The main workflow is:

> Select participating shop → Request check-in → Verify location and duplication → Record visit and stamp → Recalculate progress → Evaluate badge criteria → Award badge and notify user

---

# 2. User-Side Functions

## 2.1 View Participating Heritage Food Shops

The system must display shops that are eligible for Food Passport check-in.

### Required behaviour

- Retrieve participating, published heritage shops.
- Display the shop name and location.
- Allow the user to open shop details.
- Clearly indicate whether the user has already checked in.
- Exclude unavailable or unpublished shops from new check-ins.

The exact method used to mark a shop as participating must be coordinated with the Heritage Shop Tracking module.

User interaction note:

- The user selects a shop from the participating list and opens the shop detail. The shop detail view presents a prominent "Check In" action — the user does not type shop coordinates or other check-in details manually.

---

## 2.2 Check In at a Heritage Shop

A logged-in user must be able to request a check-in at a participating shop.

### Required behaviour

1. User selects a shop.
2. User taps the check-in button.
3. System obtains the user's current location.
4. System retrieves the shop coordinates.
5. System calculates the distance.
6. System verifies that the user is within the permitted radius.
7. System checks for a duplicate check-in.
8. System records a successful visit.
9. System creates or records the passport stamp.
10. System updates progress and statistics.
11. System evaluates badge eligibility.
12. System displays a success message.

User flow (updated):

1. User selects a participating heritage shop and opens its detail page or modal.
2. The detail view shows a `Check In` button the user may tap to request a check-in.
3. When the user taps `Check In` the system requests the device/browser location permission and obtains the current GPS coordinates.
4. The system uses the shop's stored coordinates (not user-supplied shop data) and calculates the distance.
5. The system verifies the user is within the permitted radius and checks for duplicates.
6. On success the system records the visit, creates the passport stamp, updates progress, evaluates badges, and returns a success response to display to the user.

### Failed check-in conditions

- Location permission is denied.
- Current location cannot be obtained.
- User is outside the permitted radius.
- User has already checked in at the same shop.
- The shop is not participating.
- The shop is unpublished or unavailable.
- A network or server error occurs.

A failed request must not create a visit, stamp, or badge.

UI requirement:

- The UI must present the `Check In` action on the shop detail and must not require the user to manually enter shop coordinates, shop id, or GPS values to perform a check-in.

---

## 2.3 Location Verification

The system must verify the user's current location before accepting a check-in.

### Required handling

- Request browser or device location permission.
- Use the shop's stored latitude and longitude.
- Calculate distance on the server or through a trusted service.
- Compare the result with the permitted check-in radius.
- Reject out-of-range requests.
- Do not rely only on a hidden form field supplied by the browser.
- Record the verified distance where useful for audit and testing.

Implementation note:

- The front-end is responsible for requesting the user's location and sending only the user's position to the server; the server must look up the authoritative shop coordinates and perform the distance comparison.

The exact check-in radius is not defined in the source documents and must be confirmed by the team.

---

## 2.4 Prevent Duplicate Check-Ins

The system must prevent duplicate check-ins for the same user and shop.

### Minimum confirmed rule

A user cannot create another successful check-in for the same heritage food shop.

The source does not specify whether repeat visits should become possible after a period. The safest interpretation for the current assignment is one passport check-in per user per participating shop.

Enforce this rule through both:

- Application validation
- A database unique constraint

---

## 2.5 Record Visit History and Passport Stamps

After a successful check-in, record:

- User
- Heritage shop
- Check-in date
- Check-in time
- Passport stamp
- Verified location information where approved

### User functions

- View complete visit history.
- Search visited locations.
- Filter visited locations.
- Sort visited locations.
- Open the related shop.
- View the obtained stamp.

---

## 2.6 Track Passport Progress

The system must calculate and display:

- Total participating shops
- Total shops visited by the user
- Passport completion percentage
- Collected stamps
- Remaining shops

### Recommended formula

```text
Completion Percentage =
(Unique Participating Shops Visited / Total Active Participating Shops) × 100
```

Historical progress must be handled carefully when a shop becomes unpublished. The team should decide whether the denominator uses all historically participating shops or only currently active shops.

---

## 2.7 Earn Achievement Badges

After a successful check-in, the system must evaluate badge criteria.

### Required behaviour

- Retrieve active badge definitions.
- Check whether the user meets each criterion.
- Prevent duplicate badge awards.
- Create a user-badge record when eligible.
- Record the unlock date.
- Notify the user.
- Display the newly unlocked badge.

### Badge information

- Badge name
- Description
- Icon
- Category
- Unlock criteria
- Active or archived status

The exact criterion types must be defined in the badge-management design.

---

## 2.8 View Badge Collection

The user must be able to view:

- Unlocked badges
- Locked badges
- Badge name
- Description
- Icon
- Unlock status
- Unlock date

Locked badges may display the unlock condition when appropriate.

---

## 2.9 View Passport Statistics

Display:

- Total heritage food shops visited
- Passport completion percentage
- Total passport stamps
- Total achievement badges

The statistics must be derived from stored check-ins and badge records rather than manually entered values.

---

## 2.10 View Leaderboard

The user must be able to view ranking information.

### Possible ranking data identified by the requirements

- Passport points
- Number of heritage food shops visited
- Number of badges earned

The team must choose one official ranking formula because the source provides examples but does not define a single formula.

### Required behaviour

- Display username and rank.
- Display the ranking score.
- Highlight the current user.
- Use only active, eligible accounts.
- Handle tied scores consistently.
- Display an error or empty state when rankings cannot be retrieved.

---

## 2.11 Share Achievement or Passport Milestone

The user must be able to share:

- An achievement badge
- A passport milestone

### Required handling

- Display shareable achievements.
- Allow the user to select one.
- Use supported social-media or device-sharing options.
- Handle cancellation.
- Handle unsupported applications.
- Handle network or platform failure.
- Do not mark content as shared unless the action succeeds.

---

# 3. Administrator-Side Functions

## 3.1 View Achievement Badges

The administrator must be able to view all badge definitions, including:

- Badge name
- Category
- Unlock criterion
- Status
- Number of users awarded, where implemented
- Created and updated dates

---

## 3.2 Create a Badge

The administrator must be able to create a new achievement badge.

### Required fields

- Badge name
- Description
- Icon
- Category
- Unlock criteria

### Required behaviour

- Validate all required information.
- Validate the badge icon.
- Validate the criterion configuration.
- Store the badge.
- Display a confirmation message.

---

## 3.3 Edit a Badge

The administrator must be able to update badge information.

### Rules

- Validate all changes.
- Preserve existing user awards.
- Do not silently change historical unlock dates.
- Record an audit log.

The team should be careful when changing criteria for a badge that has already been awarded.

---

## 3.4 Activate, Deactivate, or Archive a Badge

### Activate

- Make the badge eligible for future evaluation.

### Deactivate

- Stop future awards without removing historical user badges.

### Archive

- Mark an obsolete badge as archived.
- Preserve all user achievement records.

The requirements state that badges already awarded to users must not be permanently deleted.

---

## 3.5 Badge Audit Log

The system must maintain an audit log for:

- Creation
- Modification
- Activation
- Deactivation
- Archival

Record:

- Badge
- Administrator
- Action
- Previous values where practical
- New values
- Date and time

---

# 4. Main Rules and Statuses

## 4.1 Badge Statuses

| Status | Meaning |
|---|---|
| `ACTIVE` | Can be evaluated and awarded |
| `INACTIVE` | Not currently awarded |
| `ARCHIVED` | Obsolete but retained for history |

## 4.2 Check-In Rules

- User must be logged in.
- Shop must be participating.
- Shop must be eligible and published.
- User must be within the allowed radius.
- Duplicate check-in is rejected.
- A successful check-in and stamp are stored atomically.
- Badge evaluation occurs only after a successful check-in.

---

# 5. Required Pages and Interfaces

## User pages

- Participating shop list
- Shop detail page or modal with `Check In` action
- Location-permission/error messages
- Food Passport dashboard
- Visit-history page
- Stamp collection
- Badge collection
- Badge detail
- Passport statistics
- Leaderboard
- Share-achievement interface

## Administrator pages

- Badge-management list
- Create badge form
- Edit badge form
- Activate/deactivate/archive controls
- Badge audit-log page

---

# 6. Recommended Database Tables

- `shop_check_ins`
- `passport_stamps`, or stamp fields within check-ins
- `achievement_badges`
- `user_achievement_badges`
- `badge_audit_logs`

### Important relationships

- A user has many check-ins.
- A shop has many check-ins.
- A user can check in once per shop under the current interpretation.
- A badge can be awarded to many users.
- A user can earn many badges.
- A badge audit record belongs to an administrator and badge.

---

# 7. Suggested Laravel Components

## Models

- `ShopCheckIn`
- `PassportStamp`
- `AchievementBadge`
- `UserAchievementBadge`
- `BadgeAuditLog`

## User controllers

- `PassportController`
- `CheckInController`
- `AchievementController`
- `LeaderboardController`
- `AchievementShareController`

## Administrator controllers

- `Admin\AchievementBadgeController`
- `Admin\BadgeAuditController`

## Services

- `LocationVerificationService`
- `CheckInService`
- `PassportProgressService`
- `BadgeEvaluationService`
- `LeaderboardService`

## Form requests

- `StoreCheckInRequest`
- `StoreAchievementBadgeRequest`
- `UpdateAchievementBadgeRequest`

## Policies

- `ShopCheckInPolicy`
- `AchievementBadgePolicy`

---

# 8. Security and Data-Integrity Checklist

- [ ] User authentication is required for check-in.
- [ ] The shop is verified as participating and published.
- [ ] The server recalculates or validates distance.
- [ ] Duplicate check-ins are blocked by a unique constraint.
- [ ] Failed check-ins create no records.
- [ ] Check-in, stamp, progress, and badge award use a database transaction.
- [ ] A badge cannot be awarded twice to the same user.
- [ ] Users cannot modify check-in timestamps or shop IDs.
- [ ] Badge-management routes require administrator access.
- [ ] Awarded badges are not permanently deleted.
- [ ] Audit logs are immutable to normal users.
- [ ] Shared content does not expose private account information.
- [ ] Location information is stored only to the degree required.

---

# 9. Testing Checklist

## Check-in tests

- [ ] Successful in-range check-in.
- [ ] Reject out-of-range check-in.
- [ ] Reject duplicate check-in.
- [ ] Reject check-in for non-participating shop.
- [ ] Reject check-in for unpublished shop.
- [ ] Handle denied location permission.
- [ ] Handle location retrieval failure.
- [ ] Handle network failure without partial records.
- [ ] Confirm visit details are recorded.
- [ ] Confirm passport stamp is created.
- [ ] Confirm progress is recalculated.

## Passport and achievement tests

- [ ] View empty passport.
- [ ] View visit history.
- [ ] Search visit history.
- [ ] Filter and sort visit history.
- [ ] Display collected and remaining shops.
- [ ] Calculate completion percentage correctly.
- [ ] Award a badge when criteria are met.
- [ ] Do not award a badge when criteria are not met.
- [ ] Prevent duplicate badge award.
- [ ] Send an unlock notification.
- [ ] Display locked and unlocked badges.
- [ ] Calculate statistics correctly.
- [ ] Display leaderboard order and ties correctly.
- [ ] Share a badge successfully.
- [ ] Handle cancelled or failed sharing.

## Administrator tests

- [ ] Create a valid badge.
- [ ] Reject invalid badge information.
- [ ] Edit a badge.
- [ ] Activate and deactivate a badge.
- [ ] Archive an obsolete badge.
- [ ] Prevent permanent deletion of an awarded badge.
- [ ] Record every badge-management action in the audit log.
- [ ] Prevent normal-user access to badge management.

---

# 10. Integration with Other Modules

## Heritage Shop Tracking

Provides:

- Shop ID
- Shop name
- Coordinates
- Published status
- Participating status

## User Management

Provides:

- Authenticated user
- Username for the leaderboard
- Active account status

## Notification Component

Receives badge-unlock notifications.

## Food Trail and Navigation

May display passport progress or visited state for shops included in a trail.

## Blind Box

May indicate whether a recommended shop has already been visited.

---

# 11. Module Boundary

This module does not:

- Create or edit normal heritage shop profiles.
- Generate navigation routes.
- Moderate community contributions.
- Manage user accounts.
- Generate random Blind Box recommendations.

---

# 12. Decisions Still Required

The source documents do not specify:

1. Exact permitted check-in radius.
2. Whether repeat check-ins become possible after a period.
3. Official leaderboard formula.
4. Complete list of badge criterion types.
5. How progress changes when a participating shop is unpublished.

These rules should be agreed before completing the database constraints and tests.

---

# 13. Recommended Development Order

1. Confirm check-in radius and duplicate rule.
2. Create check-in, badge, user-badge, and audit migrations.
3. Add participating-shop configuration.
4. Implement location verification.
5. Implement transactional check-in.
6. Build the Food Passport and history.
7. Implement progress and statistics.
8. Build administrator badge management.
9. Implement badge evaluation and awards.
10. Add notifications.
11. Build the leaderboard.
12. Add sharing.
13. Complete security, location, and feature tests.

---

# 14. Definition of Done

The module is complete when:

- Eligible users can check in only within the permitted radius.
- Duplicate and invalid check-ins are rejected.
- Successful visits and stamps are recorded.
- Passport history, progress, and statistics are accurate.
- Badges are awarded automatically without duplication.
- Users can view and share achievements.
- The leaderboard uses the agreed calculation.
- Administrators can safely manage badge definitions.
- Historical awards and audit records are preserved.
- All location, transaction, authorization, badge, and ranking tests pass.
