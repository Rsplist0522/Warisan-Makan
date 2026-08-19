# Community Contribution Module — Development Scope

**Project:** WarisanMakan  
**Assigned module:** Community Contribution & Vendor Submission  
**Primary actors:** User and System Administrator  
**Technology:** Laravel, PHP, Blade, MySQL, Progressive Web Application (PWA)

---

## 1. Module Purpose

The Community Contribution module allows registered users to contribute information that helps preserve Malaysian culinary heritage. Users can submit heritage shop information, including heritage story/details fields, supporting media, and correction requests. System Administrators review these records before the information is accepted into the main heritage database.

This module is responsible for the complete workflow from:

> Create contribution → Save draft → Submit → Review → Approve / Reject / Request revision → Notify user → Keep moderation history

---

## 2. Important Implementation Rule

The project documents contain a small inconsistency:

- One requirement version says users may edit a submission before approval.
- The later Community Contribution document says users edit saved drafts before submission.

This checklist follows the latest workflow previously confirmed for the module:

- A **Draft** can be edited or deleted.
- A **Pending Review** contribution cannot be edited, but may be withdrawn before the administrator starts reviewing it.
- An **Under Review** contribution cannot be edited or withdrawn.
- A **Revision Required** contribution can be edited and resubmitted.
- Approved and rejected records remain in contribution history.

---

# 3. User-Side Functions

## 3.1 Submit Heritage Shop Information

The user must be able to create a heritage shop contribution.

### Form fields

- Contribution title
- Shop name
- Primary food category
- Establishment year
- Founder name
- Founder background
- Current owner name
- Current owner details
- Heritage or family story
- Operating hours
- Contact number
- Address
- City
- State
- Postal code
- Latitude and longitude, where applicable
- Supporting images or videos

### System responsibilities

- Require the user to be logged in.
- Validate all mandatory fields.
- Validate establishment year and contact number formats.
- Sanitize long-form text.
- Validate uploaded media.
- Store the contribution under the authenticated user.
- Allow **Save as Draft** or **Submit**.
- Assign the correct contribution status.
- Display success or validation messages.

---

## 3.2 Heritage Story Details Within Shop Submissions

Heritage story/details are collected only as part of the Heritage Shop submission workflow.

### Required handling

- Keep the existing Heritage or family story field on the shop submission form.
- Validate and sanitize long-form shop story text.
- Store the story/details with the heritage shop contribution record.
- Do not implement a separate food-story contribution type, form, route, table, or moderation queue.

---

## 3.3 Upload Supporting Media

The user may attach evidence or supporting media to a contribution.

### Required handling

- Image upload
- Video upload
- Optional caption
- File type validation
- File size validation
- Upload failure handling
- Preview uploaded files
- Remove or replace files before submission
- Prevent unsafe file execution
- Delete abandoned media when a draft or contribution is permanently removed

---

## 3.4 Manage Drafts

The user must be able to manage contributions with the `DRAFT` status.

### Functions

- View all personal drafts
- Open a draft
- Edit a draft
- Add or remove supporting media
- Delete a draft
- Submit a saved draft
- Display an empty state when no drafts exist

### Rules

- A user may access only their own drafts.
- Submitting a draft changes its status to `PENDING_REVIEW`.
- A submitted draft must record the submission date and time.

---

## 3.5 Manage Contributions

The user must be able to view and monitor all submitted contributions.

### Contribution list information

- Contribution title
- Contribution summary
- Submission date
- Last updated date
- Current status
- Available action

### Functions

- View contribution history
- Search or filter personal contributions
- View complete contribution details
- View attached media
- View administrator feedback
- View the contribution's edit/version history
- Withdraw an eligible contribution
- Revise and resubmit when revision is requested

### Empty state

Display a clear message when the user has no contribution history.

---

## 3.6 Withdraw a Contribution

A user may withdraw a contribution only when:

- Its status is `PENDING_REVIEW`; and
- The administrator has not started reviewing it.

### Required handling

- Display a confirmation dialog.
- Check the contribution status again on the server.
- Check that `review_started_at` is empty.
- Change the status to `WITHDRAWN`.
- Record `withdrawn_at`.
- Do not allow withdrawal after the status becomes `UNDER_REVIEW`.

A withdrawn contribution should remain visible in the user's contribution history.

---

## 3.7 Revise and Resubmit

