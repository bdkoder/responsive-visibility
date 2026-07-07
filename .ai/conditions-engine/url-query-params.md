# URL Query Param Visibility Plan

## Goal

Add a URL query param condition to the Visibility Conditions engine so a block can show or hide based on the current request URL.

## Why it matters

- Useful for campaign links, preview links, QA flows, and targeted landing pages.
- Stays server-side, so gated content is removed before the page reaches the browser.
- Fits the existing conditions engine without touching device breakpoint behavior.

## Supported syntax

- `preview`
- `utm_source=google`
- `utm_source IN [google, yahoo]`
- `campaign IN [summer, fall]`

## Rule behavior

- A plain key checks existence only.
- `key=value` checks for one exact value.
- `key IN [a, b]` checks for any allowed value.
- Existing All / Any grouping continues to work through the current condition relation selector.

## Implementation notes

1. Add a new `Condition` subclass under `includes/conditions/`.
2. Register it in `includes/class-conditions.php`.
3. Keep the parser small and explicit.
4. Read from the current request query on the server.
5. Leave the render pipeline and existing conditions untouched.

## Safety

- Missing or malformed params should simply return false.
- No frontend JS should be added for this feature.
- Existing blocks must keep behaving the same.

## Docs

- Public usage guide: `docs/url-query-params.html`
- Main visibility guide: `docs/visibility-conditions.html`
