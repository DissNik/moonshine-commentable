# Comment field for [MoonShine Laravel admin panel](https://moonshine-laravel.com)

A field for managing comments associated with this model through a `MorphMany` or `HasMany` relationship.

<picture>
    <img alt="Queue Dashboard" src="./art/screenshot.png">
</picture>

---

## Current architecture

The package now has two explicit layers:

- Comment domain core: models, trait, events, policies, migrations, and config-based model resolution.
- Optional MoonShine adapter: field, resource, pages, components, views, and assets.

The default install path remains the same, but projects can now override the MoonShine-facing layer without forking the package internals.

## Public extension points

- `moonshine-commentable.models.comment`: override the comment model.
- `moonshine-commentable.models.comment_read`: override the read-state model.
- `moonshine-commentable.policies.comment`: override the comment policy.
- `moonshine-commentable.moonshine.register_resource`: disable automatic resource registration.
- `moonshine-commentable.moonshine.resource`: replace the default `CommentResource`.
- `moonshine-commentable.moonshine.pages.index`: replace the default index page.
- `moonshine-commentable.moonshine.pages.form`: replace the default form page.
- `moonshine-commentable.moonshine.events.comment_added`: replace the client event emitted after async comment creation.
- `moonshine-commentable.transport.mode`: reserve transport selection, with `polling` as the default.
- `moonshine-commentable.transport.polling.interval`: polling interval used by the default list fragment.

## Integration guidance

If a project needs custom author presentation, extra actions, or different MoonShine field/page wiring, prefer a custom resource or page class configured through `moonshine.resource` and `moonshine.pages.*` instead of patching package internals.

The transport contract stays polling-first for now. Websocket support should later plug into the same transport configuration without changing the comment domain model or database schema.