When the administrator requests revision:

- The status becomes `REVISION_REQUIRED`.
- The user can read the administrator's feedback.
- The user can edit the contribution.
- The system stores a new contribution version.
- The user may upload replacement or additional media.
- Resubmission changes the status to `PENDING_REVIEW`.
- The system records the resubmission time.

---

## 3.8 Submit Heritage Information Correction Request

From a published heritage shop profile, the user must be able to report incorrect or outdated information.

### Correction request fields

- Related heritage shop
- Field believed to be incorrect
- Current displayed value
- Proposed corrected value
- Reason for the request
- Supporting evidence
- Optional caption

### User functions

- Submit a correction request
- View all personal correction requests
- View correction request details
- View current status
- Read administrator comments
- Provide additional information when requested
- Receive the final outcome

---

## 3.9 User Notifications

Users must receive notifications when:

- A contribution is approved
- A contribution is rejected
- A contribution requires revision
- A correction request is approved
- A correction request is rejected
- Additional correction information is required

### Notification functions

- Display notification title and message
- Link the notification to the related contribution or correction request
- Mark notification as read
- Show unread notification count, if the shared notification component supports it

---

# 4. Administrator-Side Functions

## 4.1 Submission Management Dashboard

The administrator must be able to:

- View all pending submissions
- View heritage shop submissions
- Search by contribution title or contributor
- Filter by status
- Filter by contributor
- Filter by submission date
- Sort records
- Open a submission for review

### Suggested dashboard summary

- Pending Review count
- Under Review count
- Revision Required count
- Approved count
- Rejected count
- Correction Request count

---

## 4.2 Review a Community Contribution

When the administrator opens a contribution, the system must display:

- Contributor information
- Contribution summary
- All submitted form details
- Supporting media
- Submission date
- Current status
- Version/edit history
- Previous review comments
- Available moderation actions

### Start-review behaviour

When the administrator starts reviewing:

- Change the status from `PENDING_REVIEW` to `UNDER_REVIEW`.
- Record `review_started_at`.
- Record a `START_REVIEW` moderation action.
- Prevent the user from withdrawing the contribution.

---

## 4.3 Moderation Actions

The administrator must be able to perform these actions:

### Approve

- Validate that required contribution information exists.
- Change status to `APPROVED`.
- Record the administrator and review time.
- Create or hand off the approved shop record to the Heritage Shop Tracking module.
- Link the resulting shop through `approved_shop_id` where supported.
- Notify the contributor.

### Reject

- Require a rejection reason.
- Change status to `REJECTED`.
- Store the review comment.
- Notify the contributor.

### Request Revision

- Require revision instructions.
- Change status to `REVISION_REQUIRED`.
- Store the review comment.
- Allow the user to edit and resubmit.
- Notify the contributor.

### Delete Inappropriate or Duplicate Submission

- Require a reason.
- Prefer soft deletion for audit purposes.
- Change status to `DELETED`.
- Record the moderation action.

---

## 4.4 Review Correction Requests

The administrator must be able to:

- View all correction requests
- Search and filter requests
- Open the related published shop
- Compare the current value and proposed value
- View the user's reason
- View supporting evidence
- Start review
- Approve
- Reject
- Request additional information
- Enter comments for the requester

### Approve correction

- Update the approved field in the published heritage shop record.
- Record the old and new information where possible.
- Change correction status to `APPROVED`.
- Store the review record.
- Notify the requester.

### Reject correction

- Require a reason.
- Change status to `REJECTED`.
- Store the review record.
- Notify the requester.

### Request additional information

- Require an administrator comment.
- Change status to `ADDITIONAL_INFO_REQUIRED`.
- Notify the requester.
- Allow the requester to provide the requested information.

---

## 4.5 Moderation History and Audit

The administrator must be able to view processed records and moderation history.

### Audit information

- Submission or correction request ID
- Administrator
- Action
- Previous status
- New status
- Comment or reason
- Date and time
- Contribution version reviewed

Audit records should not be editable by normal users.

---

# 5. Status Definitions and Transitions

## 5.1 Contribution Statuses

