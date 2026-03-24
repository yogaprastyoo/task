## Title
[Feature] Implement Search Across Hierarchy (Flat Results with Path)

## Description
Users need the ability to search for workspaces by name across all hierarchy levels. Because workspaces can be nested deeply up to 3 levels (e.g., `Project A > Marketing > Logo Design`), simply returning the matching workspace without context is confusing. This feature will implement a global search that returns a flat list of matching workspaces, where each result explicitly includes its full hierarchical path (e.g., "Project A > Marketing > Logo Design").

## Scope
- **Included:** Modify the existing workspace retrieval logic (`WorkspaceRepository@getByOwner` or the `index` method) to accept an optional `?search=` query parameter.
- **Included:** Implement a case-insensitive search (`LIKE` or `ILIKE`) on the workspace `name`.
- **Included:** Eager load ancestor relationships (`parent.parent`) to construct the contextual path for each search result.
- **Included:** Add a `path` string (e.g., "Parent > Child > Match") or a `breadcrumbs` array to the API response for each matching item.
- **Excluded:** Returning a fully nested tree structure. The response must remain flat for easy rendering in search dropdowns or lists.

## Acceptance Criteria
- [ ] Update `WorkspaceController@index` (or create a dedicated search endpoint) to accept a `search` parameter.
- [ ] If `search` is provided, the API must filter workspaces where the `name` matches the keyword (ignores `parent_id` hierarchy rules, searching globally for that user).
- [ ] Each object in the search result MUST include a `path` attribute (or `breadcrumbs` array) indicating its location in the hierarchy.
- [ ] Ensure query efficiency by eager loading parents (e.g., `->with('parent.parent')`) rather than lazy-loading N+1 queries.
- [ ] Ensure the search only returns workspaces owned by the authenticated user (`owner_id`).
- [ ] Write Pest feature tests to verify: search filtering works, paths are correctly constructed for deep children, and unauthorized data is not leaked.

## Technical Notes
- **Relation Dependency**: This feature pairs perfectly with the Breadcrumbs concept (Issue #38). You can reuse the logic used to resolve breadcrumbs.
- **Eager Loading**: Since the maximum depth is 3, you can safely use `with('parent.parent')` in your Eloquent query to fetch all possible ancestors in one go, completely preventing N+1 problems during map/transformation.
- **Service Layer**: Keep the query scope in the `WorkspaceRepository` (e.g., `searchByOwner(int $ownerId, string $keyword)`) and the path construction/transformation in the `WorkspaceService` or an Eloquent API Resource.

## File Structure Guidance
- `app/Repositories/WorkspaceRepository.php`: Add search query method.
- `app/Services/WorkspaceService.php`: Handle search orchestration and path string construction.
- `app/Models/Workspace.php`: Ensure relationships are defined properly for deep eager loading.
- `app/Http/Controllers/WorkspaceController.php`: Pass the `search` query parameter.
- `tests/Feature/WorkspaceTest.php`: Add specific tests for deep keyword searching.

## Definition of Done
- Users can search workspaces globally by name via the API.
- Search results are flat but clearly show the hierarchical context (`path`).
- No N+1 query performance issues exist during search.
- Pest feature tests confirm accurate searching and path construction for 3-level deep data.
- Code conforms to standard formatting (`vendor/bin/pint --format agent`).
- Controller remains clean, acting only as an HTTP router.
