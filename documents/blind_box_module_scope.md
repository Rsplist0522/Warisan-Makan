# Blind Box Module — Development Scope

**Project:** WarisanMakan  
**Assigned module:** Blind Box  
**Primary actors:** User and System Administrator  
**Technology:** Laravel, PHP, Blade, MySQL, Progressive Web Application (PWA)

\---

## 1\. Module Purpose

The Blind Box module gives users a random heritage shop recommendation to make heritage food discovery more playful and spontaneous.

The proposal confirms these functions:

1. Manage Blind Box data
2. Generate a random heritage shop
3. Save recommendation history
4. View previous Blind Box results
5. Re-roll a recommendation
6. Filter a random recommendation
7. Edit a Blind Box item
8. Delete a Blind Box item

The basic workflow is:

> User selects filters → System builds an eligible shop pool → Random shop is selected → Result is displayed and stored → User may view history or re-roll

\---

## 2\. Important Source Limitation

The proposal lists the Blind Box functions, but the Requirement Analysis document does not provide:

* Dedicated functional requirements
* A use-case diagram
* Use-case description tables
* Detailed business rules
* Confirmed database fields
* Defined filter options
* Re-roll limits
* Random-selection rules

Therefore, this file separates:

* **Confirmed scope** from the proposal
* **Recommended implementation rules** needed to build a complete module

The team should add formal Blind Box functional requirements and use-case documentation before the final submission.

\---

# 3\. User-Side Functions

## 3.1 Generate a Random Heritage Shop

The user must be able to request a random heritage shop recommendation.

### Confirmed behaviour

* User starts a Blind Box draw.
* System randomly selects a heritage shop.
* System displays the result.

### Recommended eligibility rules

Use only shops that are:

* Published
* Available
* Included in Blind Box data
* Compatible with the user's active filters

### Suggested result information

* Shop name
* Cover image
* Food category
* State or location
* Short heritage description
* Signature food, where available
* Link to the full shop profile
* View on map action, where supported

These display fields come from the Heritage Shop Tracking module.

\---

## 3.2 Filter the Random Recommendation

The proposal confirms that users can filter the random recommendation, but it does not define the filter types.

### Recommended initial filters

* Food category
* State
* Location
* Not previously recommended
* Not previously visited, if integrated with Food Passport

Only food category, state, and location are already common fields elsewhere in WarisanMakan. The team must formally confirm which Blind Box filters are required.

### Required behaviour

* Validate selected filter values.
* Build an eligible shop pool.
* Display a no-result message when the pool is empty.
* Allow filters to be cleared.
* Store applied filters with the result history where useful.

\---

## 3.3 Re-Roll a Recommendation

The user must be able to request another random result.

### Recommended behaviour

* Keep the same active filters.
* Exclude the immediately previous result when at least one alternative exists.
* Generate a new result.
* Save the new result as a separate history record.
* Display a message when no alternative shop is available.

The proposal does not specify a re-roll limit. The team must decide whether re-roll is unlimited or restricted.

\---

## 3.4 Save Recommendation History

Every successful Blind Box result must be stored.

### Recommended history information

* User
* Recommended heritage shop
* Draw date and time
* Applied filters
* Whether the result was produced by a re-roll
* Sequence or draw-session identifier

Do not store failed draws as successful recommendations.

\---

## 3.5 View Previous Blind Box Results

The user must be able to view their previous recommendations.

### Suggested list information

* Shop name
* Result image
* Food category
* State
* Recommended date
* Re-roll indicator

### Required behaviour

* Show only the authenticated user's history.
* Paginate long history.
* Allow the user to open the related shop profile.
* Display an empty state when no result exists.
* Handle a shop that later becomes unpublished without deleting the history record.

The interface may show that an old result is no longer publicly available.

\---

# 4\. Administrator-Side Functions

## 4.1 Manage Blind Box Data

The administrator must be able to control which heritage shops are eligible for random recommendation.

Because the proposal separately lists edit and delete, a complete management page should minimally support:

* View Blind Box items
* Add or include an eligible shop
* Edit Blind Box item configuration
* Delete or remove an item from Blind Box eligibility

The **add/include** action is inferred from the word “manage” and should be confirmed in the formal requirements.

\---

## 4.2 View Blind Box Items

Recommended management-list information:

* Heritage shop
* Shop publication status
* Blind Box eligibility status
* Food category
* State
* Weight or priority, only if weighted random selection is approved
* Created and updated dates

The simplest design is one Blind Box configuration record per heritage shop.

\---