| Status | Meaning | User Action | Administrator Action |
|---|---|---|---|
| `DRAFT` | Saved but not submitted | Edit, delete, submit | None |
| `PENDING_REVIEW` | Waiting for review | View, withdraw if review has not started | Start review |
| `UNDER_REVIEW` | Administrator is reviewing | View only | Approve, reject, request revision, delete |
| `REVISION_REQUIRED` | Changes requested | Edit and resubmit | Wait for resubmission |
| `APPROVED` | Contribution accepted | View | View history |
| `REJECTED` | Contribution rejected | View feedback | View history |
| `WITHDRAWN` | User withdrew before review | View | View history |
| `DELETED` | Removed as inappropriate or duplicate | View only if retained for user history | View audit record |

### Main contribution flow

```text
DRAFT
  └── Submit
       └── PENDING_REVIEW
            ├── User withdraws before review → WITHDRAWN
            └── Administrator starts review → UNDER_REVIEW
                    ├── Approve → APPROVED
                    ├── Reject → REJECTED
                    ├── Request revision → REVISION_REQUIRED
                    │       └── User resubmits → PENDING_REVIEW
                    └── Delete → DELETED
```

---

## 5.2 Correction Request Statuses

| Status | Meaning |
|---|---|
| `PENDING` | Waiting for administrator review |
| `UNDER_REVIEW` | Administrator is checking the request |
| `ADDITIONAL_INFO_REQUIRED` | User must provide more information |
| `APPROVED` | Correction accepted and shop information updated |
| `REJECTED` | Correction not accepted |

---

# 6. Required Pages and Interfaces

## 6.1 User Pages

- My Contributions dashboard
- Heritage Shop Submission form
- Draft list
- Draft detail/edit page
- Contribution history/list
- Contribution detail page
- Revision edit page
- Correction Request form
- My Correction Requests list
- Correction Request detail page
- Related notifications view

## 6.2 Administrator Pages

- Community Submission dashboard
- Submission list with search and filters
- Submission review detail page
- Contribution version history page
- Correction Request list
- Correction Request review page
- Moderation history/audit page

---

# 7. Database Tables to Handle

The Community Contribution ERD includes these tables:

## Core contribution tables

1. `contributions`
2. `contribution_shop_details`
3. `contribution_media`
4. `contribution_versions`
5. `contribution_reviews`

## Correction request tables

6. `correction_requests`
7. `correction_evidence`
8. `correction_reviews`

## Shared table

9. `notifications`

## Main relationships

- One user can submit many contributions.
- One contribution stores heritage shop information, including heritage story/details fields.
- One contribution can contain many media records.
- One contribution can contain many versions.
- One contribution can contain many review records.
- One user can submit many correction requests.
- One shop can receive many correction requests.
- One correction request can contain many evidence records and reviews.

---

# 8. Suggested Laravel Components

## 8.1 Models

- `Contribution`
- `ContributionShopDetail`
- `ContributionMedia`
- `ContributionVersion`
- `ContributionReview`
- `CorrectionRequest`
- `CorrectionEvidence`
- `CorrectionReview`
- `Notification`

## 8.2 User Controllers

- `ContributionController`
- `ContributionDraftController`
- `ContributionRevisionController`
- `CorrectionRequestController`

## 8.3 Administrator Controllers

- `Admin\ContributionModerationController`
- `Admin\CorrectionModerationController`
- `Admin\ModerationHistoryController`

## 8.4 Form Request Validation

- `StoreShopContributionRequest`
- `UpdateDraftContributionRequest`
- `ResubmitContributionRequest`
- `StoreCorrectionRequest`
- `ModerateContributionRequest`
- `ModerateCorrectionRequest`

## 8.5 Policies and Middleware

- Authentication middleware
- Administrator role middleware
- `ContributionPolicy`
- `CorrectionRequestPolicy`
- Ownership checks
- Status-based action checks
- CSRF protection
- File-upload authorization

## 8.6 Services

- `ContributionService`
- `ContributionModerationService`
- `CorrectionRequestService`
- `MediaUploadService`
- `NotificationService`
- `ContributionPublicationService`

Use database transactions for submission, moderation, approval, and correction updates.

---

# 9. Validation and Security Checklist

- [ ] Only authenticated users can submit contributions.
- [ ] Only the owner can view or edit their private draft.
- [ ] Only administrators can access moderation routes.
- [ ] Server-side validation is used even when client-side validation exists.
- [ ] Long-form text is sanitized against XSS.
- [ ] File extensions and MIME types are validated.
- [ ] File sizes are limited.
- [ ] Uploaded files use safe generated filenames.
- [ ] Invalid media is rejected with a clear message.
- [ ] Users cannot change contribution status through request manipulation.
- [ ] Users cannot withdraw a contribution after review begins.
- [ ] Rejection and revision actions require comments.
- [ ] Moderation actions are stored in audit records.
- [ ] Approval and correction updates use database transactions.
- [ ] Soft deletion is used where audit history must be preserved.

