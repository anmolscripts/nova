# Nova Public API Stabilization Review

Date: 2026-07-01

Scope: public global helpers, HTTP response helpers, CLI command names, generator names, exception names, and exported framework classes/interfaces.

## Public API Review

### Helpers

The global helper surface is now documented in `bootstrap/helpers.php`.

Primary helpers reviewed:

- Request and routing: `request()`, `route()`, `url()`, `back()`, `back_url()`
- Responses: `response()`, `json()`, `redirect()`, `download()`, `view()`, `render()`
- Data and services: `config()`, `env()`, `db()`, `cache()`, `logger()`, `storage()`
- Session and validation: `session()`, `flash()`, `old()`, `errors()`, `validate()`
- Auth and authorization: `auth()`, `user()`, `guest()`, `check()`, `id()`, `login()`, `logout()`, `attempt()`, `can()`, `cannot()`, `authorize()`
- Actions and UI feedback: `success()`, `error()`, `warning()`, `info()`, `danger()`
- Uploads and assets: `upload()`, `asset()`, `asset_url()`, `image()`

No public helper was removed because every duplicate or ambiguous helper could be in use by applications. Compatibility was preserved.

### CLI

Current CLI command groups are internally consistent enough for v1.0:

- Core: `serve`, `doctor`, `about`, `version`
- Generators: `make:page`, `make:component`, `make:action`, `make:policy`, `make:middleware`, `make:migration`, `make:procedure`, `make:seeder`
- Cache: `page:cache`, `route:cache`, `config:cache`, `component:cache`, `optimize`, `optimize:clear`, `cache:clear`

The generator namespace consistently uses `make:*`. Cache commands consistently use `*:cache`, except `optimize` and `optimize:clear`, which are established aggregate commands and should remain.

### Responses

The response naming model is clear:

- `response()` creates `Nova\Http\Response`
- `json()` creates JSON `Response`
- `redirect()` creates `RedirectResponse`
- `download()` creates `DownloadResponse`
- `view()` creates `ViewResponse`

The one consistency concern is that `json()` does not include the word `response`, while `response()`, `redirect()`, and `download()` are action-oriented. This is common PHP framework practice and should not be changed before v1.0.

### Exceptions

Current exception names are consistent with their owning subsystem:

- `Nova\Exceptions\HttpException`
- `Nova\Validation\ValidationException`
- `Nova\Database\DatabaseException`
- `Nova\Action\ActionException`
- `Nova\Component\ComponentException`
- `Nova\App\LayoutRenderException`

The only naming outlier is `LayoutRenderException`, which is more specific than the others. That specificity is useful and does not require a v1.0 change.

## Naming Consistency Report

### Consistent

- `make:*` generator command names.
- `*:cache` cache command names.
- Boolean helpers: `auth()`, `guest()`, `check()`, `can()`, `cannot()`.
- Storage API method names mirror common filesystem verbs: `put`, `get`, `exists`, `delete`, `copy`, `move`, `size`, `mime`, `lastModified`, `url`, `temporaryUrl`, `download`, `directories`, `files`.
- Upload lifecycle methods are consistent: `store`, `storeAs`, `temporary`, `path`, `url`, `extension`, `mime`, `size`, `hashName`.

### Inconsistent But Kept For Compatibility

- `auth()` and `check()` both return the authenticated state. Keep both for v1.0; prefer documenting `auth()` as primary.
- `danger()` is a flash helper, while `error()` returns an `ActionResult`. The names are understandable but can surprise users because both describe failure states.
- `asset()` renders Vite tags, while `asset_url()` returns a public storage URL. The distinction is documented, but the names are close enough to confuse new developers.
- `storage()` now resolves disks for configured disk names and still supports legacy storage paths for non-disk strings. This compatibility behavior should be documented prominently.
- `route()` has three behaviors: all route params, one current route param, and named route URL generation. This is convenient but overloaded.
- `session()` and `flash()` are getter/setter helpers depending on argument count. This is familiar but harder for static analysis.
- `cache()` reads values but does not write values through the helper. This is consistent with the current implementation, but the helper name may imply full cache access.

## PHPDoc Coverage Summary

- Added PHPDoc to every global public helper in `bootstrap/helpers.php`.
- Added class/interface PHPDoc summaries to framework classes and interfaces under `framework/`.
- Public method return types were reviewed. Non-constructor public methods have explicit return types. Constructors intentionally do not declare return types.
- Strict typing was reviewed for framework/config/bootstrap/test PHP files touched by this pass; touched files use `declare(strict_types=1);`.

## Recommended But Unapplied Breaking Changes For Nova v2.0

These should not be applied before v1.0 because they can break existing applications:

1. Replace `check()` with `auth()` as the single public authenticated-state helper, or make `auth()` return an auth manager and keep `check()` for the boolean.
2. Split `route()` into clearer helpers, such as `route_param()` for current parameters and `route()` only for URL generation.
3. Rename `asset()` to `vite()` or `asset_tags()` if Nova wants `asset()` to mean a public URL in the future.
4. Rename `asset_url()` to `storage_url()` if public storage URLs should be explicitly tied to Storage.
5. Replace getter/setter overloads like `session()` and `flash()` with explicit methods or helpers for static analysis friendliness.
6. Consider renaming `danger()` to `error_flash()` or renaming action `error()` to `failure()` to remove failure-message ambiguity.
7. Make `storage()` return only `StorageManager|Disk` and remove legacy path fallback.
8. Consider adding `download_inline()` or an options array to `download()` if boolean positional flags become hard to read.
9. Normalize exception names around subsystem nouns only, for example `LayoutException`, if Nova wants all subsystem exceptions to be symmetrical.