## 4.3 Add or Include a Blind Box Item

Recommended behaviour:

* Select an existing heritage shop.
* Confirm that it is published and valid.
* Prevent duplicate Blind Box configuration for the same shop.
* Set the item as active.
* Save the record.
* Display a success message.

The Blind Box module should not create a new heritage shop. It only references a shop managed by the Heritage Shop Tracking module.

\---

## 4.4 Edit a Blind Box Item

The proposal explicitly confirms editing.

### Possible editable settings

* Active or inactive eligibility
* Display note
* Weight or priority, only if approved
* Availability period, only if approved

The source does not define these settings. A minimal implementation only needs an active/inactive eligibility flag.

\---

## 4.5 Delete a Blind Box Item

The proposal explicitly confirms deletion.

### Recommended behaviour

* Display a confirmation prompt.
* Remove or deactivate the shop from future random draws.
* Preserve previous user recommendation history.
* Prefer soft deletion when historical results reference the item.

Deleting the Blind Box configuration must not delete the actual heritage shop.

\---

# 5\. Random Selection Rules

## 5.1 Minimum Random Algorithm

A simple source-aligned implementation can:

1. Retrieve eligible published shop IDs.
2. Apply confirmed filters.
3. Exclude invalid records.
4. Select one record randomly.
5. Store the result.
6. Display the related shop.

In Laravel, this can be implemented through a service rather than placing the logic directly in a controller.

## 5.2 Recommended Fairness Rules

* Every eligible shop has equal probability by default.
* Do not silently favour a shop unless weighted selection is formally approved.
* Exclude the immediately previous result on re-roll where alternatives exist.
* Do not return unpublished or deleted shops.
* Use server-side random selection.
* Record the selected shop before returning the result to prevent history mismatch.

## 5.3 No-Result Handling

When no eligible shop matches:

* Display a clear message.
* Explain that the filters may be too restrictive.
* Allow the user to clear or change filters.
* Do not create a recommendation-history record.

\---

# 6\. Suggested Statuses

## Blind Box item status

|Status|Meaning|
|-|-|
|`ACTIVE`|Eligible for random selection|
|`INACTIVE`|Temporarily excluded|
|`DELETED`|Removed from future draws but retained where history depends on it|

A shop must also be `PUBLISHED` in the Heritage Shop Tracking module to be selected.

\---

# 7\. Required Pages and Interfaces

## User pages

* Blind Box landing page
* Filter panel
* Draw/reveal interface
* Result page or result card
* Re-roll action
* Recommendation-history list
* Previous-result detail

## Administrator pages

* Blind Box management list
* Include-shop form
* Edit Blind Box item
* Activate/deactivate control
* Delete confirmation

\---

# 8\. Recommended Database Tables

## 8.1 `blind\\\\\\\_box\\\\\\\_items`

Suggested fields:

* `id`
* `heritage\\\\\\\_shop\\\\\\\_id`
* `status`
* `weight`, only if approved
* `available\\\\\\\_from`, only if approved
* `available\\\\\\\_until`, only if approved
* `created\\\\\\\_by`
* `created\\\\\\\_at`
* `updated\\\\\\\_at`
* `deleted\\\\\\\_at`

## 8.2 `blind\\\\\\\_box\\\\\\\_results`

Suggested fields:

* `id`
* `user\\\\\\\_id`
* `blind\\\\\\\_box\\\\\\\_item\\\\\\\_id`
* `heritage\\\\\\\_shop\\\\\\\_id`
* `draw\\\\\\\_session\\\\\\\_id`
* `filters\\\\\\\_json`
* `is\\\\\\\_reroll`
* `recommended\\\\\\\_at`

Storing `heritage\\\\\\\_shop\\\\\\\_id` directly helps preserve the result even if the configuration later changes.

\---

# 9\. Suggested Laravel Components

## Models

* `BlindBoxItem`
* `BlindBoxResult`

## User controllers

* `BlindBoxController`
* `BlindBoxHistoryController`

## Administrator controllers

* `Admin\\\\\\\\BlindBoxItemController`

## Services

* `BlindBoxSelectionService`
* `BlindBoxHistoryService`

## Form requests

* `GenerateBlindBoxRequest`
* `StoreBlindBoxItemRequest`
* `UpdateBlindBoxItemRequest`

## Policies

* `BlindBoxResultPolicy`
* `BlindBoxItemPolicy`

\---

# 10\. Validation and Security Checklist

