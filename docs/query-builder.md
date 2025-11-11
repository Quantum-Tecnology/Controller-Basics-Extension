### Query Builder — Field syntax, options and examples

This document explains how to use the package’s Query Builder to fetch Eloquent models using a compact, GraphQL‑like field syntax. The behavior described here is derived from the feature tests in `tests/Feature/Builder/QueryBuilder/*` and the builder’s source code.

#### What it does

- Accepts a root Eloquent `Model` and a field selection (string or array).
- Eager-loads nested relations respecting the field selection.
- Automatically adds `withCount` for non-BelongsTo relations (e.g., `comments_count`, `tags_count`).
- Supports per‑relation pagination (limit/offset) and ordering.
- Supports flexible filtering on the root model and on any included relation path.

The entry point is `QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder::execute()` which returns an `Illuminate\Database\Eloquent\Builder`. You then finish with standard Eloquent methods such as `get()`, `sole()`, `paginate()`, or `simplePaginate()`.

---

### Quick start

```php
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use App\Models\Post; // your app’s model

$builder = app(QueryBuilder::class);

// 1) Minimal: fetch one post with default fields (all model columns) and without relations
$post = $builder->execute(new Post())->sole();

// 2) With relations using GraphQL-like string
$fields = 'id author { name }';
$post   = $builder->execute(new Post(), $fields)->sole();

// 3) With nested relations using array syntax
$fields = ['id', 'comments' => ['likes' => ['comment']], 'author'];
$post   = $builder->execute(new Post(), $fields)->sole();
```

---

### Field selection syntax

You can express fields either as a string (GraphQL‑like) or as a PHP array.

- String examples
  - `"id author { name }"`
  - `"id title comments { id likes { comment } }"`
- Array examples
  - `[ 'id', 'author' => [], ]`
  - `[ 'id', 'comments' => ['likes' => ['comment']], 'author', ]`

Notes
- Scalars and relation names can be mixed at the same level. A relation becomes “active” when followed by a `{ ... }` block (string syntax) or when mapped to an array (array syntax).
- BelongsTo relations are loaded as normal, but counts are not added to them (counts are added to non‑BelongsTo relations only).
- When you include a BelongsTo relation, the builder ensures the parent’s foreign key is selected automatically.

---

### Relation counts

For every non‑BelongsTo relation included, the builder prepares a corresponding `*_count` at the appropriate level via `withCount()`.

Examples from tests
- Including `comments` on `Post` yields `comments_count` on the `Post` instances.
- Including `likes` on a `Comment` yields `likes_count` on the `Comment` instances.

---

### Pagination and ordering per relation

You can paginate and order each included relation independently using dynamic option keys. Keys are formed by taking the relation “path” and joining segments with underscores.

Key patterns
- Pagination
  - `page_limit_{path}`: max number of related records to load for that path
  - `page_offset_{path}`: zero‑based offset of related records for that path
- Ordering
  - `order_column_{path}`: column used to sort the related records
  - `order_direction_{path}`: `asc` (default) or `desc`

Path naming
- A simple relation `comments` → path is `comments`.
- A nested relation `comments.likes` → path is `comments_likes`.

Defaults
- If you don’t pass pagination options for a path, the builder uses:
  - `page_limit`: `config('page.per_page')` (commonly 10 in the tests)
  - `page_offset`: 0
- If `order_direction_{path}` is omitted, it defaults to `asc`.

Examples
```php
$fields  = ['id', 'comments' => ['likes' => ['comment']], 'author'];

// Paginate comments on Post: take 4, skip first 3
$options = [
    'page_offset_comments' => 3,
    'page_limit_comments'  => 4,
];
$post = $builder->execute(new Post(), $fields, $options)->sole();

// Paginate likes under comments: take 2, skip first 2
$options = [
    'page_offset_comments_likes' => 2,
    'page_limit_comments_likes'  => 2,
];
$post = $builder->execute(new Post(), $fields, $options)->sole();

// Order comments DESC and likes under comments DESC
$options = [
    'order_column_comments'          => 'id',
    'order_direction_comments'       => 'desc',
    'order_column_comments_likes'    => 'id',
    'order_direction_comments_likes' => 'desc',
];
$post = $builder->execute(new Post(), $fields, $options)->sole();
```

---

### Filtering

Filters are passed as option keys that start with `filter` and contain a descriptor in parentheses. The general form is:

- Root model filter
  - `filter(field,op) => value`
  - If `op` is omitted: `filter(field) => value` defaults to `=`
- Relation path filter
  - `filter_{path}(field,op) => value`
  - Use underscores for the path: e.g., `comments.likes` → `comments_likes`

Supported operations and value forms (from `FilterParser`/`ApplyFilter`)
- Equality / IN
  - `op =` or `==` with value: one value or multiple using `|`
  - Example: `filter(title,=) => 'Hello'` or `filter(id,=) => '1|2|3'`
