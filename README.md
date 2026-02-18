# 🔎 Artisan Browse

## An interactive terminal UI for browsing, searching, and executing Laravel Artisan commands.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joshembling/artisan-browse.svg?style=flat-square)](https://packagist.org/packages/joshembling/artisan-browse)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/joshembling/artisan-browse/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/joshembling/artisan-browse/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/joshembling/artisan-browse/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/joshembling/artisan-browse/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/joshembling/artisan-browse.svg?style=flat-square)](https://packagist.org/packages/joshembling/artisan-browse)


🔎 Artisan Browse provides an interactive terminal UI for discovering, searching, and executing all Laravel Artisan commands. Instead of having to remember command names, their arguments and flags, you can interactively browse available commands, search by name or description, and be prompted for required arguments and optional flags. 

This is perfect for any devs who want immediate command discovery and execution without having to search the docs, source dive or google furiously to find the right command. 💪

## Features

- **Interactive Command Search** - Searchable list of all available Artisan commands with descriptions
- **Smart Filtering** - Filter by command namespace or search across command names and descriptions
- **Interactive Arguments & Options** - Using Laravel Prompts, this guides you through required arguments and optional flags
- **Command Preview** - See the exact command that will be executed before confirming
- **Configurable Behavior** - Customise blacklists, scroll behavior, search options, and more
- **Auto-Execute Option** - Skip confirmation and run commands immediately if desired
## Installation

You can install the package via composer:

```bash
composer require joshembling/artisan-browse
```

Then run the package's install command:

```bash
php artisan artisan-browse:install
```

If you'd rather do it separately, you can publish the config file with:

```bash
php artisan vendor:publish --tag="artisan-browse-config"
```

## Usage

Run the interactive browser:

```bash
php artisan browse
```

Filter by namespace:

```bash
php artisan browse make
```

This will show only commands starting with `make:`, like `make:model`, `make:controller`, etc.

### Configuration

Publish the config file and customise the following options:

```php
return [
    // Commands to exclude from the browse list
    'blacklist_commands' => [
        // 'horizon', 'octane', 'sail'
    ],

    // Number of commands to show before enabling scroll
    'select_command_scroll' => 50,

    // Number of options to show before enabling scroll
    'select_options_scroll' => 20,

    // Show command preview before confirmation
    'show_command_preview' => true,

    // Search commands by name and description
    'search_descriptions' => true,

    // Auto-execute without confirmation prompt
    'auto_execute' => false,

    // Options to skip when collecting input
    'skip_options' => [
        'help', 'quiet', 'verbose', 'version', 'ansi', 
        'no-ansi', 'no-interaction', 'env', 'silent'
    ],
];
```

## Demo 

![Artisan Browse Demo](assets/artisan_browse.gif)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Josh Embling](https://github.com/joshembling)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