* \[ ] Random draws use only published and active shops.
* \[ ] Filter values are validated.
* \[ ] Random selection occurs on the server.
* \[ ] The user cannot submit a chosen shop ID as the “random” result.
* \[ ] A successful result and history record are stored atomically.
* \[ ] Users can view only their own history.
* \[ ] Administrator routes require administrator access.
* \[ ] Duplicate Blind Box configuration is prevented.
* \[ ] Removing an item does not delete the heritage shop.
* \[ ] Removing an item does not destroy previous recommendation history.
* \[ ] Unpublished shops are excluded immediately from new draws.
* \[ ] User-supplied filter data is not executed as raw SQL.

\---

# 11\. Testing Checklist

## User tests

* \[ ] Generate a result from all eligible shops.
* \[ ] Confirm every returned shop is published.
* \[ ] Filter by each confirmed filter.
* \[ ] Combine filters.
* \[ ] Display a no-result message.
* \[ ] Re-roll and receive another shop where available.
* \[ ] Handle re-roll when only one eligible shop exists.
* \[ ] Save every successful result.
* \[ ] Do not save failed draws.
* \[ ] View personal recommendation history.
* \[ ] Prevent access to another user's history.
* \[ ] Open a previous result.
* \[ ] Handle a previously recommended shop that becomes unpublished.

## Administrator tests

* \[ ] View Blind Box items.
* \[ ] Include an eligible heritage shop.
* \[ ] Prevent duplicate item configuration.
* \[ ] Prevent inclusion of an invalid shop.
* \[ ] Edit eligibility.
* \[ ] Deactivate an item.
* \[ ] Confirm inactive items are excluded.
* \[ ] Delete or soft-delete an item.
* \[ ] Confirm historical results remain.
* \[ ] Prevent normal-user access to management routes.

## Randomness tests

* \[ ] All eligible items can be selected across repeated controlled tests.
* \[ ] Ineligible items are never selected.
* \[ ] Active filters are always respected.
* \[ ] Re-roll exclusion is respected when an alternative exists.
* \[ ] Result and stored history always reference the same shop.

\---

# 12\. Integration with Other Modules

## Heritage Shop Tracking

Provides:

* Shop ID
* Publication status
* Name
* Images
* Category
* State and location
* Heritage story
* Profile route

Blind Box configuration must reference existing shops rather than duplicate shop information.

## User Management

Provides the authenticated user for recommendation history.

## Food Passport and Achievement

Optional integration can:

* Mark already visited shops.
* Filter out previously visited shops.
* Show passport status on the result.

This integration is not explicitly required by the proposal.

## Food Trail and Navigation

Optional integration can:

* Open the result on a map.
* Start a trail from the recommended shop.

This integration is not explicitly required.

\---

# 13\. Module Boundary

The Blind Box module does not:

* Create or edit the official heritage shop profile.
* Publish or unpublish shops.
* Perform Passport check-ins.
* Generate a multi-stop food trail.
* Moderate community contributions.

It only selects from valid shop records and stores recommendation history.

\---

# 14\. Decisions Still Required

Before implementation is finalized, the team must confirm:

1. Exact filter options.
2. Whether guests or only logged-in users may draw.
3. Whether re-roll is limited.
4. Whether already recommended shops should be excluded.
5. Whether already visited shops should be excluded.
6. Whether random selection is equal or weighted.
7. Whether an administrator can add a shop directly to Blind Box eligibility.
8. Whether date-based availability is needed.
9. Whether recommendation history can be deleted by users.

These decisions should be added to the formal functional requirements and use-case descriptions.

\---

# 15\. Recommended Development Order

1. Write and approve detailed Blind Box functional requirements.
2. Confirm filters and random-selection rules.
3. Create Blind Box item and result migrations.
4. Integrate published heritage-shop queries.
5. Build administrator item management.
6. Implement the selection service.
7. Build the user draw and reveal interface.
8. Implement filter handling.
9. Implement re-roll.
10. Store recommendation history.
11. Build the history page.
12. Add optional Passport or Trail integration.
13. Complete authorization, eligibility, and randomness tests.

\---

# 16\. Definition of Done

The Blind Box module is complete when:

* The formal Blind Box requirements are approved.
* Users can generate a valid random heritage shop.
* Confirmed filters are applied correctly.
* Re-roll follows the agreed rule.
* Every successful result is stored.
* Users can view only their own history.
* Administrators can manage eligible Blind Box items.
* Inactive, deleted, or unpublished shops are never selected.
* Historical results remain intact after configuration changes.
* All validation, authorization, eligibility, history, and randomness tests pass.