- Comparisons
  - Any SQL operator like `>`, `>=`, `<`, `<=`, `!=`, `<>`, `like`, etc.
  - Multiple values are applied as separate `where field op value` clauses within a grouped `where`.
- Nullability
  - Use `null` or `not-null` as the operation; the value should be `'true'` (string)
  - Example: `filter(deleted_at,null) => 'true'`
- Boolean strings
  - If value is the string `'true'` or `'false'`, it is cast to boolean and applied with the given operator.
- Custom scopes via `by_...`
  - If the field name starts with `by_`, the builder calls a camelCase method on the query, passing:
    - First argument: a collection of tokens from the operation string split by `|`
    - Second argument: the collection of values
  - Example: `filter(by_created_between,year|month) => '2024-01-01|2024-12-31'` calls `$query->byCreatedBetween(collect(['year','month']), collect(['2024-01-01','2024-12-31']))`

Filter grouping
- Filters are grouped by model/table automatically. For relation paths, use the path in the key prefix to target that relation’s query closure.

Examples
```php
$fields = ['id', 'comments' => ['likes' => []]];

$options = [
    // Root Post filters
    'filter(title,like)' => '%Laravel%',
    'filter(id,=)'       => '1|2|3',

    // Filter comments under posts
    'filter_comments(body,like)' => '%great%'
];

$posts = $builder->execute(new Post(), $fields, $options)->get();
```

---

### Return types and chaining

`execute()` returns an `Eloquent\Builder` for the provided model. You can use any standard terminal method on it:

- `get()` → `Collection<Model>`
- `sole()` → single `Model` (throws if not exactly one)
- `paginate($perPage)` / `simplePaginate($perPage)` → paginator instances on the root query

When using the separate Graph layer provided by this package (see tests under `tests/Feature/Builder/GraphBuilder`), the per‑relation pagination and counts are transformed into a serializable array structure with `data`/`meta`. At the Query Builder level, you receive Eloquent models with relations loaded and counts available as `*_count`.

---

### End‑to‑end examples (from tests)

Fetch a post with its author
```php
$post = $builder->execute(new Post(), 'id author { name }')->sole();
// $post->author->name is available
```

Fetch a post with tags and counts
```php
$post = $builder->execute(new Post(), 'id tags { name }')->sole();
// $post->tags_count === 20 (for example)
// $post->tags->count() defaults to config('page.per_page') (10 in tests)
```

Nested includes with pagination and ordering
```php
$fields = ['id', 'comments' => ['likes' => ['comment']], 'author'];

$options = [
    'page_offset_comments'          => 3,
    'page_limit_comments'           => 4,
    'page_offset_comments_likes'    => 2,
    'page_limit_comments_likes'     => 2,
    'order_column_comments'         => 'id',
    'order_direction_comments'      => 'desc',
    'order_column_comments_likes'   => 'id',
    'order_direction_comments_likes'=> 'desc',
];

$post = $builder->execute(new Post(), $fields, $options)->sole();
// Access loaded relations: $post->comments, $post->comments->first()->likes, etc.
// Counts available: $post->comments_count, $post->comments->first()->likes_count
```

---

### Tips and caveats

- The default per‑relation page limit comes from `config('page.per_page')`. In the test suite it is 10.
- Offsets are zero‑based in options; make sure your expectations match when verifying specific record positions.
- Counts are only generated for non‑BelongsTo relations.
- For nested counts, the builder injects `withCount()` inside the relation closures automatically; at the root level it applies `withCount()` directly on the main query.
- If you include a BelongsTo relation, the builder makes sure the foreign key is selected on the parent to avoid missing column errors.

---

### Reference of dynamic option keys

- Pagination
  - `page_limit_{path}`
  - `page_offset_{path}`
- Ordering
  - `order_column_{path}`
  - `order_direction_{path}` (`asc` or `desc`)
- Filters
  - Root: `filter(field,op)` / `filter(field)`
  - Relation: `filter_{path}(field,op)` / `filter_{path}(field)`

Where `{path}` is the relation path with dots replaced by underscores. Examples: `comments`, `comments_likes`, `author_posts`, etc.

---

### Troubleshooting

- Nothing loads from a relation
  - Ensure the relation name is included correctly in the field selection and that it has a block (`author { ... }`) or an array value in PHP syntax.
- Counts not appearing
  - Verify the relation is not a BelongsTo; counts are only added for non‑BelongsTo relations.
- Ordering seems ignored
  - Provide both `order_column_{path}` and, if needed, `order_direction_{path}`. Defaults to `asc` if direction is omitted.
- Filters not applied
  - Check the filter key syntax. For nested relations, remember to use the underscore path form, e.g., `filter_comments(body,like)`.
