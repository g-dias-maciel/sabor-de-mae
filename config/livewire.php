<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Class Namespace
    |---------------------------------------------------------------------------
    |
    | This value sets the root class namespace for Livewire component classes in
    | your application. This value will change where component auto-discovery
    | finds components. It's also referenced by the file creation commands.
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |---------------------------------------------------------------------------
    | View Path
    |---------------------------------------------------------------------------
    |
    | This value is used to specify where Livewire component Blade templates are
    | stored when running file creation commands like `artisan make:livewire`.
    | It is also used if you choose to omit a component's render() method.
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |---------------------------------------------------------------------------
    | Layout
    |---------------------------------------------------------------------------
    | The view that will be used as the layout when rendering any Livewire
    | component that doesn't explicitly define one in its render() method.
    |
    */

    'layout' => 'layouts.app',

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing uploads in a temporary directory
    | before the file is stored permanently. All file uploads are directed to
    | a global endpoint for temporary storage. You may configure this below:
    |
    */

    'temporary_file_upload' => [
        'disk' => null,        // Example: 'local', 's3'
        'rules' => null,       // Example: ['file', 'mimes:png,jpg']
        'directory' => null,   // Example: 'tmp'
        'middleware' => null,  // Example: 'throttle:5,1'
        'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs...
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5, // Max duration (in minutes) before an upload is invalidated...
    ],

    /*
    |---------------------------------------------------------------------------
    | Render On Redirect
    |---------------------------------------------------------------------------
    |
    | This value determines if Livewire will run a component's `render()` method
    | after a redirect has been triggered using something like `redirect(...)`
    | Setting this to true will also re-render a component's `render()`
    | method after a redirect.
    |
    */

    'render_on_redirect' => false,

    /*
    |---------------------------------------------------------------------------
    | Eloquent Model Binding
    |---------------------------------------------------------------------------
    |
    | Previous versions of Livewire supported binding directly to eloquent model
    | properties using wire:model by default. However, this behavior has been
    | removed in favor of wire:model attributes. You may opt-in to the
    | older behavior by setting this to true.
    |
    */

    'legacy_model_binding' => false,

    /*
    |---------------------------------------------------------------------------
    | Auto-inject Frontend Assets
    |---------------------------------------------------------------------------
    |
    | By default, Livewire automatically injects its JavaScript and CSS into the
    | <head> and <body> of pages containing Livewire components. You may
    | customize this behavior or disable it for custom use cases.
    |
    */

    'inject_assets' => true,

    /*
    |---------------------------------------------------------------------------
    | Livewire Component Scanning
    |---------------------------------------------------------------------------
    |
    | Configure which directories Livewire should scan for components.
    |
    */

    'component_paths' => [
        app_path('Livewire'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Navigation
    |---------------------------------------------------------------------------
    |
    | This value determines if Livewire will use its own router for navigating
    | between pages. This is the default behavior in Livewire v3+ and is
    | required for `wire:navigate` to work.
    |
    */

    'navigate' => false,
];
