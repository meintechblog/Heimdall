# Dashboard Search Enter Behavior Design

## Goal
Remove the provider dropdown next to the Heimdall homepage search field and make the search box behave in a user-centric way:

- typing continues to filter Heimdall tiles live
- pressing `Enter` with a non-empty query opens a Google search for that query in a new tab

## Current State
- The homepage search form is rendered in `app/Search.php`.
- The provider dropdown is still emitted by the server when multiple search providers exist.
- Live tile filtering and tag/category filtering already live in `resources/assets/js/dashboardFilters.js`.
- The current submit logic is still coupled to provider selection and legacy search-provider behavior.

## Chosen Approach
Use a single-purpose homepage search UI:

1. Remove the provider dropdown from the rendered markup.
2. Keep the visible search field as the single control for both live filtering and `Enter`.
3. Handle `Enter` in `dashboardFilters.js`:
   - if the field is empty, do nothing special
   - if the field is non-empty, prevent normal form submit and open a Google search in a new tab
4. Keep the existing on-input local filter path untouched except for decoupling it from provider selection.

## Why This Approach
- It matches user expectation: typing filters locally, `Enter` escalates to the web.
- It removes dead UI instead of hiding a misleading control.
- It centralizes homepage search behavior in one JS module instead of splitting it across PHP markup, form submit, and provider state.
- It avoids depending on restore-sensitive user settings for homepage filtering behavior.

## Alternatives Considered
### Keep the dropdown hidden and force `google` in JS
Rejected because the server would still render obsolete provider state and the DOM would keep dead behavior.

### Keep provider support and special-case only `Enter`
Rejected because the visible UI would still imply that provider choice matters, which conflicts with the desired user flow.

## Affected Areas
- `app/Search.php`
- `resources/assets/js/dashboardFilters.js`
- `tests/js/dashboardFilters.test.js`
- `tests/Feature/DashTest.php`
- `readme.md`

## Testing Strategy
- JS regression test for live tile filtering while typing
- JS regression test for `Enter` opening a Google search in a new tab
- Feature regression test confirming the provider dropdown is no longer rendered on the dashboard

## Risks
- Popup blockers could block `window.open` if the handler is not attached directly to the submit/keypress interaction.
- Removing the dropdown must not break the existing search form layout.
- Existing tests that assume provider markup may need adjustment.
