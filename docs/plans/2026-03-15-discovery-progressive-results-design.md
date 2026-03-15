# Progressive Discovery Results Design

## Goal

Show discovery candidates while a fresh discovery run is still in progress, instead of waiting for the full candidate batch to finish.

## Recommended Approach

Use incremental polling, not SSE or websockets.

- The first click on the dashboard `+` button starts a fresh discovery run.
- The backend processes at most one discovery source per progress request.
- After each step, the frontend receives the currently cached partial candidate list and re-renders the panel.
- The list grows source-by-source while the scan is still running.

## Why This Approach

- Fits the current Heimdall stack without adding a new long-lived transport.
- Keeps the existing cached discovery services and chunked host probing.
- Limits each progress request to a bounded amount of work.
- Reuses the current dashboard polling model and error handling style.

## Backend Changes

- Add a `discoveries/progress` endpoint.
- Keep a small cached scan-session state:
  - source order
  - current source index
  - whether the scan is complete
- On a fresh run, reset the state and start from the first source.
- On each progress request:
  - advance through already-cached sources quickly
  - scan at most one uncached source
  - return:
    - `totalCount`
    - `candidates`
    - `completedSources`
    - `totalSources`
    - `isComplete`

## Frontend Changes

- Add a `data-progress-url` to the discovery hub markup.
- When the panel opens:
  - start a fresh progress run
  - render partial candidates immediately
  - keep polling until `isComplete`
- While the run is active:
  - keep the current “searching” state visible
  - update candidate cards in place as new results arrive
- When the panel is closed:
  - stop the in-flight progress polling loop

## Performance Rules

- No continuous background hot loop.
- Poll only while the discovery panel is open.
- Stop when hidden or complete.
- Let each service keep its own cache TTL and chunk rules.

## Testing

- JS test:
  - the discovery panel should render an initial partial result set
  - then append/replace with more candidates from later progress payloads
  - then stop polling when complete
- PHP test:
  - progress endpoint returns partial payload shape
  - progress endpoint respects admin authorization

