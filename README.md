# PHBeam Engine

PHBeam is simple and fast PHP micro-framework. It has no any admin panel. All data and config stored by PHP or JSON files.

It is good for building sites with dozens of pages and even with small product catalogs or galleries.

## Requirements

You need Apache web server with PHP >= 5.4. No database required.

## Installation

You can clone this repository:

```
git clone https://github.com/nikomtis/phbeam.git myproject
```

Or download zip with last version from https://github.com/nikomtis/phbeam/releases

Configure your web server's `DocumentRoot` to use `public/index.php` file as entry point.

## Setting up your project

To create a website with PHBeam Engine you should follow these steps:

- Add menus and menu items (routes)
- Fill PHP files with HTML code and meta tags for each page
- Define page layouts if you have different content structure on different pages
- Create modules with equal or similar content that appears on many pages

Let's review all these steps one by one.

### Adding routes (menu items)

Any webpage can be displayed for users only if it presents in one of the menu files.

`menus/` directory stores menu files with website routes. We have one menu named `main` out of the box. Let's look into the file `main.php`:

```php
<?php

return [
    '' => [
        'file' => 'home',
        'name' => 'Home',
        'layout' => '',
        'body_class' => '',
    ],
];
```

There is only one menu item, or route.

Each key on the first level of the array is the page URL. Array element having empty URL matches website home page. Leading slashes are skipped by router.

Each menu item contains 4 fields:

- `file` (required) is name of the file (without extension) in `content/` directory that contains HTML code for this page; if that file is in subdirectory (e.g. `content/subdirectory/page.php`), field must filled like that: `'file' => 'subdirectory/page'`
- `name` (optional) is menu item display name; you can use it to render navigation menu
- `layout` (optional) is layout name that should be used by page; if field does not present or it is empty, page will use `default_layout` value from `config.php`
- `body_class` (optional) is CSS class that will be applied to `<body>` HTML tag; if field does not present or it is empty, page will use `default_body_class` value from `config.php`

Feel free to add your own fields that can be used, for example, for special navigation menu styling or scripts.

If you want to add a new menu item for some page, menu file will look like:

```php
<?php

return [
    '' => [
        'file' => 'home',
        'name' => 'Home',
        'layout' => '',
        'body_class' => '',
    ],
    'about' => [ // Page will be available on example.com/about
        'file' => 'about', // Page file with HTML code is "content/about.php"
        'name' => 'About us', // We want this name in navigation menu
    ]
];
```

In "Creating modules" section we will see how to render navigation menu.

#### Registering new menu

If your website have only one navigation menu (Home, About, Contacts and so on), you have to add menu items to main menu file. But you may need to have more than one nav, e.g. secondary menu in the sidebar. Then create new menu file `secondary.php` in `menus/` directory and register it in `config.php`:

```php
...
'menus' => [
    'main',
    'secondary', // Separate menu for sidebar
],
...
```

### Filling site with content

`content/` directory stores files that presents webpages content.

Each page may have up to 3 files. Look to example for page named "home" (menu file stores page names):

- `home.php` will contain HTML code for this page
- `home_meta.php` will store title, description and keywords that will be inserted in `<head>` HTML tag
- `home_modules.php` will control which modules will be rendered in every specified position

So to add a new page you should create at least one file with name that you spicified in menu.

Also there is a special page named "404". It will render if there is no menu item for requested route. You can operate with it's files too.

### Defining page layouts

`layouts/` directory stores page layouts. Layouts are wrappers for your pages content.

There is `layout.php` file that contains primary markup for your website. This is right place for adding fonts, stylesheets and scripts. You can use `phb_insert_css()` and `phb_insert_js()` functions to add local CSS and JS files. If you want to add stylesheet from `public/css/main.css` file, just do something like this:

```html
<head>
    ...
    <?php phb_insert_css('main'); ?>
    ...
</head>
```

and it will be rendered as:

```html
<head>
    ...
    <link rel="stylesheet" href="/css/main.css?v=1531560805">
    ...
</head>
```

In the code above you can see "v" URL parameter. It is timestamp when file was last modified. It helps to clear browser cache when you update your stylesheet.

`phb_insert_js()` works similar. This code:

```html
<?php phb_insert_js('main'); ?>
```

will be rendered as:

```html
<script src="/js/main.js?v=1531560805"></script>
```

#### Custom layouts

You can add different layouts that will be used by different pages.

There is `bare.php` layout out of the box that renders webpage content directly and does not affect to page view; it used by all pages by default (default layout can be changed in `config.php` file, see `default_layout` option). But you can add one more layout for wrap some pages with for example div with `container` class to limit content width. Just create file `container.php` (you may choose any name) with code:

```html
<div class="container">
    <?php echo $GLOBALS['article']; ?>
</div>
```

and then specify `container` layout (without extension) for any page in menu file.

Layout for 404 page can be specified in `config.php` - `error_page_layout` is what you need.

### Creating modules

`modules/` directory stores files that can be included in any place of your website.

Let's look how to add a navbar module.

First we need to create `navbar.php` file in `modules/` directory:

```html
<ul class="nav">
    <?php foreach ($GLOBALS['menu_main'] as $alias => $menu_item): ?>
        <a href="/<?php echo $alias; ?>" class="nav-link<?php if ($alias === $GLOBALS['path']) echo ' active'; ?>">
            <?php echo $menu_item['name']; ?>
        </a>
    <?php endforeach;?>
</ul>
```

