<?php

namespace JoshEmbling\ArtisanBrowse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class ArtisanBrowseCommand extends Command
{
    protected $signature = 'browse {namespace? : Optional namespace or command prefix to filter by}';

    protected $description = 'An interactive terminal UI for browsing, searching, and executing Laravel Artisan commands.';

    private array $blacklistCommands = [];

    public function handle(): int
    {
        $this->blacklistCommands = config('artisan-browse.blacklist_commands', []);

        $namespaceFilter = $this->argument('namespace');

        $commands = $this->getFilteredCommands($namespaceFilter);

        if (empty($commands)) {
            warning('No commands found'.($namespaceFilter ? " matching '{$namespaceFilter}'" : '').'.');

            return self::FAILURE;
        }

        $commandMap = $this->buildCommandMap($commands);

        $selectedName = $this->selectCommand($commandMap, $namespaceFilter);

        if (! $selectedName) {
            return self::SUCCESS;
        }

        $command = $commands[$selectedName];
        $collectedArgs = $this->collectCommandArguments($command);
        $collectedArgs = array_merge($collectedArgs, $this->collectCommandOptions($command));

        if (! $this->previewAndConfirmCommand($selectedName, $collectedArgs)) {
            error('Aborted.');

            return self::SUCCESS;
        }

        return $this->call($selectedName, $collectedArgs);
    }

    /**
     * Build the searchable command map with styled labels.
     * Array keys are plain command names (used for execution).
     * Array values are ANSI-styled strings (used for display).
     */
    private function buildCommandMap(array $commands): array
    {
        $commandMap = [];

        foreach ($commands as $name => $command) {
            $commandMap[$name] = $this->getStyledCommandName($name, $command->getDescription());
        }

        return $commandMap;
    }

    /**
     * Get the styled display name for a command.
     */
    private function getStyledCommandName(string $name, ?string $description): string
    {
        if (str_contains($name, ':')) {
            [$ns, $cmd] = explode(':', $name, 2);

            return "\e[93m{$ns}:\e[32m{$cmd}".($description ? "\e[97m - {$description}" : '');
        }

        return "\e[32m{$name}".($description ? "\e[97m - {$description}" : '');
    }

    /**
     * Show the interactive search prompt and return the selected command name.
     */
    private function selectCommand(array $commandMap, ?string $namespaceFilter): ?string
    {
        $searchDescriptions = config('artisan-browse.search_descriptions', true);

        return search(
            label: 'Search and select a command'.($namespaceFilter ? " \e[2m(filtered: {$namespaceFilter})\e[0m" : ''),
            options: function (string $query) use ($commandMap, $searchDescriptions) {
                if (empty($query)) {
                    return $commandMap;
                }

                $queryLower = Str::lower($query);

                return array_filter(
                    $commandMap,
                    function ($label, $name) use ($queryLower, $searchDescriptions) {
                        $nameMatches = Str::contains(Str::lower($name), $queryLower);

                        if ($searchDescriptions) {
                            return $nameMatches || Str::contains(Str::lower($label), $queryLower);
                        }

                        return $nameMatches;
                    },
                    ARRAY_FILTER_USE_BOTH
                );
            },
            placeholder: 'Type to filter commands...',
            scroll: config('artisan-browse.select_command_scroll', 50),
        );
    }

    /**
     * Collect values for all command arguments.
     */
    private function collectCommandArguments(Command $command): array
    {
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

        return $collectedArgs;
    }

    /**
     * Collect values for all command options.
     */
    private function collectCommandOptions(Command $command): array
    {
        $definition = $command->getDefinition();
        $collectedArgs = [];

        // Options to skip — default Symfony noise options
        $skipOptions = config('artisan-browse.skip_options', []);
        $options = Arr::where(
            $definition->getOptions(),
            fn ($opt) => ! in_array($opt->getName(), $skipOptions)
        );

        if (empty($options)) {
            return $collectedArgs;
        }

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
            scroll: config('artisan-browse.select_options_scroll', 20),
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

        return $collectedArgs;
    }

    /**
     * Preview the command and ask for confirmation before execution.
     */
    private function previewAndConfirmCommand(string $commandName, array $collectedArgs): bool
    {
        $preview = $commandName;

        foreach ($collectedArgs as $key => $val) {
            if ($val === true) {
                $preview .= " {$key}";
            } elseif (str_starts_with($key, '--')) {
                $preview .= " {$key}={$val}";
            } else {
                $preview .= " {$val}";
            }
        }

        if (config('artisan-browse.show_command_preview', true)) {
            note("Running: php artisan {$preview}");
        }

        if (config('artisan-browse.auto_execute', false)) {
            return true;
        }

        return confirm('Execute this command?', default: true);
    }

    protected function getFilteredCommands(?string $namespaceFilter): array
    {
        $all = $this->getApplication()->all();

        $commands = array_filter($all, function ($command, $name) {
            if ($command->isHidden() || $name === 'browse') {
                return false;
            }

            foreach ($this->blacklistCommands as $blacklisted) {
                if (Str::startsWith($name, $blacklisted)) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        if ($namespaceFilter) {
            $commands = array_filter(
                $commands,
                fn ($command, $name) => Str::startsWith($name, $namespaceFilter),
                ARRAY_FILTER_USE_KEY
            );
        }

        ksort($commands);

        return $commands;
    }
}
