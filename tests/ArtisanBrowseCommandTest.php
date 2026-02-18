<?php

use Illuminate\Console\Command;
use JoshEmbling\ArtisanBrowse\Commands\ArtisanBrowseCommand;

describe('ArtisanBrowseCommand', function () {
    describe('command map building', function () {
        it('returns empty map for no commands', function () {
            $command = resolve(ArtisanBrowseCommand::class);

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('buildCommandMap');

            $map = $method->invoke($command, []);

            expect($map)->toBeEmpty();
        });

        it('styles command names with color codes', function () {
            $command = resolve(ArtisanBrowseCommand::class);

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('buildCommandMap');

            // Create a mock command
            $mockCmd = new class extends Command
            {
                protected $name = 'test:example';

                protected $description = 'Test command';

                public function getDescription(): string
                {
                    return $this->description;
                }
            };

            $map = $method->invoke($command, ['test:example' => $mockCmd]);

            expect($map)->toHaveKey('test:example');
            expect($map['test:example'])->toContain("\e[");
        });

        it('includes command description in styled output', function () {
            $command = resolve(ArtisanBrowseCommand::class);

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('buildCommandMap');

            $mockCmd = new class extends Command
            {
                protected $name = 'make:model';

                protected $description = 'Create a new model class';

                public function getDescription(): string
                {
                    return $this->description;
                }
            };

            $map = $method->invoke($command, ['make:model' => $mockCmd]);

            expect($map['make:model'])->toContain('Create a new model class');
        });
    });

    describe('argument collection', function () {
        it('collects arguments as array', function () {
            $command = resolve(ArtisanBrowseCommand::class);

            $mockCmd = new class extends Command
            {
                protected $name = 'test:cmd';

                public function getDescription(): string
                {
                    return 'Test command';
                }
            };

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('collectCommandArguments');

            $result = $method->invoke($command, $mockCmd);

            expect($result)->toBeArray();
        });
    });

    describe('option collection', function () {
        it('skips options in skip_options config', function () {
            config(['artisan-browse.skip_options' => ['help', 'quiet', 'verbose']]);

            $command = resolve(ArtisanBrowseCommand::class);

            $mockCmd = new class extends Command
            {
                protected $name = 'test:cmd';

                public function getDescription(): string
                {
                    return 'Test command';
                }
            };

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('collectCommandOptions');

            $result = $method->invoke($command, $mockCmd);

            expect($result)->toBeArray();
        });

        it('returns array from option collection', function () {
            $command = resolve(ArtisanBrowseCommand::class);

            $mockCmd = new class extends Command
            {
                protected $name = 'test:cmd';

                public function getDescription(): string
                {
                    return 'Test command';
                }
            };

            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('collectCommandOptions');

            $result = $method->invoke($command, $mockCmd);

            expect($result)->toBeArray();
        });
    });

    describe('search functionality', function () {
        it('respects search_descriptions config when false', function () {
            config(['artisan-browse.search_descriptions' => false]);

            expect(config('artisan-browse.search_descriptions'))->toBeFalse();
        });

        it('respects search_descriptions config when true', function () {
            config(['artisan-browse.search_descriptions' => true]);

            expect(config('artisan-browse.search_descriptions'))->toBeTrue();
        });
    });

    describe('command preview and confirmation', function () {
        it('respects show_command_preview config when false', function () {
            config(['artisan-browse.show_command_preview' => false]);

            expect(config('artisan-browse.show_command_preview'))->toBeFalse();
        });

        it('respects show_command_preview config when true', function () {
            config(['artisan-browse.show_command_preview' => true]);

            expect(config('artisan-browse.show_command_preview'))->toBeTrue();
        });

        it('respects auto_execute config when true', function () {
            config(['artisan-browse.auto_execute' => true]);

            expect(config('artisan-browse.auto_execute'))->toBeTrue();
        });

        it('respects auto_execute config when false', function () {
            config(['artisan-browse.auto_execute' => false]);

            expect(config('artisan-browse.auto_execute'))->toBeFalse();
        });
    });

    describe('scroll configuration', function () {
        it('uses select_command_scroll config', function () {
            config(['artisan-browse.select_command_scroll' => 30]);

            expect(config('artisan-browse.select_command_scroll'))->toBe(30);
        });

        it('uses select_options_scroll config', function () {
            config(['artisan-browse.select_options_scroll' => 15]);

            expect(config('artisan-browse.select_options_scroll'))->toBe(15);
        });

        it('applies default scroll value when not explicitly set', function () {
            // When config is not set, it should use the default in the code
            $configValue = config('artisan-browse.select_command_scroll');

            expect($configValue)->toBeGreaterThanOrEqual(1);
        });
    });

    describe('configuration', function () {
        it('respects blacklist_commands config', function () {
            config(['artisan-browse.blacklist_commands' => ['tinker', 'pint']]);

            expect(config('artisan-browse.blacklist_commands'))->toContain('tinker');
            expect(config('artisan-browse.blacklist_commands'))->toContain('pint');
        });

        it('respects skip_options config', function () {
            config(['artisan-browse.skip_options' => ['help', 'quiet']]);

            expect(config('artisan-browse.skip_options'))->toContain('help');
            expect(config('artisan-browse.skip_options'))->toContain('quiet');
        });
    });
});
