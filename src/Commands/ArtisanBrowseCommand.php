<?php

namespace JoshEmbling\ArtisanBrowse\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class ArtisanBrowseCommand extends Command
{
    protected $signature = 'browse {namespace? : Optional namespace or command prefix to filter by}';

    protected $description = 'Interactively browse and run Artisan commands';

    /**
     * Commands that are interactive themselves or otherwise unsafe to proxy.
     */
    protected array $skipCommands = [
        // 'tinker',
        // 'tenant:tinker',
        // 'db',
        // 'browse',
        // 'pail',
        // 'queue:work',
        // 'queue:listen',
        // 'schedule:work',
        // 'boost:mcp',
        // 'mcp:start',
        // 'vapor:handle',
        // 'completion',
    ];

    protected array $blacklistCommands = [
        // 'horizon',
        // 'octane',
        // 'sail',
    ];

    public function handle(): int
    {
        $namespaceFilter = $this->argument('namespace');

        $commands = $this->getFilteredCommands($namespaceFilter);

        if (empty($commands)) {
            warning('No commands found'.($namespaceFilter ? " matching '{$namespaceFilter}'" : '').'.');

            return self::FAILURE;
        }

        // Build the searchable list with styled labels.
        // Array keys are plain command names (used for execution).
        // Array values are ANSI-styled strings (used for display).
        $commandMap = [];
        foreach ($commands as $name => $command) {
            $description = $command->getDescription();

            if (str_contains($name, ':')) {
                [$ns, $cmd] = explode(':', $name, 2);
                $styledName = "\e[93m{$ns}:\e[32m{$cmd}".($description ? "\e[97m - {$description}" : '');
            } else {
                $styledName = "\e[32m{$name}".($description ? "\e[97m - {$description}" : '');
            }

            $commandMap[$name] = $styledName;
        }

        $selectedName = search(
            label: 'Search and select a command'.($namespaceFilter ? " \e[2m(filtered: {$namespaceFilter})\e[0m" : ''),
            options: function (string $query) use ($commandMap) {
                if (empty($query)) {
                    return $commandMap;
                }

                // Filter against plain command names, not the styled labels
                return array_filter(
                    $commandMap,
                    fn ($label, $name) => str_contains(strtolower($name), strtolower($query)),
                    ARRAY_FILTER_USE_BOTH
                );
            },
            placeholder: 'Type to filter commands...',
            scroll: 50,
        );

        if (! $selectedName) {
            return self::SUCCESS;
        }

        $command = $commands[$selectedName];

        // Check if this command is one we should skip
        if (in_array($selectedName, $this->skipCommands)) {
            warning("'{$selectedName}' is interactive or long-running. Run it directly instead.");

            return self::FAILURE;
        }

        // Introspect arguments and options
        $definition = $command->getDefinition();
        $collectedArgs = [];

        // Handle arguments (excluding the default 'command' argument Symfony adds)
        foreach ($definition->getArguments() as $arg) {
            if ($arg->getName() === 'command') {
                continue;
            }

            $required = $arg->isRequired();
            $default = $arg->getDefault();
            $label = $arg->getName().($arg->getDescription() ? " ({$arg->getDescription()})" : '');

            if (! $required) {
                $label = "[optional] {$label}";
            }

            $value = text(
                label: $label,
                default: is_string($default) ? $default : '',
                required: $required,
            );

            if ($value !== '') {
                $collectedArgs[$arg->getName()] = $value;
            }
        }

        // Handle options — only show non-default Symfony noise options
        $skipOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env', 'silent'];
        $options = array_filter(
            $definition->getOptions(),
            fn ($opt) => ! in_array($opt->getName(), $skipOptions)
        );

        if (! empty($options)) {
            // Build a labelled list for multiselect
            $optionChoices = [];
            foreach ($options as $opt) {
                $flag = $opt->acceptValue() ? '--'.$opt->getName().'=' : '--'.$opt->getName();
                $label = $opt->getDescription() ? "{$flag}  \e[90m{$opt->getDescription()}\e[0m" : $flag;
                $optionChoices[$opt->getName()] = $label;
            }

            $selectedOptions = \Laravel\Prompts\multiselect(
                label: 'Select options to set (space to select, enter to confirm)',
                options: $optionChoices,
                default: [],
                required: false,
                scroll: 20,
            );

            foreach ($selectedOptions as $optName) {
                $opt = $options[$optName];
                $optDesc = $opt->getDescription();
                $default = $opt->getDefault();

                if (! $opt->acceptValue()) {
                    // Boolean flag — selected means enabled
                    $collectedArgs["--{$optName}"] = true;
                } else {
                    $value = text(
                        label: "--{$optName}".($optDesc ? ": {$optDesc}" : ''),
                        default: is_string($default) ? $default : '',
                        required: false,
                    );
                    if ($value !== '') {
                        $collectedArgs["--{$optName}"] = $value;
                    }
                }
            }
        }

        // Preview and confirm
        $preview = $selectedName;
        foreach ($collectedArgs as $key => $val) {
            if ($val === true) {
                $preview .= " {$key}";
            } elseif (str_starts_with($key, '--')) {
                $preview .= " {$key}={$val}";
            } else {
                $preview .= " {$val}";
            }
        }

        note("Running: php artisan {$preview}");

        $confirmed = confirm('Execute this command?', default: true);

        if (! $confirmed) {
            error('Aborted.');

            return self::SUCCESS;
        }

        return $this->call($selectedName, $collectedArgs);
    }

    protected function getFilteredCommands(?string $namespaceFilter): array
    {
        $all = $this->getApplication()->all();

        $commands = array_filter($all, function ($command, $name) {
            if ($command->isHidden() || $name === 'browse') {
                return false;
            }

            foreach ($this->blacklistCommands as $blacklisted) {
                if (str_starts_with($name, $blacklisted)) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        if ($namespaceFilter) {
            $commands = array_filter(
                $commands,
                fn ($name) => str_starts_with($name, $namespaceFilter),
                ARRAY_FILTER_USE_KEY
            );
        }

        ksort($commands);

        return $commands;
    }
}