---

# 10. Testing Checklist

## 10.1 User Tests

- [ ] Submit valid heritage shop information.
- [ ] Reject incomplete heritage shop information.
- [ ] Save contribution as draft.
- [ ] Edit own draft.
- [ ] Prevent access to another user's draft.
- [ ] Delete own draft.
- [ ] Submit saved draft.
- [ ] Upload valid image.
- [ ] Upload valid video.
- [ ] Reject unsupported media format.
- [ ] Reject oversized media.
- [ ] View contribution history.
- [ ] View contribution details and feedback.
- [ ] Withdraw pending contribution before review.
- [ ] Prevent withdrawal after review starts.
- [ ] Edit revision-required contribution.
- [ ] Resubmit revised contribution.
- [ ] Submit correction request.
- [ ] Upload correction evidence.
- [ ] View correction status.
- [ ] Open and mark notification as read.

## 10.2 Administrator Tests

- [ ] View pending submissions.
- [ ] Search and filter submissions.
- [ ] Start review and lock withdrawal.
- [ ] Approve contribution.
- [ ] Reject contribution with reason.
- [ ] Request revision with instructions.
- [ ] Delete inappropriate or duplicate submission.
- [ ] View contribution versions.
- [ ] Confirm moderation action is recorded.
- [ ] Confirm contributor receives notification.
- [ ] View correction requests.
- [ ] Approve correction and update shop data.
- [ ] Reject correction with reason.
- [ ] Request additional information.
- [ ] View moderation history.

---

# 11. Integration with Other Modules

## User Management

Required from the User Management module:

- Authenticated user ID
- User role
- Active/deactivated account status
- User profile information

## Heritage Shop Tracking

Required from the Heritage Shop Tracking module:

- Existing heritage shop list
- Existing heritage food items
- Food categories
- Published shop profile
- Creation of a shop record after contribution approval
- Updating published shop information after correction approval

## Notification Component

The notification table is shared with other modules. Coordinate:

- Notification format
- Unread count
- Notification routes
- Reference IDs
- Mark-as-read behaviour

---

# 12. Module Boundary — What Is Not Your Main Responsibility

The following functions belong mainly to other modules:

- Google login and user-account management
- Public heritage shop browsing and searching
- Heritage shop CRUD performed directly by the shop-management administrator
- Publishing or unpublishing normal shop records
- Food Passport check-ins and badges
- Food Trail generation and navigation
- Blind Box recommendations

Your module only interacts with the Heritage Shop Tracking module when:

1. An approved contribution must create or update heritage content.
2. An approved correction request must update a published shop record.

---

# 13. Recommended Development Order

1. Create migrations and Eloquent models.
2. Implement contribution status rules.
3. Build the Heritage Shop form.
4. Implement draft management.
5. Implement media uploads.
6. Build contribution history and detail pages.
7. Implement withdrawal rules.
8. Build administrator submission dashboard.
9. Implement moderation actions and audit records.
10. Implement revision and resubmission.
11. Implement correction requests.
12. Implement correction moderation.
13. Integrate notifications.
14. Integrate approved records with Heritage Shop Tracking.
15. Complete feature tests and fix authorization issues.

---

# 14. Definition of Done

The Community Contribution module is complete when:

- Users can submit heritage shop contributions.
- Drafts can be saved, edited, deleted, and submitted.
- Media upload validation works.
- Users can track statuses and view feedback.
- Eligible pending contributions can be withdrawn.
- Revision-required contributions can be corrected and resubmitted.
- Users can submit and track correction requests.
- Administrators can search, review, and moderate submissions.
- Administrators can review correction requests.
- Every moderation action is audited.
- Users receive the correct notifications.
- Approved data is correctly handed to or updated in the Heritage Shop Tracking module.
- All authorization, validation, and status-transition tests pass.

---

## Source Basis

This checklist was prepared from the WarisanMakan proposal, functional requirements, Community Contribution use-case documentation, product backlog, and Community Contribution ERD.