What we are using in this file:

- `$GLOBALS['menu_main']` is associative array of `main` menu items from `menus/main.php` file
- `$alias` is page URL for building link (don't forget about leading `/` to define URL root)
- `$GLOBALS['path']` is current page URL
- `$menu_item['name']` is page name; you have access to all menu item's fields that present in menu file

Now you can just insert `<?php phb_insert_module('navbar'); ?>` in any place of the page file (or even in other module) to render navbar.

But it will be more comfortable to use module positions.

#### Using module positions

Let's upgrade our `layouts/container.php` custom layout (Bootstrap CSS classes are used):

```html
<div class="container">
    <?php if (phb_get_position_modules('sidebar')): ?>
        <div class="row">
            <div class="col-sm-3">
                <aside>
                    <?php phb_insert_position('sidebar'); ?>
                </aside>
            </div>
            <div class="col-sm-9">
                <main>
                    <?php echo $GLOBALS['article']; ?>
                </main>
            </div>
        </div>
    <?php else: ?>
        <main>
            <?php echo $GLOBALS['article']; ?>
        </main>
    <?php endif;?>
</div>
```

We can use `phb_get_position_modules('sidebar')` function to check if is there any modules in `sidebar` position for current page and prevent rendering empty sidebar.

Insert `<?php phb_insert_position('sidebar'); ?>` to specify where modules should appear.

Then configure pages to use `container` layout by editing menu file or by setting `default_layout` option in `config.php` file.

And define this module for `sidebar` position in `content/home_modules.php`:

```php
<?php

return [
    'sidebar' => [
        'navbar'
    ]
];
```

#### Passing data to modules

You may need to render similar but not equal modules on different pages, for example page title with background image. Let's see how to do this.

Create `modules/fancy_title.php` file:

```html
<div class="fancy-title" style="background-image: url(<?php echo $params['image']; ?>)">
    <div class="fancy-title-text">
        <?php echo $params['text']; ?>
    </div>
</div>
```

And add it to `content/home_modules.php`:

```php
<?php

return [
    'sidebar' => [
        'navbar',
        'fancy_title' => [
            'text' => 'Home', // $params['text'] in the module file
            'image' => '/img/home.jpg' // $params['image']
        ]
    ]
];
```

Also if you do not want to use position, you can pass params to module with second parameter:

```html
<?php phb_insert_module('fancy_title', ['text' => 'Home', 'image' => '/img/home.jpg']); ?>
```

#### Changing modules classes prefix

When using position to render a module, it will be wrapped in div with prefixed CSS class. For example, if your module file is `navbar.php`, CSS class will be `module_navbar`.

You can change classes prefix in `config.php` by editing `modules_classes_prefix` option.

## Allowing URL params

PHBeam router strictly checks URL matching with aliases in menu files. If there will be only one excess character, you will see 404 error page. All URL params will be blocked.

But if you want to allow opening website pages with URL parameters, add needed params to `config.php`. For example, to allow URLs like `example.com/about?yclid=123123` (such URLs are used by Yandex.Direct) do this:

```php
<?php

return [
    'allowed_url_params' => [
        'yclid',
    ],
    ...
];
```

## Working with resources

In PHBeam you can store custom data arrays: small product catalogs, photo galleries, news feeds and more. You can use PHP or JSON files for that.

### PHP resources

Let's add resource for two products.

Create `resources/catalog_iphones.php` resource file:

```php
<?php

return [
    'iphone_x_256_silver' => [
        'name' => 'iPhone X 256GB Silver',
        'image' => '/img/catalog/iphones/iphone_x_256_silver.png',
        'memory' => '256',
        'display' => '5,8',
    ],
    'iphone_8_64_gold' => [
        'name' => 'iPhone 8 64GB Gold',
        'image' => '/img/catalog/iphones/iphone_8_64_gold.png',
        'memory' => '64',
        'display' => '4,7',
    ],
];
```

Then create `modules/catalog.php` module:

```html
<div class="catalog">
    <?php foreach (phb_get_data_from_php("resources/catalog_{$params['category']}") as $key => $product): ?>
        <div class="product">
            <div class="product-image">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
            </div>
            <div class="product-name">
                <?php echo $product['name']; ?>
            </div>
            <div class="product-memory">
                <?php echo $product['memory']; ?>GB
            </div>
            <div class="product-display">
                <?php echo $product['display']; ?>"
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

And insert `<?php phb_insert_module('catalog', ['category' => 'iphones']); ?>` in the right place on your page.

`phb_get_data_from_php()` function returns array with data from specified PHP file. We can walk by array and render some HTML code for each array element.

### JSON resources

You can use JSON resources as well. In this case your resource file `resources/catalog_iphones.json` will be like:

```json
{
  "iphone_x_256_silver": {
    "name": "iPhone X 256GB Silver",
    "image": "/img/catalog/iphones/iphone_x_256_silver.png",
    "memory": "256",
    "display": "5,8"
  },
  "iphone_8_64_gold": {
    "name": "iPhone 8 64GB Gold",
    "image": "/img/catalog/iphones/iphone_8_64_gold.png",
    "memory": "64",
    "display": "4,7"
  }
}
```

Instead of `phb_get_data_from_php()` function you will need to use `phb_get_data_from_json()`.

## License

MIT Licensed. Copyright (c) Nikita Privalov 2018.
